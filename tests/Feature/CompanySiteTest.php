<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCompany;
use Tests\TestCase;

class CompanySiteTest extends TestCase
{
    use CreatesCompany, RefreshDatabase;

    public function test_home_is_ai_first_and_results_section_lists_active_projects_only(): void
    {
        $company = $this->createCompany(['name' => 'FTS']);
        $service = $this->createService($company, ['name' => 'AI Website']);
        $company->projects()->create([
            'service_id' => $service->id, 'slug' => 'hotel', 'name' => 'Hotel AI Website', 'status' => 'demo',
            'summary' => 'Hotel demo', 'description' => 'Desc', 'is_active' => true,
        ]);
        $company->projects()->create([
            'service_id' => $service->id, 'slug' => 'draft-project', 'name' => 'Draft Project', 'status' => 'demo',
            'summary' => 'Not ready', 'description' => 'Desc', 'is_active' => false,
        ]);

        // The chat is the page — the portfolio is only rendered (hidden) for the "view results" toggle to reveal.
        $this->get('/')
            ->assertOk()
            ->assertSee('data-staff-panel', false)
            ->assertSee('data-results', false)
            ->assertSee('Hotel AI Website')
            ->assertDontSee('Draft Project');
    }

    public function test_quotation_services_never_show_a_price(): void
    {
        $company = $this->createCompany();
        $this->createService($company, ['pricing_model' => 'quotation', 'starting_price' => 999999]);

        $this->get('/')->assertOk()->assertDontSee('999.999')->assertSee('Harga sesuai kebutuhan');
    }

    public function test_language_switch_translates_interface_and_content(): void
    {
        $company = $this->createCompany([
            'tagline' => 'Website yang bekerja',
            'translations' => ['ja' => ['tagline' => 'スタッフのように働く']],
        ]);
        $this->createService($company, ['translations' => ['ja' => ['name' => 'AIウェブサイト']]]);

        $this->get('/?lang=en')->assertOk()->assertSee('Talk to AI Staff');
        $this->get('/?lang=ja')->assertOk()->assertSee('AIスタッフに相談')->assertSee('AIウェブサイト')->assertSee('スタッフのように働く');
        $this->get('/?lang=xx')->assertOk()->assertSee('Bicara dengan AI Staff');
    }

    public function test_site_is_not_found_without_a_published_company(): void
    {
        $this->createCompany(['public_status' => 'draft']);

        $this->get('/')->assertNotFound();
    }
}
