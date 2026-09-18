<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\HandoverRequest;
use App\Models\Lead;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesCompany;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use CreatesCompany, RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_pages_render(): void
    {
        $company = $this->createCompany();
        $user = $this->createAdmin($company);
        $service = $this->createService($company);
        $project = $company->projects()->create(['slug' => 'p', 'name' => 'Project', 'summary' => 's', 'description' => 'd']);
        $item = $company->knowledgeItems()->create(['category' => 'faq', 'title' => 'Q', 'body' => 'A']);
        $conversation = $company->conversations()->create(['visitor_token' => (string) Str::uuid()]);
        $lead = Lead::create(['company_id' => $company->id, 'conversation_id' => $conversation->id, 'type' => 'demo', 'name' => 'Budi', 'email' => 'b@example.com', 'needs_summary' => 'Demo please']);
        $handover = HandoverRequest::create(['conversation_id' => $conversation->id, 'reason' => 'partnership', 'summary' => 'Reseller']);

        foreach ([
            '/admin', '/admin/company', '/admin/services', '/admin/services/create', "/admin/services/{$service->id}/edit",
            '/admin/projects', '/admin/projects/create', "/admin/projects/{$project->id}/edit",
            '/admin/knowledge-items', '/admin/knowledge-items/create', "/admin/knowledge-items/{$item->id}/edit",
            '/admin/leads', "/admin/leads/{$lead->id}", '/admin/handovers', "/admin/handovers/{$handover->id}",
        ] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
        }
    }

    public function test_admin_can_create_a_service_with_tiers_and_translations(): void
    {
        $company = $this->createCompany();
        $user = $this->createAdmin($company);

        $this->actingAs($user)->post('/admin/services', [
            'name' => 'FTS Menu', 'category' => 'saas', 'summary' => 'QR menu', 'description' => 'Digital menu',
            'features' => "QR code\nMultilingual", 'pricing_model' => 'subscription', 'starting_price' => 49000,
            'price_unit' => '/ bulan', 'pricing_tiers' => "Free | 0 | / bulan\nStarter | Rp49.000 | / bulan",
            'translations' => ['ja' => ['name' => 'FTSメニュー', 'features' => "QRコード\n多言語"]],
            'is_active' => '1',
        ])->assertRedirect('/admin/services');

        $service = Service::sole();
        $this->assertSame('fts-menu', $service->slug);
        $this->assertSame(['QR code', 'Multilingual'], $service->features);
        $this->assertEquals(49000, $service->pricing_tiers[1]['price']);
        $this->assertSame(['QRコード', '多言語'], $service->translatedFeatures('ja'));
        $this->assertTrue($service->is_active);
    }

    public function test_quotation_services_drop_any_submitted_price(): void
    {
        $company = $this->createCompany();
        $user = $this->createAdmin($company);

        $this->actingAs($user)->post('/admin/services', [
            'name' => 'AI Website', 'category' => 'ai', 'summary' => 's', 'description' => 'd',
            'pricing_model' => 'quotation', 'starting_price' => 123,
        ])->assertRedirect();

        $this->assertNull(Service::sole()->starting_price);
    }

    public function test_admins_cannot_touch_another_companys_records(): void
    {
        $company = $this->createCompany();
        $user = $this->createAdmin($company);
        $other = $this->createCompany();
        $foreignService = $this->createService($other);
        $foreignLead = Lead::create(['company_id' => $other->id, 'type' => 'demo', 'name' => 'X', 'phone' => '1', 'needs_summary' => 'x']);

        $this->actingAs($user)->get("/admin/services/{$foreignService->id}/edit")->assertNotFound();
        $this->actingAs($user)->delete("/admin/services/{$foreignService->id}")->assertNotFound();
        $this->actingAs($user)->get("/admin/leads/{$foreignLead->id}")->assertNotFound();
        $this->assertModelExists($foreignService);
    }

    public function test_lead_status_can_be_updated(): void
    {
        $company = $this->createCompany();
        $user = $this->createAdmin($company);
        $lead = Lead::create(['company_id' => $company->id, 'type' => 'demo', 'name' => 'X', 'phone' => '1', 'needs_summary' => 'x']);

        $this->actingAs($user)->patch("/admin/leads/{$lead->id}/status", ['status' => 'contacted'])->assertRedirect();

        $this->assertSame('contacted', $lead->fresh()->status);
    }

    public function test_resolving_a_handover_returns_the_conversation_to_the_ai(): void
    {
        $company = $this->createCompany();
        $user = $this->createAdmin($company);
        $conversation = $company->conversations()->create(['visitor_token' => (string) Str::uuid(), 'status' => Conversation::STATUS_HANDED_OVER]);
        $handover = HandoverRequest::create(['conversation_id' => $conversation->id, 'reason' => 'complaint', 'summary' => 's']);

        $this->actingAs($user)->post("/admin/handovers/{$handover->id}/reply", ['message' => 'Hello from FTS'])->assertRedirect();
        $this->actingAs($user)->post("/admin/handovers/{$handover->id}/resolve")->assertRedirect('/admin/handovers');

        $this->assertSame('Hello from FTS', $conversation->messages()->sole()->content);
        $this->assertSame(Conversation::STATUS_ACTIVE, $conversation->fresh()->status);
        $this->assertSame('resolved', $handover->fresh()->status);
    }
}
