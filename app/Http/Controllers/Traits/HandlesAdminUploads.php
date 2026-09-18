<?php

namespace App\Http\Controllers\Traits;

trait HandlesAdminUploads
{
    /**
     * Store an uploaded file into the public storage disk.
     *
     * @param  string  $key  Request key
     * @param  mixed  $model  Model instance
     * @param  string  $folder  Destination folder inside public storage
     * @return string|null
     */
    public function storeFile($key, $model, $folder)
    {
        if (request()->hasFile($key)) {
            $name = time().'-'.request()->file($key)->getClientOriginalName();
            request()->file($key)->storeAs('public/'.$folder, $name);

            return $name;
        }

        return null;
    }
}
