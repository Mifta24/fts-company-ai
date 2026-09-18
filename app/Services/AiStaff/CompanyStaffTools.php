<?php

namespace App\Services\AiStaff;

use App\Models\Company;
use App\Models\Conversation;
use App\Models\HandoverRequest;
use App\Models\KnowledgeItem;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Executes the AI Staff's tools against this company's controlled data.
 * Every company fact, service, price and portfolio answer must pass through
 * here — the model itself is never trusted to hold any of it.
 */
class CompanyStaffTools
{
    public function __construct(
        private readonly Company $company,
        private readonly Conversation $conversation,
        private readonly string $locale,
    ) {}

    /**
     * OpenAI-compatible function-calling schema.
     *
     * @return list<array{type: string, function: array<string, mixed>}>
     */
    public static function definitions(): array
    {
        return array_map(
            fn (array $tool) => ['type' => 'function', 'function' => $tool],
            [
                [
                    'name' => 'search_knowledge',
                    'description' => 'Search the company\'s approved knowledge base: who the company is, each leadership team member (category "team"), core values, markets served (Indonesia/Japan), office address and hours, response times, how projects run, website plan pricing, support, and FAQs (e.g. development timeline). Always use this instead of answering company-fact questions from memory. Returns up to 5 entries.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'Keywords from the visitor question, e.g. "timeline", "support", "Japan office"'],
                            'category' => [
                                'type' => 'string',
                                'enum' => KnowledgeItem::CATEGORIES,
                                'description' => 'Optional category filter',
                            ],
                        ],
                        'required' => ['query'],
                    ],
                ],
                [
                    'name' => 'list_services',
                    'description' => 'List the services the company offers, shown to the visitor as cards. Use this when the visitor asks what the company does, what it sells, or which service fits them.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'category' => [
                                'type' => 'string',
                                'enum' => Service::CATEGORIES,
                                'description' => 'Optional filter',
                            ],
                        ],
                    ],
                ],
                [
                    'name' => 'get_service_detail',
                    'description' => 'Full details of one service by slug: features, who it is for, pricing (only if published), and related portfolio projects. ALWAYS call this before telling a visitor any price — never state a price from memory.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'service_slug' => ['type' => 'string', 'description' => 'Slug from list_services, e.g. "ai-website"'],
                        ],
                        'required' => ['service_slug'],
                    ],
                ],
                [
                    'name' => 'show_projects',
                    'description' => 'Show portfolio projects and solutions as cards with screenshots. Use this when the visitor asks for examples, case studies, previous work, or proof that a service works.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'service_slug' => ['type' => 'string', 'description' => 'Optional: only projects for this service'],
                            'industry' => ['type' => 'string', 'description' => 'Optional: e.g. hospitality, restaurant, automotive, tourism, beauty'],
                            'keyword' => ['type' => 'string', 'description' => 'Optional single keyword, e.g. "japan", "booking", "company profile"'],
                        ],
                    ],
                ],
                [
                    'name' => 'get_project_detail',
                    'description' => 'Full details of one portfolio project by slug: what it does, highlights, tech stack, status and live link.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'project_slug' => ['type' => 'string'],
                        ],
                        'required' => ['project_slug'],
                    ],
                ],
                [
                    'name' => 'create_lead',
                    'description' => 'Register the visitor as a prospect after they agree to a consultation, demo, or quotation. Required before calling: their name, at least one contact (email or phone/WhatsApp), and a short summary of their needs. Confirm details with the visitor first. Does not schedule a fixed meeting — the sales team follows up.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'type' => ['type' => 'string', 'enum' => Lead::TYPES],
                            'name' => ['type' => 'string'],
                            'organization' => ['type' => 'string', 'description' => 'Company or business name'],
                            'email' => ['type' => 'string'],
                            'phone' => ['type' => 'string', 'description' => 'Phone or WhatsApp number'],
                            'business_type' => ['type' => 'string', 'description' => 'e.g. hotel, restaurant, clinic, retail'],
                            'country' => ['type' => 'string'],
                            'service_slug' => ['type' => 'string', 'description' => 'The service they are interested in, if known'],
                            'preferred_contact' => ['type' => 'string', 'enum' => ['whatsapp', 'email', 'phone', 'meeting']],
                            'preferred_time' => ['type' => 'string', 'description' => 'When they prefer to be contacted, in their words'],
                            'budget_range' => ['type' => 'string'],
                            'needs_summary' => ['type' => 'string', 'description' => 'What they want to achieve, written for a sales colleague who has not read this conversation'],
                        ],
                        'required' => ['type', 'name', 'needs_summary'],
                    ],
                ],
                [
                    'name' => 'request_human_handover',
                    'description' => 'Hand this conversation to a human FTS team member. Use for: custom project scoping, price negotiation or discounts, partnership/reseller inquiries, technical support for an existing customer, complaints, or anything you cannot answer confidently from the tools. Always write a clear summary.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'reason' => ['type' => 'string', 'enum' => HandoverRequest::REASONS],
                            'summary' => ['type' => 'string', 'description' => 'What the visitor wants and the context gathered so far, written for a team member who has not seen this conversation'],
                        ],
                        'required' => ['reason', 'summary'],
                    ],
                ],
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{text: string, ui: array<string, mixed>|null}
     */
    public function dispatch(string $name, array $input): array
    {
        return match ($name) {
            'search_knowledge' => $this->searchKnowledge($input),
            'list_services' => $this->listServices($input),
            'get_service_detail' => $this->getServiceDetail($input),
            'show_projects' => $this->showProjects($input),
            'get_project_detail' => $this->getProjectDetail($input),
            'create_lead' => $this->createLead($input),
            'request_human_handover' => $this->requestHumanHandover($input),
            default => ['text' => "Unknown tool: {$name}", 'ui' => null],
        };
    }

    private function searchKnowledge(array $input): array
    {
        $query = Str::lower(trim((string) ($input['query'] ?? '')));
        $category = in_array($input['category'] ?? null, KnowledgeItem::CATEGORIES, true) ? $input['category'] : null;
        $words = collect(preg_split('/[\s,.?!、。？]+/u', $query))->filter(fn (string $word) => mb_strlen($word) >= 3);

        $items = $this->company->knowledgeItems()
            ->where('is_active', true)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('sort_order')
            ->get()
            ->map(fn (KnowledgeItem $item) => ['item' => $item, 'score' => $this->relevance($item, $query, $words)])
            ->filter(fn (array $row) => $query === '' || $row['score'] > 0)
            ->sortByDesc('score')
            ->take(5)
            ->pluck('item');

        if ($items->isEmpty()) {
            return [
                'text' => 'No matching knowledge base entry was found. Do not guess — tell the visitor you will confirm with the team, or call request_human_handover.',
                'ui' => null,
            ];
        }

        $results = $items->map(fn (KnowledgeItem $item) => [
            'category' => $item->category,
            'title' => $item->translated('title', $this->locale),
            'body' => $item->translated('body', $this->locale),
        ])->values()->all();

        return ['text' => $this->json($results), 'ui' => null];
    }

    /**
     * Keyword score across every locale's text, plus tag hits — tags are also
     * matched as substrings of the query so unspaced Japanese questions work.
     *
     * @param  Collection<int, string>  $words
     */
    private function relevance(KnowledgeItem $item, string $query, Collection $words): int
    {
        if ($query === '') {
            return 1;
        }

        $tags = collect($item->tags ?? [])->map(fn (string $tag) => Str::lower($tag));
        $haystack = Str::lower($item->title.' '.$item->body.' '.json_encode($item->translations, JSON_UNESCAPED_UNICODE).' '.$tags->implode(' '));

        $score = Str::contains($haystack, $query) ? 5 : 0;
        $score += $words->filter(fn (string $word) => Str::contains($haystack, $word))->count();
        $score += $tags->filter(fn (string $tag) => $tag !== '' && Str::contains($query, $tag))->count() * 2;

        return $score;
    }

    private function listServices(array $input): array
    {
        $category = in_array($input['category'] ?? null, Service::CATEGORIES, true) ? $input['category'] : null;

        $services = $this->company->services()
            ->where('is_active', true)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('sort_order')
            ->get();

        if ($services->isEmpty()) {
            return ['text' => 'No services are listed yet. Offer to connect the visitor with the team.', 'ui' => null];
        }

        $cards = $services->map(fn (Service $service) => [
            'service_slug' => $service->slug,
            'category' => $service->category,
            'name' => $service->translated('name', $this->locale),
            'summary' => $service->translated('summary', $this->locale),
            'is_featured' => $service->is_featured,
            'image_url' => $service->image_url,
            ...$this->priceFields($service),
        ])->values()->all();

        return [
            'text' => $this->json($cards),
            'ui' => ['type' => 'service_list', 'services' => $cards],
        ];
    }

    private function getServiceDetail(array $input): array
    {
        $service = $this->company->services()
            ->where('is_active', true)
            ->where('slug', (string) ($input['service_slug'] ?? ''))
            ->first();

        if (! $service) {
            return ['text' => 'Service not found. Call list_services to see valid slugs.', 'ui' => null];
        }

        $detail = [
            'service_slug' => $service->slug,
            'category' => $service->category,
            'name' => $service->translated('name', $this->locale),
            'summary' => $service->translated('summary', $this->locale),
            'description' => $service->translated('description', $this->locale),
            'features' => $service->translatedFeatures($this->locale),
            'ideal_for' => $service->translations[$this->locale]['ideal_for'] ?? $service->ideal_for ?? [],
            'image_url' => $service->image_url,
            ...$this->priceFields($service),
            'pricing_tiers' => $service->pricing_tiers ?? [],
            'related_projects' => $service->projects()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Project $project) => ['project_slug' => $project->slug, 'name' => $project->translated('name', $this->locale)])
                ->all(),
        ];

        return [
            'text' => $this->json($detail),
            'ui' => ['type' => 'service_detail', 'service' => $detail],
        ];
    }

    private function showProjects(array $input): array
    {
        $serviceSlug = $input['service_slug'] ?? null;
        $industry = $input['industry'] ?? null;
        $keyword = Str::lower(trim((string) ($input['keyword'] ?? '')));

        $projects = $this->company->projects()
            ->where('is_active', true)
            ->when($serviceSlug, fn ($q) => $q->whereHas('service', fn ($s) => $s->where('slug', $serviceSlug)))
            ->when($industry, fn ($q) => $q->where('industry', Str::lower($industry)))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->get()
            ->when($keyword !== '', fn ($all) => $all->filter(fn (Project $project) => Str::contains(
                Str::lower(implode(' ', [
                    $project->name, $project->summary, $project->industry, $project->client_name,
                    implode(' ', $project->tags ?? []), json_encode($project->translations, JSON_UNESCAPED_UNICODE),
                ])),
                $keyword,
            )))
            ->take(6);

        if ($projects->isEmpty() && ($serviceSlug || $industry || $keyword !== '')) {
            return [
                'text' => 'No portfolio project matches that filter. Call show_projects without filters to show the full portfolio instead.',
                'ui' => null,
            ];
        }

        if ($projects->isEmpty()) {
            return ['text' => 'No portfolio projects are published yet.', 'ui' => null];
        }

        $cards = $projects->map(fn (Project $project) => $this->projectCard($project))->values()->all();

        return [
            'text' => $this->json($cards),
            'ui' => ['type' => 'project_list', 'projects' => $cards],
        ];
    }

    private function getProjectDetail(array $input): array
    {
        $project = $this->company->projects()
            ->where('is_active', true)
            ->where('slug', (string) ($input['project_slug'] ?? ''))
            ->with('service')
            ->first();

        if (! $project) {
            return ['text' => 'Project not found. Call show_projects to see valid slugs.', 'ui' => null];
        }

        $detail = [
            ...$this->projectCard($project),
            'description' => $project->translated('description', $this->locale),
            'highlights' => $project->translatedHighlights($this->locale),
            'tech_stack' => $project->tech_stack ?? [],
            'service' => $project->service ? [
                'service_slug' => $project->service->slug,
                'name' => $project->service->translated('name', $this->locale),
            ] : null,
        ];

        return [
            'text' => $this->json($detail),
            'ui' => ['type' => 'project_detail', 'project' => $detail],
        ];
    }

    private function createLead(array $input): array
    {
        $email = trim((string) ($input['email'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));
        $name = trim((string) ($input['name'] ?? ''));
        $summary = trim((string) ($input['needs_summary'] ?? ''));

        if ($name === '' || $summary === '') {
            return ['text' => 'Missing name or needs_summary. Ask the visitor before calling create_lead again.', 'ui' => null];
        }

        if ($email === '' && $phone === '') {
            return ['text' => 'No contact detail given. Ask the visitor for an email or phone/WhatsApp number first.', 'ui' => null];
        }

        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['text' => "The email \"{$email}\" does not look valid. Ask the visitor to double-check it.", 'ui' => null];
        }

        $service = isset($input['service_slug'])
            ? $this->company->services()->where('slug', $input['service_slug'])->first()
            : null;

        $lead = Lead::create([
            'company_id' => $this->company->id,
            'conversation_id' => $this->conversation->id,
            'service_id' => $service?->id,
            'type' => in_array($input['type'] ?? null, Lead::TYPES, true) ? $input['type'] : 'consultation',
            'name' => Str::limit($name, 250, ''),
            'organization' => $input['organization'] ?? null,
            'email' => $email ?: null,
            'phone' => $phone ?: null,
            'business_type' => $input['business_type'] ?? null,
            'country' => $input['country'] ?? null,
            'preferred_contact' => $input['preferred_contact'] ?? null,
            'preferred_time' => $input['preferred_time'] ?? null,
            'budget_range' => $input['budget_range'] ?? null,
            'needs_summary' => $summary,
            'status' => Lead::STATUS_NEW,
        ]);

        $this->conversation->update([
            'visitor_name' => $lead->name,
            'visitor_email' => $lead->email ?? $this->conversation->visitor_email,
        ]);

        $payload = [
            'reference' => $lead->reference(),
            'type' => $lead->type,
            'name' => $lead->name,
            'organization' => $lead->organization,
            'service' => $service?->translated('name', $this->locale),
            'contact' => $lead->email ?? $lead->phone,
            'preferred_contact' => $lead->preferred_contact,
            'status' => 'received',
            'note' => 'The FTS team will follow up. Do not promise a specific meeting time or price.',
        ];

        return [
            'text' => $this->json($payload),
            'ui' => ['type' => 'lead_confirmation', 'lead' => $payload],
        ];
    }

    private function requestHumanHandover(array $input): array
    {
        $reason = in_array($input['reason'] ?? null, HandoverRequest::REASONS, true) ? $input['reason'] : 'low_confidence';
        $summary = trim((string) ($input['summary'] ?? '')) ?: 'Visitor asked to speak with the team.';

        HandoverRequest::create([
            'conversation_id' => $this->conversation->id,
            'reason' => $reason,
            'summary' => $summary,
            'status' => HandoverRequest::STATUS_OPEN,
        ]);

        $this->conversation->update([
            'status' => Conversation::STATUS_HANDED_OVER,
            'handover_summary' => $summary,
        ]);

        return [
            'text' => $this->json([
                'ok' => true,
                'message' => 'A team member has been notified and will join this conversation shortly.',
            ]),
            'ui' => ['type' => 'handover', 'reason' => $reason],
        ];
    }

    // --- helpers ---

    /**
     * @return array{pricing_model: string, starting_price: float|null, currency: string, price_unit: string|null, price_note: string|null}
     */
    private function priceFields(Service $service): array
    {
        return [
            'pricing_model' => $service->pricing_model,
            'starting_price' => $service->hasPublishedPrice() ? (float) $service->starting_price : null,
            'currency' => $this->company->currency,
            'price_unit' => $service->translations[$this->locale]['price_unit'] ?? $service->price_unit,
            'price_note' => $service->translated('price_note', $this->locale),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function projectCard(Project $project): array
    {
        return [
            'project_slug' => $project->slug,
            'name' => $project->translated('name', $this->locale),
            'client_name' => $project->client_name,
            'industry' => $project->industry,
            'status' => $project->status,
            'summary' => $project->translated('summary', $this->locale),
            'image_url' => $project->image_url,
            'live_url' => $project->live_url,
        ];
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
