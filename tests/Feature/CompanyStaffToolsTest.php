<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Conversation;
use App\Models\HandoverRequest;
use App\Models\Lead;
use App\Services\AiStaff\CompanyStaffTools;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesCompany;
use Tests\TestCase;

class CompanyStaffToolsTest extends TestCase
{
    use CreatesCompany, RefreshDatabase;

    private Company $company;

    private Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = $this->createCompany();
        $this->conversation = $this->company->conversations()->create(['visitor_token' => (string) Str::uuid(), 'locale' => 'id']);
    }

    private function tools(string $locale = 'id'): CompanyStaffTools
    {
        return new CompanyStaffTools($this->company, $this->conversation, $locale);
    }

    public function test_search_knowledge_ranks_matches_and_skips_inactive_entries(): void
    {
        $this->company->knowledgeItems()->create(['category' => 'about', 'title' => 'Pasar Jepang', 'body' => 'Kami melayani Jepang.', 'is_active' => true]);
        $this->company->knowledgeItems()->create(['category' => 'faq', 'title' => 'Jepang rahasia', 'body' => 'Tidak aktif.', 'is_active' => false]);
        $this->company->knowledgeItems()->create(['category' => 'faq', 'title' => 'Harga', 'body' => 'Sesuai scope.', 'is_active' => true]);

        $result = $this->tools()->dispatch('search_knowledge', ['query' => 'melayani jepang']);

        $this->assertStringContainsString('Pasar Jepang', $result['text']);
        $this->assertStringNotContainsString('rahasia', $result['text']);
        $this->assertStringNotContainsString('Harga', $result['text']);
    }

    public function test_search_knowledge_matches_unspaced_japanese_questions_through_tags(): void
    {
        $this->company->knowledgeItems()->create([
            'category' => 'pricing', 'title' => 'Kebijakan harga', 'body' => 'Sesuai scope.', 'tags' => ['料金'],
            'translations' => ['ja' => ['title' => '料金について', 'body' => 'ご相談後にお見積りします。']], 'is_active' => true,
        ]);

        $result = $this->tools('ja')->dispatch('search_knowledge', ['query' => 'AIウェブサイトの料金はいくらですか']);

        $this->assertStringContainsString('ご相談後にお見積りします。', $result['text']);
    }

    public function test_search_knowledge_tells_the_model_not_to_guess_when_nothing_matches(): void
    {
        $result = $this->tools()->dispatch('search_knowledge', ['query' => 'office in paris']);

        $this->assertStringContainsString('Do not guess', $result['text']);
        $this->assertNull($result['ui']);
    }

    public function test_list_services_hides_price_for_quotation_services(): void
    {
        $this->createService($this->company, ['pricing_model' => 'quotation', 'starting_price' => 5000000]);
        $this->createService($this->company, ['slug' => 'menu', 'name' => 'Menu', 'category' => 'saas', 'pricing_model' => 'subscription', 'starting_price' => 49000, 'price_unit' => '/ bulan']);

        $result = $this->tools()->dispatch('list_services', []);
        $services = collect($result['ui']['services'])->keyBy('service_slug');

        $this->assertSame('service_list', $result['ui']['type']);
        $this->assertNull($services['ai-website']['starting_price']);
        $this->assertSame(49000.0, $services['menu']['starting_price']);
    }

    public function test_get_service_detail_returns_translated_features_and_related_projects(): void
    {
        $service = $this->createService($this->company, [
            'features' => ['Fitur A'],
            'translations' => ['en' => ['name' => 'AI Website EN', 'features' => ['Feature A']]],
        ]);
        $this->company->projects()->create(['service_id' => $service->id, 'slug' => 'hotel', 'name' => 'Hotel', 'summary' => 's', 'description' => 'd', 'is_active' => true]);

        $result = $this->tools('en')->dispatch('get_service_detail', ['service_slug' => 'ai-website']);

        $this->assertSame('AI Website EN', $result['ui']['service']['name']);
        $this->assertSame(['Feature A'], $result['ui']['service']['features']);
        $this->assertSame('hotel', $result['ui']['service']['related_projects'][0]['project_slug']);
    }

    public function test_get_service_detail_rejects_unknown_or_other_company_services(): void
    {
        $this->createService($this->createCompany(), ['slug' => 'foreign']);

        $result = $this->tools()->dispatch('get_service_detail', ['service_slug' => 'foreign']);

        $this->assertNull($result['ui']);
        $this->assertStringContainsString('not found', $result['text']);
    }

    public function test_show_projects_filters_by_service(): void
    {
        $service = $this->createService($this->company);
        $this->company->projects()->create(['service_id' => $service->id, 'slug' => 'hotel', 'name' => 'Hotel AI', 'summary' => 's', 'description' => 'd', 'is_active' => true]);
        $this->company->projects()->create(['slug' => 'menu', 'name' => 'Menu SaaS', 'summary' => 's', 'description' => 'd', 'is_active' => true]);

        $result = $this->tools()->dispatch('show_projects', ['service_slug' => 'ai-website']);

        $this->assertSame(['Hotel AI'], array_column($result['ui']['projects'], 'name'));
    }

    public function test_create_lead_requires_a_contact_detail(): void
    {
        $result = $this->tools()->dispatch('create_lead', ['type' => 'consultation', 'name' => 'Budi', 'needs_summary' => 'Wants an AI website']);

        $this->assertNull($result['ui']);
        $this->assertSame(0, Lead::count());
    }

    public function test_create_lead_rejects_an_invalid_email(): void
    {
        $result = $this->tools()->dispatch('create_lead', ['type' => 'demo', 'name' => 'Budi', 'email' => 'budi@', 'needs_summary' => 'x']);

        $this->assertStringContainsString('does not look valid', $result['text']);
        $this->assertSame(0, Lead::count());
    }

    public function test_create_lead_records_the_prospect_and_links_the_service(): void
    {
        $service = $this->createService($this->company);

        $result = $this->tools()->dispatch('create_lead', [
            'type' => 'consultation', 'name' => 'Budi', 'organization' => 'Hotel Melati', 'phone' => '+62 812-3456',
            'business_type' => 'hotel', 'service_slug' => 'ai-website', 'needs_summary' => 'Wants an AI concierge for 40 rooms.',
        ]);

        $lead = Lead::sole();
        $this->assertSame($service->id, $lead->service_id);
        $this->assertSame($this->conversation->id, $lead->conversation_id);
        $this->assertSame('new', $lead->status);
        $this->assertSame('lead_confirmation', $result['ui']['type']);
        $this->assertSame($lead->reference(), $result['ui']['lead']['reference']);
        $this->assertSame('Budi', $this->conversation->fresh()->visitor_name);
    }

    public function test_handover_marks_the_conversation_as_handed_over(): void
    {
        $result = $this->tools()->dispatch('request_human_handover', ['reason' => 'price_negotiation', 'summary' => 'Wants a discount.']);

        $this->assertSame('handover', $result['ui']['type']);
        $this->assertSame(Conversation::STATUS_HANDED_OVER, $this->conversation->fresh()->status);
        $this->assertSame('price_negotiation', HandoverRequest::sole()->reason);
    }
}
