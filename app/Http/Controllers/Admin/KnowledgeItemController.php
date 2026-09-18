<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentCompany;
use App\Http\Controllers\Controller;
use App\Models\KnowledgeItem;
use App\Support\AdminInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KnowledgeItemController extends Controller
{
    use ResolvesCurrentCompany;

    public function index(Request $request): View
    {
        $company = $this->currentCompany($request);

        $items = $company->knowledgeItems()->orderBy('category')->orderBy('sort_order')->get();

        return view('admin.knowledge-items.index', compact('company', 'items'));
    }

    public function create(Request $request): View
    {
        return view('admin.knowledge-items.form', [
            'company' => $this->currentCompany($request),
            'item' => new KnowledgeItem(['category' => 'about', 'is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->currentCompany($request);

        $item = $company->knowledgeItems()->create($this->validated($request));

        return redirect()->route('admin.knowledge-items.index')->with('status', "Knowledge item \"{$item->title}\" created.");
    }

    public function edit(Request $request, KnowledgeItem $knowledgeItem): View
    {
        $company = $this->currentCompany($request);
        abort_if($knowledgeItem->company_id !== $company->id, 404);

        return view('admin.knowledge-items.form', ['company' => $company, 'item' => $knowledgeItem]);
    }

    public function update(Request $request, KnowledgeItem $knowledgeItem): RedirectResponse
    {
        $company = $this->currentCompany($request);
        abort_if($knowledgeItem->company_id !== $company->id, 404);

        $knowledgeItem->update($this->validated($request));

        return redirect()->route('admin.knowledge-items.index')->with('status', "Knowledge item \"{$knowledgeItem->title}\" updated.");
    }

    public function destroy(Request $request, KnowledgeItem $knowledgeItem): RedirectResponse
    {
        $company = $this->currentCompany($request);
        abort_if($knowledgeItem->company_id !== $company->id, 404);

        $knowledgeItem->delete();

        return redirect()->route('admin.knowledge-items.index')->with('status', 'Knowledge item deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(KnowledgeItem::CATEGORIES)],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'translations.en.title' => ['nullable', 'string', 'max:255'],
            'translations.en.body' => ['nullable', 'string'],
            'translations.ja.title' => ['nullable', 'string', 'max:255'],
            'translations.ja.body' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        return [
            'category' => $data['category'],
            'title' => $data['title'],
            'body' => $data['body'],
            'translations' => [
                'en' => ['title' => $data['translations']['en']['title'] ?? null, 'body' => $data['translations']['en']['body'] ?? null],
                'ja' => ['title' => $data['translations']['ja']['title'] ?? null, 'body' => $data['translations']['ja']['body'] ?? null],
            ],
            'tags' => AdminInput::list($data['tags'] ?? null),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }
}
