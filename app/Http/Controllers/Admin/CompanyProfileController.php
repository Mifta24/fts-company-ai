<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentCompany;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    use ResolvesCurrentCompany;

    public function edit(Request $request): View
    {
        return view('admin.company.edit', ['company' => $this->currentCompany($request)]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ai_staff_name' => ['required', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'translations.en.tagline' => ['nullable', 'string', 'max:255'],
            'translations.en.description' => ['nullable', 'string', 'max:3000'],
            'translations.ja.tagline' => ['nullable', 'string', 'max:255'],
            'translations.ja.description' => ['nullable', 'string', 'max:3000'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'default_locale' => ['required', Rule::in(Company::SUPPORTED_LOCALES)],
            'public_status' => ['required', Rule::in(['draft', 'published'])],
        ]);

        $company->update([
            ...collect($data)->except('translations')->all(),
            'currency' => strtoupper($data['currency']),
            'translations' => [
                'en' => ['tagline' => $data['translations']['en']['tagline'] ?? null, 'description' => $data['translations']['en']['description'] ?? null],
                'ja' => ['tagline' => $data['translations']['ja']['tagline'] ?? null, 'description' => $data['translations']['ja']['description'] ?? null],
            ],
        ]);

        return back()->with('status', 'Company profile updated.');
    }
}
