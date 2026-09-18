<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'company_id',
    'slug',
    'category',
    'name',
    'summary',
    'description',
    'translations',
    'features',
    'ideal_for',
    'pricing_model',
    'starting_price',
    'price_unit',
    'price_note',
    'pricing_tiers',
    'image_url',
    'is_featured',
    'is_active',
    'sort_order',
])]
class Service extends Model
{
    use HasTranslations;

    public const CATEGORIES = ['ai', 'saas', 'development', 'automation'];

    public const PRICING_SUBSCRIPTION = 'subscription';

    public const PRICING_ONE_TIME = 'one_time';

    public const PRICING_QUOTATION = 'quotation';

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'features' => 'array',
            'ideal_for' => 'array',
            'pricing_tiers' => 'array',
            'starting_price' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Features for a locale: translations.{locale}.features overrides the base list.
     *
     * @return list<string>
     */
    public function translatedFeatures(string $locale): array
    {
        $value = $this->translations[$locale]['features'] ?? null;

        return filled($value) ? $value : ($this->features ?? []);
    }

    public function hasPublishedPrice(): bool
    {
        return $this->pricing_model !== self::PRICING_QUOTATION && $this->starting_price !== null;
    }
}
