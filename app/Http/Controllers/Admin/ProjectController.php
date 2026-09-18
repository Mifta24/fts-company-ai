<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentCompany;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Project;
use App\Support\AdminInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    use ResolvesCurrentCompany;

    public function index(Request $request): View
    {
        $company = $this->currentCompany($request);

        $projects = $company->projects()->with('service')->orderBy('sort_order')->get();

        return view('admin.projects.index', compact('company', 'projects'));
    }

    public function create(Request $request): View
    {
        $company = $this->currentCompany($request);

        return view('admin.projects.form', [
            'company' => $company,
            'project' => new Project(['status' => 'live', 'is_active' => true]),
            'services' => $company->services()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);

        $project = $company->projects()->create($this->validated($request, $company));

        return redirect()->route('admin.projects.index')->with('status', "Project \"{$project->name}\" created.");
    }

    public function edit(Request $request, Project $project): View
    {
        $company = $this->currentCompany($request);
        abort_if($project->company_id !== $company->id, 404);

        return view('admin.projects.form', [
            'company' => $company,
            'project' => $project,
            'services' => $company->services()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $company = $this->currentCompany($request);
        abort_if($project->company_id !== $company->id, 404);

        $project->update($this->validated($request, $company, $project));

        return redirect()->route('admin.projects.index')->with('status', "Project \"{$project->name}\" updated.");
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $company = $this->currentCompany($request);
        abort_if($project->company_id !== $company->id, 404);

        $project->delete();

        return redirect()->route('admin.projects.index')->with('status', 'Project deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Company $company, ?Project $project = null): array
    {
        $request->merge(['slug' => AdminInput::slug($request->input('slug'), (string) $request->input('name'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('projects')->where('company_id', $company->id)->ignore($project?->id)],
            'service_id' => ['nullable', Rule::exists('services', 'id')->where('company_id', $company->id)],
            'client_name' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:60'],
            'status' => ['required', Rule::in(Project::STATUSES)],
            'summary' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'highlights' => ['nullable', 'string'],
            'tech_stack' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'],
            'live_url' => ['nullable', 'url', 'max:500'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'translations.*.name' => ['nullable', 'string', 'max:255'],
            'translations.*.summary' => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string'],
            'translations.*.highlights' => ['nullable', 'string'],
        ]);

        $translations = [];
        foreach (['en', 'ja'] as $locale) {
            $input = $data['translations'][$locale] ?? [];
            $translations[$locale] = [
                'name' => $input['name'] ?? null,
                'summary' => $input['summary'] ?? null,
                'description' => $input['description'] ?? null,
                'highlights' => AdminInput::lines($input['highlights'] ?? null),
            ];
        }

        return [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'service_id' => $data['service_id'] ?? null,
            'client_name' => $data['client_name'] ?? null,
            'industry' => isset($data['industry']) ? strtolower($data['industry']) : null,
            'status' => $data['status'],
            'summary' => $data['summary'],
            'description' => $data['description'],
            'highlights' => AdminInput::lines($data['highlights'] ?? null),
            'tech_stack' => AdminInput::list($data['tech_stack'] ?? null),
            'tags' => AdminInput::list($data['tags'] ?? null),
            'live_url' => $data['live_url'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'translations' => $translations,
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }
}
