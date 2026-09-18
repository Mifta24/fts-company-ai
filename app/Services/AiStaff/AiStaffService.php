<?php

namespace App\Services\AiStaff;

use App\Models\Company;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Orchestrates one visitor turn against the self-hosted, OpenAI-compatible
 * chat endpoint (LM Studio / Ollama over Tailscale) — runs the tool-use loop
 * against one company's CompanyStaffTools, persists the conversation, and
 * returns the assistant message (with any UI payload to render).
 *
 * The local model is a "thinking" model that reasons at length before it
 * emits a tool call, so max_tokens must stay generous (see MAX_TOKENS).
 */
class AiStaffService
{
    private const MAX_TOOL_ITERATIONS = 6;

    private const MAX_TOKENS = 4096;

    private const REQUEST_TIMEOUT_SECONDS = 180;

    private const CONNECT_TIMEOUT_SECONDS = 15;

    private const HISTORY_LIMIT = 30;

    public function startConversation(Company $company, string $locale = 'id'): Conversation
    {
        return Conversation::create([
            'company_id' => $company->id,
            'visitor_token' => (string) Str::uuid(),
            'locale' => $locale,
            'status' => Conversation::STATUS_ACTIVE,
            'last_message_at' => now(),
        ]);
    }

    public function reply(Company $company, Conversation $conversation, string $visitorMessage): ConversationMessage
    {
        $conversation->messages()->create([
            'role' => ConversationMessage::ROLE_VISITOR,
            'content' => $visitorMessage,
        ]);

        if ($conversation->isHandedOver()) {
            $conversation->update(['last_message_at' => now()]);

            return $conversation->messages()->create([
                'role' => ConversationMessage::ROLE_SYSTEM,
                'content' => null,
                'ui_payload' => [['type' => 'waiting_for_staff']],
            ]);
        }

        $tools = new CompanyStaffTools($company, $conversation, $conversation->locale);
        $definitions = CompanyStaffTools::definitions();

        $messages = [
            ['role' => 'system', 'content' => $this->buildSystemPrompt($company, $conversation->locale)],
            ...$this->buildHistory($conversation),
        ];

        $toolLog = [];
        $uiPayloads = [];

        $message = $this->chatCompletion($messages, $definitions);

        $iterations = 0;
        while (! empty($message['tool_calls']) && $iterations < self::MAX_TOOL_ITERATIONS) {
            $iterations++;

            $messages[] = [
                'role' => 'assistant',
                'content' => $message['content'] ?? '',
                'tool_calls' => $message['tool_calls'],
            ];

            foreach ($message['tool_calls'] as $call) {
                $name = $call['function']['name'] ?? '';
                $input = json_decode($call['function']['arguments'] ?? '{}', true) ?? [];

                $result = $tools->dispatch($name, $input);

                $toolLog[] = ['name' => $name, 'input' => $input];
                if ($result['ui'] !== null) {
                    $uiPayloads[] = $result['ui'];
                }

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'] ?? '',
                    'content' => $result['text'],
                ];
            }

            $message = $this->chatCompletion($messages, $definitions);
        }

        $text = $this->cleanReply((string) ($message['content'] ?? ''));

        // A tool (e.g. request_human_handover) may have changed the
        // conversation's status directly in the DB this turn.
        $conversation->refresh();
        $conversation->update(['last_message_at' => now()]);

