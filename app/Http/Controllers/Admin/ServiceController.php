<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentCompany;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Service;
use App\Support\AdminInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ServiceController extends Controller
{
    use ResolvesCurrentCompany;

    public function index(Request $request): View
    {
        $company = $this->currentCompany($request);

        $services = $company->services()->withCount('projects')->orderBy('sort_order')->get();

        return view('admin.services.index', compact('company', 'services'));
    }

    public function create(Request $request): View
    {
        return view('admin.services.form', [
            'company' => $this->currentCompany($request),
            'service' => new Service(['category' => 'ai', 'pricing_model' => Service::PRICING_QUOTATION, 'is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);

        $service = $company->services()->create($this->validated($request, $company));

        return redirect()->route('admin.services.index')->with('status', "Service \"{$service->name}\" created.");
    }

    public function edit(Request $request, Service $service): View
    {
        $company = $this->currentCompany($request);
        abort_if($service->company_id !== $company->id, 404);

        return view('admin.services.form', compact('company', 'service'));
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $company = $this->currentCompany($request);
        abort_if($service->company_id !== $company->id, 404);

        $service->update($this->validated($request, $company, $service));

        return redirect()->route('admin.services.index')->with('status', "Service \"{$service->name}\" updated.");
    }

    public function destroy(Request $request, Service $service): RedirectResponse
    {
        $company = $this->currentCompany($request);
        abort_if($service->company_id !== $company->id, 404);

        $service->delete();

        return redirect()->route('admin.services.index')->with('status', 'Service deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Company $company, ?Service $service = null): array
    {
        $request->merge(['slug' => AdminInput::slug($request->input('slug'), (string) $request->input('name'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('services')->where('company_id', $company->id)->ignore($service?->id)],
            'category' => ['required', Rule::in(Service::CATEGORIES)],
            'summary' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'features' => ['nullable', 'string'],
            'ideal_for' => ['nullable', 'string'],
            'pricing_model' => ['required', Rule::in([Service::PRICING_SUBSCRIPTION, Service::PRICING_ONE_TIME, Service::PRICING_QUOTATION])],
            'starting_price' => ['nullable', 'numeric', 'min:0', 'required_unless:pricing_model,quotation'],
            'price_unit' => ['nullable', 'string', 'max:60'],
            'price_note' => ['nullable', 'string', 'max:1000'],
            'pricing_tiers' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'translations.*.name' => ['nullable', 'string', 'max:255'],
            'translations.*.summary' => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
            'translations.*.features' => ['nullable', 'string'],
            'translations.*.price_unit' => ['nullable', 'string', 'max:60'],
            'translations.*.price_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $translations = [];
        foreach (['en', 'ja'] as $locale) {
            $input = $data['translations'][$locale] ?? [];
            $translations[$locale] = [
                'name' => $input['name'] ?? null,
                'summary' => $input['summary'] ?? null,
                'description' => $input['description'] ?? null,
                'features' => AdminInput::lines($input['features'] ?? null),
                'ideal_for' => $service?->translations[$locale]['ideal_for'] ?? [],
                'price_unit' => $input['price_unit'] ?? null,
                'price_note' => $input['price_note'] ?? null,
            ];
        }

        return [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'category' => $data['category'],
            'summary' => $data['summary'],
            'description' => $data['description'],
            'features' => AdminInput::lines($data['features'] ?? null),
            'ideal_for' => AdminInput::lines($data['ideal_for'] ?? null),
            'pricing_model' => $data['pricing_model'],
            'starting_price' => $data['pricing_model'] === Service::PRICING_QUOTATION ? null : $data['starting_price'],
            'price_unit' => $data['price_unit'] ?? null,
            'price_note' => $data['price_note'] ?? null,
            'pricing_tiers' => $this->parseTiers($data['pricing_tiers'] ?? null),
            'image_url' => $data['image_url'] ?? null,
            'translations' => $translations,
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }

    /**
     * One tier per line: "Name | price | unit". Prices are whole amounts, so
     * separators like "Rp49.000" or "49,000" are stripped rather than read as decimals.
     *
     * @return list<array{name: string, price: float, unit: string|null}>
     */
    private function parseTiers(?string $value): array
    {
        return collect(AdminInput::lines($value))
            ->map(function (string $line) {
                $parts = array_map('trim', explode('|', $line));

                return [
                    'name' => $parts[0],
                    'price' => (float) preg_replace('/\D/', '', $parts[1] ?? '0'),
                    'unit' => $parts[2] ?? null,
                ];
            })
            ->values()
            ->all();
    }
}
