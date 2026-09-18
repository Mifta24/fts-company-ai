<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'slug',
    'custom_domain',
    'tagline',
    'description',
    'translations',
    'ai_staff_name',
    'address',
    'city',
    'country',
    'phone',
    'whatsapp',
    'email',
    'website_url',
    'timezone',
    'currency',
    'default_locale',
    'public_status',
])]
class Company extends Model
{
    use HasTranslations, SoftDeletes;

    public const SUPPORTED_LOCALES = ['id', 'en', 'ja'];

    protected function casts(): array
    {
        return [
            'translations' => 'array',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_users')
            ->using(CompanyUser::class)
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function knowledgeItems(): HasMany
    {
        return $this->hasMany(KnowledgeItem::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function isPublished(): bool
    {
        return $this->public_status === 'published';
    }

    /**
     * The company this website instance represents: the one configured in
     * FTS_COMPANY_SLUG, or the first published company.
     */
    public static function primary(): ?self
    {
        $slug = config('services.ai_staff.company_slug');

        return static::query()
            ->where('public_status', 'published')
            ->when($slug, fn ($query) => $query->where('slug', $slug))
            ->orderBy('id')
            ->first();
    }

    public function whatsappUrl(): ?string
    {
        return $this->whatsapp ? 'https://wa.me/'.preg_replace('/\D/', '', $this->whatsapp) : null;
    }
}