        return $conversation->messages()->create([
            'role' => ConversationMessage::ROLE_ASSISTANT,
            'content' => $text !== '' ? $text : null,
            'ui_payload' => $uiPayloads !== [] ? $uiPayloads : null,
            'tool_calls' => $toolLog !== [] ? $toolLog : null,
        ]);
    }

    public function confirmConsultation(Company $company, Conversation $conversation, int $messageId): ConversationMessage
    {
        return DB::transaction(function () use ($company, $conversation, $messageId): ConversationMessage {
            $conversation = $company->conversations()->lockForUpdate()->findOrFail($conversation->id);
            $message = $conversation->messages()->where('role', ConversationMessage::ROLE_ASSISTANT)->findOrFail($messageId);
            $payloads = $message->ui_payload ?? [];
            $summaryIndex = array_find_key($payloads, fn (array $payload): bool => ($payload['type'] ?? null) === 'consultation_summary');

            if ($summaryIndex === null) {
                throw ValidationException::withMessages(['message_id' => 'This message does not contain a consultation summary.']);
            }

            $draft = $payloads[$summaryIndex];
            if (isset($draft['handover_message_id'])) {
                return $conversation->messages()->findOrFail($draft['handover_message_id']);
            }

            if ($conversation->status !== Conversation::STATUS_ACTIVE || $conversation->messages()
                ->where('role', ConversationMessage::ROLE_VISITOR)->where('id', '>', $messageId)->exists()) {
                throw ValidationException::withMessages(['message_id' => 'Please ask Aya for an updated summary before sending it.']);
            }

            $tools = new CompanyStaffTools($company, $conversation, $conversation->locale);
            $result = $tools->dispatch('request_human_handover', ['reason' => 'custom_project', 'summary' => $draft['summary']]);
            $confirmation = $conversation->messages()->create([
                'role' => ConversationMessage::ROLE_SYSTEM,
                'ui_payload' => [$result['ui']],
            ]);
            $payloads[$summaryIndex]['confirmed'] = true;
            $payloads[$summaryIndex]['handover_message_id'] = $confirmation->id;
            $message->update(['ui_payload' => $payloads]);
            $conversation->update(['last_message_at' => now()]);

            return $confirmation;
        });
    }

    /**
     * One call to the local model's OpenAI-compatible /v1/chat/completions.
     *
     * @return array{content: ?string, tool_calls: ?array}
     */
    private function chatCompletion(array $messages, array $tools): array
    {
        $baseUrl = config('services.local_llm.base_url');

        if (blank($baseUrl)) {
            throw new RuntimeException('LOCAL_LLM_BASE_URL is not configured.');
        }

        $response = Http::withToken((string) config('services.local_llm.api_key'))
            ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
            ->timeout(self::REQUEST_TIMEOUT_SECONDS)
            ->post(rtrim($baseUrl, '/').'/v1/chat/completions', [
                'model' => config('services.local_llm.model'),
                'messages' => $messages,
                'tools' => $tools,
                'tool_choice' => 'auto',
                'temperature' => 0.3,
                'max_tokens' => self::MAX_TOKENS,
            ]);

        if ($response->failed()) {
            throw new RuntimeException("Local LLM request failed: HTTP {$response->status()} — {$response->body()}");
        }

        $message = $response->json('choices.0.message', []);
        $finishReason = $response->json('choices.0.finish_reason');

        if ($finishReason === 'length' && empty($message['tool_calls'])) {
            throw new RuntimeException('Local LLM ran out of tokens mid-thought before it could respond. Increase MAX_TOKENS.');
        }

        return [
            'content' => $message['content'] ?? null,
            'tool_calls' => $message['tool_calls'] ?? null,
        ];
    }

    /**
     * Some local models leak their reasoning inline; the visitor must only
     * ever see the final answer.
     */
    private function cleanReply(string $content): string
    {
        $content = preg_replace('/<think>.*?<\/think>/s', '', $content) ?? $content;
        $content = preg_replace('/^.*<\/think>/s', '', $content) ?? $content;

        return trim($content);
    }

    private function buildHistory(Conversation $conversation): array
    {
        return $conversation->messages()
            ->whereIn('role', [ConversationMessage::ROLE_VISITOR, ConversationMessage::ROLE_ASSISTANT, ConversationMessage::ROLE_STAFF])
            ->reorder()
            ->orderByDesc('id')
            ->take(self::HISTORY_LIMIT)
            ->get()
            ->reverse()
            ->map(fn (ConversationMessage $message) => match ($message->role) {
                ConversationMessage::ROLE_VISITOR => ['role' => 'user', 'content' => (string) $message->content],
                ConversationMessage::ROLE_STAFF => ['role' => 'assistant', 'content' => '[Human FTS team member replied]: '.$message->content],
                default => ['role' => 'assistant', 'content' => (string) $message->content],
            })
            ->filter(fn (array $m) => trim($m['content']) !== '')
            ->values()
            ->all();
    }

    private function buildSystemPrompt(Company $company, string $locale): string
    {
        $localeNames = ['id' => 'Bahasa Indonesia', 'en' => 'English', 'ja' => '日本語 (Japanese, polite です/ます form)'];
        $localeName = $localeNames[$locale] ?? "the visitor's language";
        $today = now($company->timezone)->toDateString();
        $staffName = $company->ai_staff_name;
        $about = $company->translated('description', $locale);

        return <<<PROMPT
        You are {$staffName}, the AI staff member of {$company->name}. You work inside {$company->name}'s own company website. This website is itself a live example of the "AI Website" service the company sells — visitors are often business owners in Indonesia and Japan evaluating that service, so how well you work IS the sales pitch.

        About the company (short): {$about}

        Your job: introduce the company, explain its services, answer questions, show relevant projects and solutions, and guide interested visitors to a consultation, demo, or quotation request.

        Hard rules, never break these:
        1. Reply in {$localeName} unless the visitor clearly switches language, then follow them.
        2. Never state a company fact (history, team, markets, process, support, contact, policies) from memory. Call search_knowledge first. If nothing relevant comes back, say you will confirm with the team, or call request_human_handover — never guess or invent clients, numbers, or awards.
        3. Never state a price from memory. Call get_service_detail. If the service has no published price (starting_price is null / pricing_model is "quotation"), say pricing depends on scope and offer a free consultation or quotation.
        4. When the visitor asks what the company does, call list_services. When they ask for examples, case studies, or proof, call show_projects. Those cards are rendered on screen as you respond — write a short, natural comment on what they are looking at, do not repeat every field.
        5. Understand the visitor before selling: ask about their business type and goal when it helps you recommend the right service. Keep it to one question at a time.
        6. Before calling create_lead you need: their name, an email or phone/WhatsApp, and what they need. Ask for missing ones, then confirm briefly and call it. Never promise a specific meeting time, discount, or delivery date.
        7. Call request_human_handover for: custom project scoping the tools cannot answer, price negotiation or discounts, partnership/reseller inquiries, support for an existing customer, complaints, or when the visitor asks for a human. Write the summary for a colleague who has not read this chat.
        8. Be warm, confident and concise — like a helpful, professional member of the FTS team. Two to four short sentences is usually enough. Plain text only, no markdown tables or headings.
        9. If asked, be honest that you are an AI staff member, and that a human team member can join any time.
        10. For a new consultation, first learn the business type and desired outcome, one question at a time. Once these are clear, call prepare_consultation to show a brief for review. Include only details the visitor gave; do not require a budget or deadline. Explain that they can edit it or press the on-screen button to forward it to FTS. Do not call create_lead or request_human_handover in the same turn as prepare_consultation: the button handles forwarding. If they change the brief, prepare a new summary. A direct request for a human, an existing customer issue or a complaint can still use rule 7 immediately.

        Currency for prices: {$company->currency}. Today's date: {$today}.
        PROMPT;
    }
}
