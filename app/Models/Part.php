<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class Part extends Model
{
    use HasFactory;

    public function getBlade(): string
    {
        $handle = $this->handleClass();
        $handle::onMount($this);

        return 'segments.'.$this->segment.'.'.$this->part.'.'.$this->part;
    }

    /**
     * @return array{blade: string, data: mixed}
     */
    public function getBladeWithData($item = null)
    {
        $handle = $this->handleClass();
        $data = ($handle && method_exists($handle, 'onMount')) ? $handle::onMount($this, $item) : $this;

        return [
            'blade' => 'client.'.$this->part,
            'data' => $data,
        ];
    }

    /**
     * @return class-string|null
     */
    public function handleClass(): ?string
    {
        return self::segmentClass((string) $this->segment, (string) $this->part);
    }

    /**
     * Load a theme part class from its segment folder.
     * Composer classmap is not required, so dashboard swaps work on production.
     *
     * @return class-string
     */
    public static function segmentClass(string $segment, string $part): ?string
    {
        $className = 'Resources\\Views\\Segments\\'.ucfirst($part);
        if (class_exists($className, false)) {
            return $className;
        }

        $path = resource_path('views/segments/'.$segment.'/'.$part.'/'.$part.'.php');
        if (! is_file($path)) {
            return null;
        }

        require_once $path;

        if (! class_exists($className, false)) {
            return null;
        }

        return $className;
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function getAreaNameAttribute()
    {
        return $this->area_id ? $this->area->name : $this->custom;
    }
}
