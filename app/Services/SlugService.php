<?php

namespace App\Services;

class SlugService
{
    public function makeUnique(string $modelClass, string $title, ?int $ignoreId = null, string $slugField = 'slug'): string
    {
        $baseSlug = sluger($title);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($modelClass, $slug, $ignoreId, $slugField)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    protected function slugExists(string $modelClass, string $slug, ?int $ignoreId, string $slugField): bool
    {
        $query = $modelClass::where($slugField, $slug);

        if ($ignoreId !== null) {
            $query->where('id', '<>', $ignoreId);
        }

        return $query->exists();
    }
}
