<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The approved knowledge base the AI Staff retrieves from. This is the ONLY
 * place company facts may come from — the model is never allowed to answer
 * a company-knowledge question from its own memory.
 */
#[Fillable([
    'company_id',
    'category',
    'title',
    'body',
    'translations',
    'tags',
    'is_active',
    'sort_order',
])]
class KnowledgeItem extends Model
{
    use HasTranslations;

    public const CATEGORIES = ['about', 'team', 'values', 'process', 'pricing', 'support', 'faq', 'contact'];

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'tags' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
