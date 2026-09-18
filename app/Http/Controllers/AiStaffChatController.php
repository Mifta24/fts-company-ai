<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Services\AiStaff\AiStaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AiStaffChatController extends Controller
{
    public function __construct(private readonly AiStaffService $aiStaff) {}

    public function start(Request $request): JsonResponse
    {
        $company = $this->publishedCompany();

        $locale = in_array($request->input('locale'), Company::SUPPORTED_LOCALES, true)
            ? $request->input('locale')
            : $company->default_locale;

        $conversation = $this->aiStaff->startConversation($company, $locale);

        return response()->json([
            'visitor_token' => $conversation->visitor_token,
            'locale' => $conversation->locale,
        ]);
    }

    public function message(Request $request): JsonResponse
    {
        $company = $this->publishedCompany();

        $data = $request->validate([
            'visitor_token' => ['required', 'uuid'],
            'message' => ['required', 'string', 'max:2000'],
            'locale' => ['nullable', 'in:'.implode(',', Company::SUPPORTED_LOCALES)],
        ]);

        $conversation = $this->findConversation($company, $data['visitor_token']);

        if (isset($data['locale']) && $data['locale'] !== $conversation->locale) {
            $conversation->update(['locale' => $data['locale']]);
        }

        try {
            $assistantMessage = $this->aiStaff->reply($company, $conversation, $data['message']);
        } catch (\Throwable $e) {
            Log::error('AI Staff reply failed', ['company_id' => $company->id, 'error' => $e->getMessage()]);

            return response()->json([
                'error' => 'ai_staff_unavailable',
                'message' => 'The AI Staff is temporarily unavailable. Please try again in a moment.',
            ], 503);
        }

        return response()->json([
            'message' => $this->formatMessage($assistantMessage),
            'status' => $conversation->fresh()->status,
        ]);
    }

    public function consultation(Request $request): JsonResponse
    {
        $company = $this->publishedCompany();
        $data = $request->validate([
            'visitor_token' => ['required', 'uuid'],
            'message_id' => ['required', 'integer', 'min:1'],
        ]);
        $conversation = $this->findConversation($company, $data['visitor_token']);
        $message = $this->aiStaff->confirmConsultation($company, $conversation, (int) $data['message_id']);

        return response()->json([
            'message' => $this->formatMessage($message),
            'status' => $conversation->fresh()->status,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $company = $this->publishedCompany();

        $data = $request->validate(['visitor_token' => ['required', 'uuid']]);

        $conversation = $this->findConversation($company, $data['visitor_token']);

        return response()->json([
            'messages' => $conversation->messages->map(fn (ConversationMessage $m) => $this->formatMessage($m))->values(),
            'status' => $conversation->status,
        ]);
    }

    private function publishedCompany(): Company
    {
        $company = Company::primary();

        abort_if(! $company, 404);

        return $company;
    }

    private function findConversation(Company $company, string $visitorToken): Conversation
    {
        $conversation = Conversation::where('company_id', $company->id)
            ->where('visitor_token', $visitorToken)
            ->first();

        if (! $conversation) {
            throw ValidationException::withMessages([
                'visitor_token' => 'This conversation no longer exists. Please start a new one.',
            ]);
        }

        return $conversation;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMessage(ConversationMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'ui_payload' => $message->ui_payload,
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }
}
