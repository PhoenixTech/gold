<?php

namespace App\Http\Controllers\Traits;

trait HandlesAdminSlugs
{
    /**
     * Generate a unique slug for a given model.
     *
     * @param  mixed  $model  Model instance
     * @param  string  $key  Request key for slug
     * @param  string  $name  Model attribute fallback
     * @return string
     */
    public function getSlug($model, $key = 'slug', $name = 'name')
    {
        if (! request()->has('slug') || request()->input('slug') === null) {
            $slug = sluger($model->$name);
        } else {
            $slug = sluger(request()->input($key, $model->$name));
        }

        return $this->createUniqueSlug($slug, $model->id);
    }

    /**
     * Ensure slug uniqueness against the current model table.
     *
     * @param  string  $slug
     * @param  int|string|null  $id
     * @return string
     */
    public function createUniqueSlug($slug, $id = null)
    {
        $originalSlug = $slug;
        $counter = 1;

        $checkExists = function ($candidate) use ($id) {
            $query = $this->_MODEL_::where('slug', $candidate);
            if ($id !== null) {
                $query->where('id', '<>', $id);
            }

            return $query->exists();
        };

        while ($checkExists($slug)) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
