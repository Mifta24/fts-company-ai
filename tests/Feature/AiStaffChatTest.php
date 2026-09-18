<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesCompany;
use Tests\TestCase;

class AiStaffChatTest extends TestCase
{
    use CreatesCompany, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.local_llm.base_url' => 'http://llm.test',
            'services.local_llm.model' => 'test-model',
        ]);
    }

    private function startConversation(string $locale = 'en'): string
    {
        return $this->postJson('/ai-staff/start', ['locale' => $locale])->assertOk()->json('visitor_token');
    }

    public function test_a_visitor_turn_runs_tools_and_returns_cards(): void
    {
        $company = $this->createCompany();
        $this->createService($company);

        Http::fakeSequence('llm.test/*')
            ->push(['choices' => [['finish_reason' => 'tool_calls', 'message' => [
                'content' => null,
                'tool_calls' => [['id' => 'call_1', 'type' => 'function', 'function' => ['name' => 'list_services', 'arguments' => '{}']]],
            ]]]])
            ->push(['choices' => [['finish_reason' => 'stop', 'message' => [
                'content' => '<think>pick services</think>Here is what we offer.',
            ]]]]);

        $token = $this->startConversation();

        $this->postJson('/ai-staff/message', ['visitor_token' => $token, 'message' => 'What do you do?'])
            ->assertOk()
            ->assertJsonPath('message.content', 'Here is what we offer.')
            ->assertJsonPath('message.ui_payload.0.type', 'service_list')
            ->assertJsonPath('message.ui_payload.0.services.0.service_slug', 'ai-website')
            ->assertJsonPath('status', 'active');

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['messages'][0]['role'] === 'system'
            && str_contains($request['messages'][0]['content'], 'Reply in English'));

        $this->getJson('/ai-staff/history?visitor_token='.$token)
            ->assertOk()
            ->assertJsonCount(2, 'messages')
            ->assertJsonPath('messages.0.role', 'visitor');
    }

    public function test_the_visitor_can_switch_language_mid_conversation(): void
    {
        $this->createCompany();
        Http::fake(['llm.test/*' => Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => 'はい']]]])]);

        $token = $this->startConversation('en');

        $this->postJson('/ai-staff/message', ['visitor_token' => $token, 'message' => 'こんにちは', 'locale' => 'ja'])->assertOk();

        $this->assertSame('ja', Conversation::sole()->locale);
        Http::assertSent(fn ($request) => str_contains($request['messages'][0]['content'], '日本語'));
    }

    public function test_the_ai_is_not_called_once_the_conversation_is_handed_over(): void
    {
        $this->createCompany();
        Http::fake();

        $token = $this->startConversation();
        Conversation::sole()->update(['status' => Conversation::STATUS_HANDED_OVER]);

        $this->postJson('/ai-staff/message', ['visitor_token' => $token, 'message' => 'Hello?'])
            ->assertOk()
            ->assertJsonPath('message.role', ConversationMessage::ROLE_SYSTEM)
            ->assertJsonPath('message.ui_payload.0.type', 'waiting_for_staff')
            ->assertJsonPath('status', 'handed_over');

        Http::assertNothingSent();
    }

    public function test_an_unreachable_model_returns_a_friendly_503(): void
    {
        $this->createCompany();
        Http::fake(['llm.test/*' => Http::response('down', 500)]);

        $token = $this->startConversation();

        $this->postJson('/ai-staff/message', ['visitor_token' => $token, 'message' => 'Hi'])
            ->assertStatus(503)
            ->assertJsonPath('error', 'ai_staff_unavailable');
    }

    public function test_unknown_tokens_are_rejected(): void
    {
        $this->createCompany();

        $this->postJson('/ai-staff/message', ['visitor_token' => '8b1f4a6e-7c1d-4a8e-9b0f-1d2c3e4f5a6b', 'message' => 'Hi'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('visitor_token');
    }

    public function test_a_staff_reply_is_visible_in_history(): void
    {
        $this->createCompany();
        $token = $this->startConversation();
        Conversation::sole()->messages()->create(['role' => ConversationMessage::ROLE_STAFF, 'content' => 'Hi, this is Rina from FTS.']);

        $this->getJson('/ai-staff/history?visitor_token='.$token)
            ->assertOk()
            ->assertJsonPath('messages.0.role', 'staff')
            ->assertJsonPath('messages.0.content', 'Hi, this is Rina from FTS.');
    }
}
