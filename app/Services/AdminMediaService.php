<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\Image\Enums\AlignPosition;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Enums\Unit;
use Spatie\Image\Image;

class AdminMediaService
{
    public function handleOptimizedImage(Request $request, Model $model, string $field, string $folder): ?string
    {
        if (! $request->hasFile($field)) {
            return null;
        }

        $file = $request->file($field);
        $name = time().'-'.$file->getClientOriginalName();
        $file->storeAs('public/'.$folder, $name);
        $model->{$field} = $name;

        $format = strtolower((string) $file->guessExtension()) === 'png' ? 'webp' : $file->guessExtension();

        $img = Image::load($file->getPathname())
            ->optimize()
            ->format($format);

        if (getSetting('watermark2') && file_exists(public_path('upload/images/logo.png'))) {
            $img->watermark(
                public_path('upload/images/logo.png'),
                AlignPosition::BottomLeft, 5, 5, Unit::Percent,
                config('app.media.watermark_size', 10), Unit::Percent,
                config('app.media.watermark_size', 10), Unit::Percent,
                Fit::Contain,
                config('app.media.watermark_opacity', 50)
            );
        }

        $img->save(storage_path('app/public/'.$folder.'/optimized-'.$name));

        return $name;
    }
}
