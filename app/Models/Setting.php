<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Setting extends Model
{
    use HasFactory, HasTranslations;

    protected $guarded = [];

    public $translatable = ['value'];

    public static $settingTypes = ['TEXT', 'NUMBER', 'LONGTEXT', 'CODE', 'EDITOR',
        'CATEGORY', 'GROUP', 'CHECKBOX', 'FILE', 'COLOR', 'SELECT', 'MENU', 'LOCATION',
        'ICON', 'DATE', 'DATETIME', 'TIME', 'PRODUCT_QUERY', 'POST_QUERY', 'CATEGORY_SET', 'GROUP_SET'];

    /**
     * Extra attributes for the setting input (e.g. xmin/xmax for NUMBER fields).
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return json_decode($this->data, true) ?? [];
    }
}
