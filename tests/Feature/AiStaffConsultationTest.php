<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\HandoverRequest;
use App\Services\AiStaff\AiStaffService;
use App\Services\AiStaff\CompanyStaffTools;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesCompany;
use Tests\TestCase;

class AiStaffConsultationTest extends TestCase
{
    use CreatesCompany, RefreshDatabase;

    private function conversation(): Conversation
    {
        return app(AiStaffService::class)->startConversation($this->createCompany(), 'en');
    }

    private function summary(Conversation $conversation): ConversationMessage
    {
        return $conversation->messages()->create([
            'role' => 'assistant',
            'ui_payload' => [['type' => 'consultation_summary', 'summary' => 'Restaurant needs an AI ordering assistant.', 'confirmed' => false]],
        ]);
    }

    public function test_preparing_a_summary_does_not_handover_without_confirmation(): void
    {
        $conversation = $this->conversation();
        $tools = new CompanyStaffTools($conversation->company, $conversation, 'en');

        $result = $tools->dispatch('prepare_consultation', ['needs_summary' => 'Restaurant needs an AI ordering assistant.']);

        $this->assertSame('consultation_summary', $result['ui']['type']);
        $this->assertSame('Restaurant needs an AI ordering assistant.', $result['ui']['summary']);
        $this->assertSame(Conversation::STATUS_ACTIVE, $conversation->fresh()->status);
        $this->assertDatabaseCount('handover_requests', 0);
    }

    public function test_invalid_summary_input_does_not_create_a_card(): void
    {
        $conversation = $this->conversation();
        $tools = new CompanyStaffTools($conversation->company, $conversation, 'en');

        foreach (['', '  ', ['invalid'], str_repeat('a', 2001)] as $summary) {
            $result = $tools->dispatch('prepare_consultation', ['needs_summary' => $summary]);
            $this->assertNull($result['ui']);
        }
    }

    public function test_confirming_a_saved_summary_sends_it_to_the_team_without_calling_the_model(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        $conversation = $this->conversation();
        $summary = $this->summary($conversation);

        $this->postJson('/ai-staff/consultation', [
            'visitor_token' => $conversation->visitor_token, 'message_id' => $summary->id,
            'summary' => 'Tampered browser text should be ignored.',
        ])->assertOk()->assertJsonPath('status', 'handed_over')->assertJsonPath('message.ui_payload.0.type', 'handover');

        $this->assertSame('Restaurant needs an AI ordering assistant.', HandoverRequest::sole()->summary);
        $this->assertSame($conversation->id, HandoverRequest::sole()->conversation_id);
        $this->assertTrue($summary->fresh()->ui_payload[0]['confirmed']);
        Http::assertNothingSent();
    }

    public function test_repeated_confirmation_returns_the_same_handover(): void
    {
        $conversation = $this->conversation();
        $summary = $this->summary($conversation);
        $request = ['visitor_token' => $conversation->visitor_token, 'message_id' => $summary->id];
        $first = $this->postJson('/ai-staff/consultation', $request)->assertOk();

        $this->postJson('/ai-staff/consultation', $request)->assertOk()
            ->assertJsonPath('message.id', $first->json('message.id'));

        $this->assertDatabaseCount('handover_requests', 1);
        $this->assertSame(2, $conversation->messages()->count());
    }

    public function test_another_visitors_summary_cannot_be_confirmed(): void
    {
        $conversation = $this->conversation();
        $other = app(AiStaffService::class)->startConversation($conversation->company);
        $summary = $this->summary($other);

        $this->postJson('/ai-staff/consultation', ['visitor_token' => $conversation->visitor_token, 'message_id' => $summary->id])
            ->assertNotFound();

        $this->assertDatabaseCount('handover_requests', 0);
    }

    public function test_tokens_from_another_company_are_rejected(): void
    {
        $this->createCompany();
        $other = $this->conversation();
        $summary = $this->summary($other);

        $this->postJson('/ai-staff/consultation', ['visitor_token' => $other->visitor_token, 'message_id' => $summary->id])
            ->assertUnprocessable()->assertJsonValidationErrors('visitor_token');

        $this->assertDatabaseCount('handover_requests', 0);
    }

    public function test_a_regular_message_cannot_be_used_as_a_summary(): void
    {
        $conversation = $this->conversation();
        $message = $conversation->messages()->create(['role' => 'assistant', 'content' => 'Hello']);

        $this->postJson('/ai-staff/consultation', ['visitor_token' => $conversation->visitor_token, 'message_id' => $message->id])
            ->assertUnprocessable()->assertJsonValidationErrors('message_id');

        $this->assertDatabaseCount('handover_requests', 0);
    }

    public function test_a_summary_becomes_stale_after_the_visitor_changes_their_needs(): void
    {
        $conversation = $this->conversation();
        $summary = $this->summary($conversation);
        $conversation->messages()->create(['role' => 'visitor', 'content' => 'Actually, I need a hotel website.']);

        $this->postJson('/ai-staff/consultation', ['visitor_token' => $conversation->visitor_token, 'message_id' => $summary->id])
            ->assertUnprocessable()->assertJsonValidationErrors('message_id');

        $this->assertDatabaseCount('handover_requests', 0);
    }

    public function test_confirmation_requires_a_token_and_message_id(): void
    {
        $this->createCompany();

        $this->postJson('/ai-staff/consultation', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['visitor_token', 'message_id']);
    }
}
