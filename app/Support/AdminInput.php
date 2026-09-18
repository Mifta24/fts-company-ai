<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Parses the plain-text list fields admin forms use (one item per line, or
 * comma-separated tags) into the JSON arrays the models store.
 */
class AdminInput
{
    /**
     * @return list<string>
     */
    public static function list(?string $value): array
    {
        return collect(preg_split('/[\r\n,]+/', (string) $value))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function lines(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    public static function slug(?string $slug, string $name): string
    {
        return Str::slug(filled($slug) ? $slug : $name);
    }
}
