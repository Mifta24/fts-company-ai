<?php

namespace Tests\Concerns;

use App\Models\Company;
use App\Models\Service;
use App\Models\User;

trait CreatesCompany
{
    protected function createCompany(array $attributes = []): Company
    {
        static $sequence = 0;
        $sequence++;

        return Company::create([
            'name' => "Company {$sequence}",
            'slug' => "company-{$sequence}",
            'ai_staff_name' => 'Aya',
            'tagline' => 'Tagline',
            'description' => 'A technology company.',
            'public_status' => 'published',
            ...$attributes,
        ]);
    }

    protected function createService(Company $company, array $attributes = []): Service
    {
        return $company->services()->create([
            'slug' => 'ai-website',
            'category' => 'ai',
            'name' => 'AI Website',
            'summary' => 'Website with an AI staff member.',
            'description' => 'Long description.',
            'pricing_model' => 'quotation',
            'is_active' => true,
            ...$attributes,
        ]);
    }

    protected function createAdmin(Company $company): User
    {
        $user = User::factory()->create();
        $company->users()->attach($user->id, ['role' => 'owner', 'status' => 'active']);

        return $user;
    }
}
