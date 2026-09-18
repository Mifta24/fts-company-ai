<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'service_id',
    'slug',
    'name',
    'client_name',
    'industry',
    'status',
    'summary',
    'description',
    'translations',
    'highlights',
    'tech_stack',
    'tags',
    'live_url',
    'image_url',
    'is_featured',
    'is_active',
    'sort_order',
])]
class Project extends Model
{
    use HasTranslations;

    public const STATUSES = ['live', 'pilot', 'demo', 'in_development'];

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'highlights' => 'array',
            'tech_stack' => 'array',
            'tags' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return list<string>
     */
    public function translatedHighlights(string $locale): array
    {
        $value = $this->translations[$locale]['highlights'] ?? null;

        return filled($value) ? $value : ($this->highlights ?? []);
    }
}
