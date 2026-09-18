<?php

namespace App\Models\Concerns;

/**
 * Reads a per-locale override from the `translations` JSON column, falling
 * back to the base (Indonesian) column when no translation is filled in.
 */
trait HasTranslations
{
    public function translated(string $field, string $locale): mixed
    {
        $value = $this->translations[$locale][$field] ?? null;

        return filled($value) ? $value : $this->{$field};
    }
}
