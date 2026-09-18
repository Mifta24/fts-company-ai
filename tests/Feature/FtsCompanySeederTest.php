<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Services\AiStaff\CompanyStaffTools;
use Database\Seeders\FtsCompanySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FtsCompanySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_idempotent_and_every_project_links_to_a_service(): void
    {
        $this->seed(FtsCompanySeeder::class);
        $this->seed(FtsCompanySeeder::class);

        $company = Company::sole();
        $this->assertSame($company->id, Company::primary()->id);
        $this->assertSame(7, $company->services()->count());
        $this->assertSame(15, $company->projects()->count());
        $this->assertSame('info@fts-tech.co.id', $company->email);
        $this->assertSame(0, $company->projects()->whereNull('service_id')->count());
        $this->assertSame(1, $company->users()->count());

        foreach ($company->knowledgeItems as $item) {
            $this->assertNotEmpty($item->translations['en']['body'] ?? null, "{$item->title} is missing English");
            $this->assertNotEmpty($item->translations['ja']['body'] ?? null, "{$item->title} is missing Japanese");
        }

        $this->get('/')->assertOk()->assertSee('FTS Menu')->assertSee('Neo Soho Mall');
    }

    public function test_seeded_knowledge_answers_team_and_office_questions(): void
    {
        $this->seed(FtsCompanySeeder::class);
        $company = Company::sole();
        $conversation = $company->conversations()->create(['visitor_token' => (string) Str::uuid()]);

        $tools = new CompanyStaffTools($company, $conversation, 'ja');
        $this->assertStringContainsString('中川', $tools->dispatch('search_knowledge', ['query' => '代表は誰ですか'])['text']);
        $this->assertStringContainsString('Neo Soho', $tools->dispatch('search_knowledge', ['query' => 'オフィスの住所'])['text']);

        $en = new CompanyStaffTools($company, $conversation, 'en');
        $this->assertStringContainsString('3 to 6 weeks', $en->dispatch('search_knowledge', ['query' => 'how long does a website take'])['text']);

        $japanProjects = $en->dispatch('show_projects', ['keyword' => 'japan'])['ui']['projects'];
        $this->assertNotEmpty($japanProjects);
        $this->assertNotContains('Thai Travel', array_column($japanProjects, 'name'));
    }
}
