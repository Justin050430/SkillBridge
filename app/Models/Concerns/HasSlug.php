<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Automatically generates a unique URL slug from a model's "title" column.
 *
 * Used by Course and Assessment so user-facing URLs use clean slugs while the
 * database keeps numeric IDs for internal relationships.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            if (empty($model->slug) || ($model->exists && $model->isDirty('title'))) {
                $model->slug = $model->generateUniqueSlug($model->title, $model->getKey());
            }
        });
    }

    /**
     * Build a unique slug from a title, appending a numeric suffix when the
     * base slug already exists (e.g. "laravel", "laravel-2", "laravel-3").
     */
    public function generateUniqueSlug(string $title, mixed $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'item';

        $slug = $base;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
