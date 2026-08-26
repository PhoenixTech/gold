<?php

use App\Models\Area;
use App\Models\Category;
use App\Models\Group;
use App\Models\Menu;
use App\Models\Part;
use App\Models\Post;
use App\Models\Product;
use App\Models\Rate;
use App\Models\Setting;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Route;

/**
 * @param  $langCode  string code like fa
 * @return bool
 */
function langIsRTL($langCode)
{
    $rtlLanguages = [
        'ar', // Arabic
        'arc', // Aramaic
        'dv', // Divehi
        'fa', // Persian (Farsi)
        'ha', // Hausa
        'he', // Hebrew
        'khw', // Khowar
        'ks', // Kashmiri
        'ku', // Kurdish
        'ps', // Pashto
        'ur', // Urdu
        'yi', // Yiddish
        'ug', // Uyghur
        'sd', // Sindhi
        'syr', // Syriac
        'dhv', // Dhivehi
        'sqr', // Siirt Arabic
        'sam', // Samaritan Aramaic
        'man', // Mandaic
        'men', // Mende
        'nqo', // N'Ko
        'phn', // Phoenician
        'syr', // Syriac
        'th', // Thaana
    ];

    return in_array(strtolower($langCode), $rtlLanguages);
}

/**
 * @param  $lang  string code like fa
 */
function getEmojiLanguagebyCode($lang): string
{
    $languages = [
        'af' => '🇿🇦', // Afrikaans
        'sq' => '🇦🇱', // Albanian
        'am' => '🇪🇹', // Amharic
        'ar' => '🇸🇦', // Arabic
        'hy' => '🇦🇲', // Armenian
        'az' => '🇦🇿', // Azerbaijani
        'eu' => '🇪🇸', // Basque
        'be' => '🇧🇾', // Belarusian
        'bn' => '🇧🇩', // Bengali
        'bs' => '🇧🇦', // Bosnian
        'bg' => '🇧🇬', // Bulgarian
        'ca' => '🇪🇸', // Catalan
        'zh' => '🇨🇳', // Chinese
        'hr' => '🇭🇷', // Croatian
        'cs' => '🇨🇿', // Czech
        'da' => '🇩🇰', // Danish
        'nl' => '🇳🇱', // Dutch
        'en' => '🇺🇸', // English
        'et' => '🇪🇪', // Estonian
        'fi' => '🇫🇮', // Finnish
        'fr' => '🇫🇷', // French
        'gl' => '🇪🇸', // Galician
        'ka' => '🇬🇪', // Georgian
        'de' => '🇩🇪', // German
        'el' => '🇬🇷', // Greek
        'gu' => '🇮🇳', // Gujarati
        'ht' => '🇭🇹', // Haitian
        'he' => '🇮🇱', // Hebrew
        'hi' => '🇮🇳', // Hindi
        'hu' => '🇭🇺', // Hungarian
        'is' => '🇮🇸', // Icelandic
        'id' => '🇮🇩', // Indonesian
        'ga' => '🇮🇪', // Irish
        'it' => '🇮🇹', // Italian
        'ja' => '🇯🇵', // Japanese
        'kk' => '🇰🇿', // Kazakh
        'ko' => '🇰🇷', // Korean
        'lv' => '🇱🇻', // Latvian
        'lt' => '🇱🇹', // Lithuanian
        'mk' => '🇲🇰', // Macedonian
        'ms' => '🇲🇾', // Malay
        'ml' => '🇮🇳', // Malayalam
        'mt' => '🇲🇹', // Maltese
        'mn' => '🇲🇳', // Mongolian
        'no' => '🇳🇴', // Norwegian
        'ps' => '🇦🇫', // Pashto
        'fa' => '🇮🇷', // Persian
        'pl' => '🇵🇱', // Polish
        'pt' => '🇵🇹', // Portuguese
        'ro' => '🇷🇴', // Romanian
        'ru' => '🇷🇺', // Russian
        'sr' => '🇷🇸', // Serbian
        'sk' => '🇸🇰', // Slovak
        'sl' => '🇸🇮', // Slovenian
        'es' => '🇪🇸', // Spanish
        'sw' => '🇰🇪', // Swahili
        'sv' => '🇸🇪', // Swedish
        'ta' => '🇮🇳', // Tamil
        'te' => '🇮🇳', // Telugu
        'th' => '🇹🇭', // Thai
        'tr' => '🇹🇷', // Turkish
        'uk' => '🇺🇦', // Ukrainian
        'ur' => '🇵🇰', // Urdu
        'uz' => '🇺🇿', // Uzbek
        'vi' => '🇻🇳', // Vietnamese
        'cy' => '🇬🇧',  // Welsh
    ];
    $lang = strtolower($lang);
    if (array_key_exists($lang, $languages)) {
        return $languages[$lang];
    } else {
        return '❓';
    }
}

/**
 * has route as named we want this model?
 *
 * @param  $name  string
 * @param  $endRoute  string 'index' or alt list
 */
function hasRoute($name): bool
{
    // create route
    $routes = explode('.', request()->route()->getName());
    $routes[count($routes) - 1] = $name;
    $cRuote = implode('.', $routes);

    if (\Illuminate\Support\Facades\Route::has($cRuote)) {
        return true;
    } else {
        return false;
    }
}

/**
 * get named route url
 *
 * @param  $name  string
 * @param  $args  array
 */
function getRoute($name, $args = []): ?string
{
    // create route
    $routes = explode('.', request()->route()->getName());
    $routes[count($routes) - 1] = $name;
    $cRuote = implode('.', $routes);

    if (\Illuminate\Support\Facades\Route::has($cRuote)) {
        return \route($cRuote, $args);
    } else {
        return null;
    }
}

/**
 * make sort link suffix
 *
 * @param  $col  string
 */
function sortSuffix($col): string
{
    if (request()->sort == $col) {
        if (request('sortType', 'asc') == 'desc') {
            return '&sortType=asc';
        } else {
            return '&sortType=desc';
        }
    } else {
        return '';
    }
}

/**
 * make array compatible | help us to translate
 */
function arrayNormalizeVueCompatible($array, $translate = false): false|string
{
    $result = [];
    foreach ($array as $index => $item) {
        $result[] = ['id' => $index, 'name' => ($translate ? __($item) : $item)];
    }

    return json_encode($result);
}

/**
 * check string is json or not
 */
function isJson($string): bool
{
    json_decode($string);

    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * save admin batch log
 *
 * @param  $cls  class
 */
function logAdminBatch($method, $cls, $ids): void
{
    $act = explode('\\', $method);
    foreach ($ids as $id) {
        auth()->user()->logs()->create([
            'action' => $act[count($act) - 1],
            'loggable_type' => $cls,
            'loggable_id' => $id,
        ]);
    }
}

/**
 * save admin log
 *
 * @param  $cls  class
 */
function logAdmin($method, $cls, $id): void
{
    $act = explode('\\', $method);
    auth()->user()->logs()->create([
        'action' => $act[count($act) - 1],
        'loggable_type' => $cls,
        'loggable_id' => $id,
    ]);
}

function gfx()
{
    return \App\Models\Gfx::pluck('value', 'key');
}

/**
 * http build query with excepts
 *
 * @return string
 */
function queryBuilder($except = null)
{
    $queries = request()->toArray();
    if ($except != null) {
        unset($queries[$except]);
        unset($queries['sortType']);
    }

    return http_build_query($queries);
}

/**
 * @param  $replace_char  string
 * @return string
 */
function sluger($name, $replace_char = '-')
{
    // special chars
    $name = str_replace(['&', '+', '@', '*'], ['and', 'plus', 'at', 'star'], $name);

    // replace non letter or digits by -
    $name = preg_replace('~[^\pL\d\.]+~u', $replace_char, $name);

    // transliterate
    $name = iconv('utf-8', 'utf-8//TRANSLIT', $name);

    // trim
    $name = trim($name, $replace_char);

    // remove duplicate -
    $name = preg_replace('~-+~', $replace_char, $name);

    // lowercase
    $name = strtolower($name);

    if (empty($name)) {
        return 'N-A';
    }

    return substr($name, 0, 120);
}

/**
 * generate last item of breadcrumb of admin panel
 *
 * @return void
 */
function lastCrump()
{

    $routes = explode('.', Route::currentRouteName());
    if (count($routes) != 3) {
        echo '<li class="breadcrumb-item">
        <a>
            <i class="ri-folder-chart-line" ></i>
            <span>'.__(ucfirst($routes[count($routes) - 1])).'</span>
        </a>
    </li>';

        return;
    }
    $route = $routes[count($routes) - 1];
    if ($route == 'home') {
        return;
    }

    if ($route == 'all' || $route == 'index' || $route == 'list') {
        $resource = str_replace('-', ' ', $routes[count($routes) - 2]);
        echo '<li class="breadcrumb-item">
        <a>
            <i class="ri-list-check" ></i>
            <span>'.__(Str::plural(ucfirst($resource))).'</span>
        </a>
    </li>';
    } else {
        $resource = str_replace('-', ' ', $routes[count($routes) - 2]);
        $link = '#';
        $temp = $routes;
        array_pop($temp);
        $temp = implode('.', $temp).'.';
        $link = \route($temp.'index');
        echo '<li class="breadcrumb-item">
        <a href="'.$link.'">
            <i class="ri-list-check" ></i>
            <span>'.__(ucfirst(Str::plural($resource))).'</span>
        </a>
    </li>';
        switch ($route) {
            case 'create':
                $title = __('Add').' '.__($routes[count($routes) - 2]);
                $icon = 'ri-add-line';
                break;
            case 'edit':
                $title = __('Edit').' '.__($routes[count($routes) - 2]);
                $icon = 'ri-edit-line';
                break;
            case 'show':
                $title = __('Show').' '.__($routes[count($routes) - 2]);
                $icon = 'ri-eye-line';
                break;
            case 'sort':
                $title = __('Sort').' '.__($routes[count($routes) - 2]);
                $icon = 'ri-sort-number-asc';
                break;
            case 'trashed':
                $title = __('Trashed').' '.__($routes[count($routes) - 2]);
                $icon = 'ri-delete-bin-6-line';
                break;

            case 'design':
                $title = __('Design').' '.__($routes[count($routes) - 2]);
                $icon = 'ri-paint-brush-line';
                break;
            default:
                $title = __('').' '.__(ucfirst($routes[count($routes) - 1]));
                $icon = 'ri-bubble-chart-line';
                break;
        }
        echo '<li class="breadcrumb-item">
            <a>
                <i class="'.$icon.'" ></i>
               <span> '.$title.' </span>
            </a>
        </li>';
    }
}

/**
 * @param  $cats  array categories or groups as nested ul li wih checkbox
 * @param  $checked  array witch one checked default
 * @param  $parent  null|integer parent id
 * @return string
 */
function showCatNestedControl($cats, $checked = [], $parent = null)
{
    $ret = '';
    foreach ($cats as $cat) {
        if ($cat->parent_id == $parent) {
            $ret .= '<li>';
            $check = in_array($cat->id, $checked) ? 'checked=""' : '';
            $ret .= "<label><input type='checkbox' name='cat[]' value='{$cat->id}' $check />";
            $ret .= $cat->name.'</label>';
            $ret .= showCatNestedControl($cats, $checked, $cat->id);
            $ret .= '</li>';
        }
    }
    if ($parent == null) {
        return $ret;
    } else {
        return "<ul class='ps-3'> $ret </ul>";
    }
}

/**
 * @param  $cats  array categories or groups as nested ul li wih checkbox
 * @param  $checked  array witch one checked default
 * @param  $parent  null|integer parent id
 * @return string
 */
function showCatNested($cats, $parent = null)
{
    $ret = '';
    foreach ($cats as $cat) {
        if ($cat->parent_id == $parent & ! $cat->hide) {
            $ret .= '<li>';
            $ret .= "<a href='".$cat->webUrl()."'>";
            $ret .= $cat->name.'</a>';
            $ret .= showCatNested($cats, $cat->id);
            $ret .= '</li>';
        }
    }
    if ($parent == null) {
        return $ret;
    } else {
        return "<ul class='ps-3'> $ret </ul>";
    }
}

/**
 * find model name form morph
 *
 * @return string
 */
function getModelName($modelable_type, $modelable_id)
{
    $r = explode('\\', $modelable_type);

    return __($r[count($r) - 1]).':'.$modelable_id;
}

/**
 * find model show link form morph
 *
 * @return string
 */
function getModelLink($modelable_type, $modelable_id)
{
    $r = explode('\\', $modelable_type);
    $model = strtolower($r[count($r) - 1]);
    $name = 'admin.'.$model.'.show';
    if (Route::has($name)) {
        return \route($name, $modelable_id);
    } else {
        return '';
    }
}

/**
 * fix action in log
 *
 * @return string
 */
function getAction($act)
{
    $r = explode('::', $act);

    return __(ucfirst($r[count($r) - 1]));

}

/**
 * get all admin routes array
 *
 * @return array
 */
function getAdminRoutes()
{
    $routes = [];
    foreach (Illuminate\Support\Facades\Route::getRoutes() as $r) {
        if (strpos($r->getName(), 'admin') !== false) {
            $routes[] = [
                'name' => $r->getName(),
                'url' => $r->uri(),
            ];
        }
    }

    return $routes;
}

/**
 * get all client routes array
 *
 * @return array
 */
function getClientRoutes()
{
    $routes = [];
    foreach (Illuminate\Support\Facades\Route::getRoutes() as $r) {
        if (strpos($r->getName(), 'admin') === false) {
            $routes[] = [
                'name' => $r->getName(),
                'url' => $r->uri(),
            ];
        }
    }

    return $routes;
}

/**
 * get model with all custom attributes
 *
 * @param  $model  \Illuminate\Database\Eloquent\Model
 * @return void
 */
function modelWithCustomAttrs($model)
{
    $data = $model->toArray();
    $attrs = $model->getMutatedAttributes();
    $attrs = array_diff($attrs, ['translations']);
    foreach ($attrs as $attr) {
        $data[$attr] = $model->getAttribute($attr);
    }

    return $data;
}

/**
 * get max size for upload
 *
 * @return int
 */
function getMaxUploadSize()
{
    $uploadMaxSize = returnBytes(ini_get('upload_max_filesize'));
    $postMaxSize = returnBytes(ini_get('post_max_size'));

    return min($uploadMaxSize, $postMaxSize);
}

/**
 * convert text to byte
 *
 * @return float|int|string
 */
function returnBytes($val)
{
    $last = strtolower($val[strlen($val) - 1]);
    $val = trim(strtolower($val), 'kgm');
    switch ($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $val *= 1024 * 1024 * 1024;
        case 'm':
            $val *= 1024 * 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}

/**
 * convert byte to human readable
 *
 * @return string
 */
function formatFileSize($size)
{
    if ($size < 1024) {
        return $size.' bytes';
    } elseif ($size < 1048576) {
        return number_format($size / 1024, 1).' KB';
    } elseif ($size < 1073741824) {
        return number_format($size / 1048576, 1).' MB';
    } else {
        return number_format($size / 1073741824, 1).' GB';
    }
}

/**
 * generating hash UID by length
 *
 * @return string
 */
function generateUniqueID($length = 8)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $uniqueID = '';

    for ($i = 0; $i < $length; $i++) {
        $randomChar = $chars[rand(0, strlen($chars) - 1)];
        $uniqueID .= $randomChar;
    }

    return $uniqueID;
}

/**
 * comment status to bypass blade error
 *
 * @return array[]
 */
function commentStatuses()
{
    return [
        ['name' => __('Approved'), 'id' => '1'],
        ['name' => __('Rejected'), 'id' => '-1'],
        ['name' => __('Pending'), 'id' => '0'],
    ];
}

/**
 * validate basic setting request b4 save
 *
 * @return mixed|string
 */
function validateSettingRequest($setting, $newValue)
{
    if (! $setting->is_basic) {
        return $newValue;
    }

    switch ($setting->key) {
        case 'optimize':
            if ($newValue != 'jpg' && $newValue != 'webp') {
                return 'webp';
            } else {
                return $newValue;
            }
        case 'gallery_thumb':
        case 'post_thumb':
        case 'product_thumb':
        case 'product_image':
            $temp = explode('x', $newValue);
            if (count($temp) != 2) {
                return '500x500';
            } else {
                if ((int) $temp[0] < 50 || (int) $temp[1] < 50) {
                    return '500x500';
                }
            }
    }

    return $newValue;
}

/***
 * get setting by key
 * @param string $key setting key
 * @return false|mixed|string|null
 */
function getSetting($key)
{
    if (! isset($_SERVER['SERVER_NAME']) || ! \Schema::hasTable('settings')) {
        return false;
    }
    $x = Setting::where('key', $key)->first();
    if ($x == null) {
        //        $a = new \stdClass();
        return '';
    }

    $txtType = ['TEXT', 'LONGTEXT', 'EDITOR'];
    if (config('app.xlang') && ! in_array($x->type, $txtType)) {
        return $x->raw;
    }

    return $x->value;
}

/**
 * validae convert image size
 *
 * @return string[]
 */
function imageSizeConvertValidate($size)
{
    $s = getSetting($size);
    if ($s == null) {

        $t = explode('x', $size);
        if (config('app.media'.$size) == null || config('app.media'.$size) == '') {
            $t[0] = 500;
            $t[1] = 500;
        }

    } else {
        $t = explode('x', $s);
    }

    return $t;

}

/**
 * nested model with data
 *
 * @return string
 */
function nestedWithData($items, $parent_id = null)
{
    $r = '<ol class="ol-sortable">'.PHP_EOL;
    foreach ($items as $item) {
        if ($item->parent_id == $parent_id) {
            $name = $item->name ?? $item->title ?? $item->id;
            $r .= "<li data-id='{$item->id}'> <span> <i class='ri-drag-move-2-line'></i> {$name}</span>".PHP_EOL;
            $r .= nestedWithData($items, $item->id);
            $r .= PHP_EOL.' </li>';
        }
    }
    $r .= '</ol>'.PHP_EOL;

    return $r;
}

/**
 * check has part if return first
 *
 * @return \App\Models\Part|false
 */
function hasPart($areaName)
{
    $a = Area::where('name', $areaName)->first();
    if ($a == null) {
        return false;
    }

    $p = Part::where('area_id', $a->id)->first();
    if ($p == null) {
        return false;
    }

    return $p;

}

/**
 * get parts of area
 *
 * @param  null  $custom  custom theme
 * @return Part[]|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Models\_IH_Part_C
 */
function getParts($areaName, $custom = null)
{
    if ($custom != null) {

        $customs = Part::where('custom', $custom)->orderBy('sort');
        if ($customs->count() > 0) {
            return $customs->get();
        }
    }

    return Area::where('name', $areaName)->first()->parts()->orderBy('sort')->get();
}

/**
 * get setting by group
 *
 * @return array
 */
function getSettingsGroup($group)
{
    $result = [];
    foreach (Setting::where('key', 'LIKE', $group.'%')
        ->whereNotNull('value')->get(['key', 'value']) as $r) {
        if ($r->value != null && $r->value != '') {
            $result[substr($r->key, mb_strlen($group))] = $r->value;
        }
    }

    return $result;
}

/**
 * get different color by backgroun
 *
 * @return string
 */
function getGrayscaleTextColor($bgColor)
{
    // Convert the provided background color to RGB
    $bgRgb = sscanf($bgColor, '#%02x%02x%02x');

    // Calculate the luminance of the background color
    $luminance = (0.299 * $bgRgb[0] + 0.587 * $bgRgb[1] + 0.114 * $bgRgb[2]) / 255;

    // Determine the best color for text based on luminance
    if ($luminance > 0.5) {
        $textColor = '#000000'; // Black text
    } else {
        $textColor = '#ffffff'; // White text
    }

    return $textColor;
}

/**
 * get group by setting key
 *
 * @return Group
 */
function getGroupBySetting($key)
{
    return Group::where('id', getSetting($key) ?? 1)->first();
}

/**
 * get menu by setting key
 *
 * @return Menu
 */
function getMenuBySetting($key)
{
    if (Menu::count() == 0) {
        return [];
    }

    return Menu::where('id', getSetting($key) ?? 1)->first();
}

/**
 * get menu's items by setting key
 *
 * @return array
 */
function getMenuBySettingItems($key)
{
    if (Menu::count() == 0) {
        return [];
    }
    $r = Menu::where('id', getSetting($key) ?? 1)->first();
    if ($r == null) {
        $r = Menu::first();
    }

    return $r->items;
}

/**
 * get group's posts by setting key
 *
 * @param  int  $limit
 * @return \App\Models\Post[]|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Models\_IH_Post_C|array
 */
function getGroupPostsBySetting($key, $limit = 10, $order = 'id', $dir = 'DESC')
{
    $g = Group::where('id', getSetting($key) ?? 1)->first();
    if ($g == null) {
        return [];
    }

    return $g->posts()->where('status', 1)->orderBy($order, $dir)->limit($limit)->get();
}

/**
 * get category's products by setting key
 *
 * @param  int  $limit
 * @param  string  $order
 * @param  string  $dir
 * @return \App\Models\Category[]|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Models\_IH_Post_C
 */
function getCategoryProductBySetting($key, $limit = 10, $order = 'id', $dir = 'DESC')
{
    return Category::where('id', getSetting($key) ?? 1)->first()
        ->products()->where('status', 1)->orderBy($order, $dir)->limit($limit)->get();
}

/**
 * get  products by setting key
 *
 * @param  int  $limit
 * @return \App\Models\Product[]|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Models\_IH_Post_C
 */
function getProductsQueryBySetting($key, $limit = 10)
{
    $data = explode(',', getSetting($key) ?? '1,id,DESC');
    if ($data[0] == 0) {
        $q = Product::where('status', 1);
    } else {
        $q = Category::where('id', $data[0])->first()
            ->products()->where('status', 1);
    }

    return $q->orderBy($data[1], $data[2])->limit($limit)->get();
}
/**
 * get posts by setting key
 *
 * @param  int  $limit
 * @return \App\Models\Post[]|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Models\_IH_Post_C
 */
function getPostsQueryBySetting($key, $limit = 10)
{
    $data = explode(',', getSetting($key) ?? '1,id,DESC');
    if ($data[0] == 0) {
        $q = Post::where('status', 1);
    } else {
        $q = Group::where('id', $data[0])->first()
            ->posts()->where('status', 1);
    }

    return $q->orderBy($data[1], $data[2])->limit($limit)->get();
}

/**
 * get group's posts by setting key
 *
 * @param  int  $limit
 * @param  string  $order
 * @param  string  $dir
 * @return \App\Models\Post[]|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Models\_IH_Post_C | array
 */
function getCategorySubCatsBySetting($key, $limit = 10, $order = 'id', $dir = 'DESC')
{
    $c = Category::where('id', getSetting($key) ?? 1)->first();
    if ($c == null) {
        return [];
    }

    return $c->children()->orderBy($order, $dir)->limit($limit)->get();
}

/**
 * @param  null  $data
 * @param  null  $message
 * @param  null  $metaTitle
 * @param  null  $metaDescription
 * @param  null  $metaImage
 * @param  null  $metaSourceImage
 * @param  null  $ogUrl
 * @param  null  $ogType
 * @param  string  $ogLocate
 * @param  null  $canonical_url
 * @return \Illuminate\Http\JsonResponse
 */
function success($data = null, $message = null, $meta = [], $og = [], $twitter = [], $canonical_url = null, $jsonLd = null)
{
    $defaultMeta = [
        'title' => null,
        'description' => null,
        'image' => null,
        'secure_image' => null,
    ];

    $defaultOg = [
        'url' => null,
        'type' => null,
        'site_name' => config('app.name'),
        'description' => null,
        'locate' => config('app.locale'),
    ];

    $defaultTwitter = [
        'card' => 'summary_large_image',
        'site' => getSetting('social.twitter'),
        'title' => null,
        'description' => null,
        'image' => null,
    ];

    return response()->json([
        'OK' => true,
        'message' => $message,
        'data' => $data,
        'meta' => array_merge($defaultMeta, $meta),
        'og' => array_merge($defaultOg, $og),
        'twitter' => array_merge($defaultTwitter, $twitter),
        'canonical_url' => $canonical_url,
    ]);
}

/**
 * @return \Illuminate\Http\JsonResponse
 */
function errors($errors, $status = 422, $message = null, $data = null)
{
    return response()->json([
        'OK' => false,
        'errors' => $errors,
        'message' => $message,
        'data' => $data,
    ], $status);
}

/**
 * make human readable
 *
 * @return string
 */
function readable($text)
{
    return ucfirst(trim(str_replace(['-', '_', '.'], ' ', $text)));
}

/**
 * register guest logs
 *
 * @return void
 */
function guestLog($action, $type = null, $id = null)
{
    $gl = new \App\Models\GuestLog;
    $gl->action = $action;
    $gl->ip = request()->ip();
    $gl->loggable_type = $type;
    $gl->loggable_id = $id;
    $gl->save();
}

/**
 * is user try more than allowed or not
 *
 * @return bool
 */
function isGuestMaxAttemptTry($action, $max = 5, $minutes = 60)
{
    if (\App\Models\GuestLog::where('ip', request()->ip())
        ->where('action', $action)
        ->where('created_at', '>', time() - ($minutes * 60))->count() >= $max) {
        return true;
    } else {
        return false;
    }
}

/**
 * home url to best experience for multi lang shops
 *
 * @return string
 */
function homeUrl()
{
    return fixUrlLang(\route('client.welcome'));
}

/**
 * posts url to best experience for multi lang shops
 *
 * @return string
 */
function postsUrl()
{
    return fixUrlLang(\route('client.posts'));
}

/**
 * products url to best experience for multi lang shops
 *
 * @return string
 */
function productsUrl()
{
    return fixUrlLang(\route('client.products'));
}

/**
 * clips url to best experience for multi lang shops
 *
 * @return string
 */
function clipsUrl()
{
    return fixUrlLang(\route('client.clips'));
}

/**
 * galleries url to best experience for multi lang shops
 *
 * @return string
 */
function gallariesUrl()
{
    return fixUrlLang(\route('client.galleries'));
}

/**
 * attachments url to best experience for multi lang shops
 *
 * @return string
 */
function attachmentsUrl()
{
    return fixUrlLang(\route('client.attachments'));
}

/**
 * tag url to best experience for multi lang shops
 *
 * @return string
 */
function tagUrl($slug)
{
    return fixUrlLang(route('client.tag', $slug));
}

function usableProp($props): array
{
    $result = [];

    foreach ($props as $prop) {
        $tmp = [];
        foreach (json_decode($prop->options) as $item) {
            $tmp[$item->value] = $item->title;
        }
        $result[$prop->name]['data'] = $tmp;
        $result[$prop->name]['icon'] = $prop->icon;
        $result[$prop->name]['unit'] = $prop->unit;
        $result[$prop->name]['searchable'] = $prop->searchable;
        $result[$prop->name]['priceable'] = $prop->priceable;
        $result[$prop->name]['type'] = $prop->type;
        $result[$prop->name]['label'] = $prop->label;
    }

    return $result;
}

/**
 * Resolve raw cart product IDs and selected quantity IDs from cookies or customer model.
 *
 * @return array{cards: array<int, int>, qs: array<int, int|null>}
 */
function getCartData(): array
{
    $cards = [];
    $qs = [];

    $cookieCard = \Cookie::get('card') ?? (request()->hasCookie('card') ? request()->cookie('card') : null);
    $cookieQ = \Cookie::get('q') ?? (request()->hasCookie('q') ? request()->cookie('q') : null);

    if (is_array($cookieCard)) {
        $cards = $cookieCard;
    } elseif (is_string($cookieCard) && trim($cookieCard) !== '') {
        $decoded = json_decode($cookieCard, true);
        if (is_array($decoded)) {
            $cards = $decoded;
        } else {
            $decoded = json_decode(urldecode($cookieCard), true);
            if (is_array($decoded)) {
                $cards = $decoded;
            }
        }
    }

    if (is_array($cookieQ)) {
        $qs = $cookieQ;
    } elseif (is_string($cookieQ) && trim($cookieQ) !== '') {
        $decoded = json_decode($cookieQ, true);
        if (is_array($decoded)) {
            $qs = $decoded;
        } else {
            $decoded = json_decode(urldecode($cookieQ), true);
            if (is_array($decoded)) {
                $qs = $decoded;
            }
        }
    }

    if (empty($cards) && auth('customer')->check()) {
        $customer = auth('customer')->user();
        if ($customer && ! empty($customer->card)) {
            $data = is_array($customer->card) ? $customer->card : json_decode($customer->card, true);
            if (is_array($data)) {
                if (isset($data['cards']) && is_array($data['cards'])) {
                    $cards = $data['cards'];
                    $qs = $data['quantities'] ?? [];
                } else {
                    $cards = $data;
                }
            }
        }
    }

    return [
        'cards' => array_values(array_filter((array) $cards, fn ($v) => $v !== null && $v !== '')),
        'qs' => array_values((array) $qs),
    ];
}

/**
 * shopping card items
 *
 * @return array<int, array<string, mixed>>
 */
function cardItems(): array
{
    $cart = getCartData();
    $cardIds = $cart['cards'];
    $quantityIds = $cart['qs'];

    if (empty($cardIds)) {
        return [];
    }

    $products = Product::query()
        ->whereIn('id', array_values(array_unique($cardIds)))
        ->with(['availableQuantities'])
        ->get()
        ->keyBy('id');

    $lines = [];
    foreach ($cardIds as $index => $productId) {
        $product = $products->get($productId);
        if ($product === null) {
            continue;
        }

        $line = (new \App\Http\Resources\ProductCardCollection($product))->resolve();
        $selectedId = $quantityIds[$index] ?? null;
        $selected = null;

        if ($selectedId !== null && $selectedId !== '') {
            $piece = $product->quantities()
                ->whereKey($selectedId)
                ->first();

            if ($piece !== null) {
                $selected = (new \App\Http\Resources\QunatityCollection($piece))->resolve();
                $line['price'] = $piece->price;
            }
        }

        $line['q'] = $selected;
        $line['selected_quantity_id'] = $selected['id'] ?? null;
        $lines[] = $line;
    }

    return app(\App\Services\CartQuoteService::class)->applyToLines($lines);
}

/**
 * shopping card items count
 */
function cardCount(): int
{
    $cart = getCartData();

    return count($cart['cards']);
}

/**
 * transports json
 *
 * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
 */
function transports()
{
    return \App\Http\Resources\TransportCollection::collection(\App\Models\Transport::all());
}

/**
 * default transport
 *
 * @return int|mixed|null
 */
function defTrannsport()
{
    if (\App\Models\Transport::where('is_default', 1)->count() == 0) {
        return null;
    }

    return \App\Models\Transport::where('is_default', 1)->first()->id;
}

/**
 * make translate json to use vue components
 *
 * @return false|string
 */
function vueTranslate($array)
{
    return json_encode($array);
}

/**
 * markup json Breadcrumb maker
 *
 * @return string
 */
function markUpBreadcrumbList($items)
{

    $json = [];
    $i = 0;
    foreach ($items as $index => $item) {

        $i++;
        $json[] = [
            '@type' => 'ListItem',
            'position' => $i,
            'name' => $index,
        ];
        if ($item != '' || $item != null) {
            $json[$i - 1]['item'] = $item;
        }
    }

    $json = json_encode($json);

    return <<<RESULT

    <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "BreadcrumbList",
          "itemListElement": $json
        }
    </script>
RESULT;

}

/**
 * fix url for multilang shops
 *
 * @return array|mixed|string|string[]
 */
function fixUrlLang($url)
{
    if (config('app.xlang.active') && app()->getLocale() != config('app.xlang.main')) {
        $welcome = \route('client.welcome');

        return str_replace($welcome, $welcome.'/'.app()->getLocale(), $url);
    }

    return $url;
}

/**
 * Send SMS
 *
 * @return bool
 *
 * @throws \GuzzleHttp\Exception\GuzzleException
 */
function sendingSMS($text, $number, $args)
{

    if (config('app.sms.url') == '' || config('app.sms.url') == null) {
        return false;
    }
    if (config('app.sms.driver') == 'Kavenegar') {
        $url = str_replace('TOKEN', config('app.sms.token'), config('app.sms.url')).'?'.http_build_query($args);
        $response = Http::get($url);
        $r = json_decode($response->body(), true);
        if ($r['return']['status'] != 200) {
            \Illuminate\Support\Facades\Log::error($r);

            return false;
        }

        return true;

    }
    $url = config('app.sms.url');

    foreach ($args as $k => $arg) {
        $text = str_replace('%'.$k, $arg, $text);
    }
    $fields = [
        'user' => config('app.sms.url'),
        'password' => config('app.sms.password'),
        'to' => $number,
        'from' => config('app.sms.number'),
        'text' => $text,
        'isflash' => 'false',
    ];

    // Create a new Guzzle client
    $client = new Client;

    try {
        // Send a POST request
        $response = $client->post($url, [
            'form_params' => $fields,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Cache-Control' => 'no-cache',
            ],
        ]);

        // Get the response body as a string
        $result = $response->getBody()->getContents();
    } catch (\Exception $e) {
        // Handle exception
        // You can log the error or return an error response here
        Log::error($e->getMessage());

        return false;
    }

    return true;

}

/**
 * table of content generator
 *
 * @return array
 */
function generateTOC($html)
{
    // Load HTML into a DOMDocument for parsing
    $doc = new DOMDocument;
    @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

    $toc = '';
    $tocItems = [];
    $lastH2 = '';
    $lastH3 = '';
    $idCounter = 0;

    // Fetch all headings in the document
    $headings = $doc->getElementsByTagName('*');

    foreach ($headings as $heading) {
        if (in_array($heading->nodeName, ['h2', 'h3'])) {
            // Generate a unique ID for each heading
            $id = generateHeadingID($heading->nodeValue, $idCounter);
            $idCounter++;
            $heading->setAttribute('id', $id);

            if ($heading->nodeName === 'h2') {
                $tocItems[] = [
                    'title' => $heading->nodeValue,
                    'id' => $id,
                    'children' => [],
                ];
                $lastH2 = $heading->nodeValue; // Update last H2 title
                $lastH3 = ''; // Reset last H3
            } elseif ($heading->nodeName === 'h3') {
                if ($lastH2) {
                    // Create a new child entry for the last H2
                    $tocItems[count($tocItems) - 1]['children'][] = [
                        'title' => $heading->nodeValue,
                        'id' => $id,
                    ];
                    $lastH3 = $heading->nodeValue; // Update last H3 title
                }
            }
        }
    }

    // Create the TOC HTML
    $toc .= buildTOC($tocItems);

    // Return the modified HTML and the TOC
    return [$toc, $doc->saveHTML()];
}

/**
 * generate heading ID for table of content
 *
 * @return string
 */
function generateHeadingID($text, $counter)
{
    // Convert to lowercase and replace non-alphanumeric characters with dashes
    $id = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $text));

    // Remove leading and trailing dashes
    $id = trim($id, '-');

    // Ensure the ID is not empty
    if (empty($id)) {
        $id = 'heading';
    }

    // Add the counter to ensure uniqueness
    $id .= '-'.$counter;

    return $id;
}

// The buildTOC function remains unchanged
function buildTOC($items)
{
    $html = '<ul>';
    foreach ($items as $item) {
        $html .= '<li>';
        $html .= '<a href="#'.$item['id'].'">'.$item['title'].'</a>';

        if (! empty($item['children'])) {
            $html .= buildTOC($item['children']);
        }

        $html .= '</li>';
    }
    $html .= '</ul>';

    return $html;
}

/**
 * detect last rate of customer
 *
 * @return int|mixed
 */
function detectRateCustomer($type, $id, $evaluation)
{
    if (! auth('customer')->check()) {
        return 0;
    }
    $rate = Rate::where('rater_id', auth('customer')->id())
        ->where('rater_type', \App\Models\Customer::class)
        ->where('rateable_type', $type)
        ->where('rateable_id', $id)
        ->where('evaluation_id', $evaluation);

    if ($rate->count() == 0) {
        return 0;
    } else {
        return $rate->first()->rate;
    }

}

/**
 * @param  $name  string area name
 * @param  $model  \Illuminate\Database\Eloquent\Model $custom model
 * @return Area|mixed
 */
function findArea($name, $model = null)
{

    if ($model != null && $model->theme != null) {
        return json_decode($model->theme);
    }

    return \App\Models\Area::where('name', $name)->first();
}

/**
 * cache number
 *
 * @return false|mixed|string|null
 */
function cacheNumber()
{
    return getSetting('cache_number');
}

/**
 * get website main categories
 *
 * @return Category[]|\LaravelIdea\Helper\App\Models\_IH_Category_C
 */
function getMainCategory($limit = 4, $orderBy = 'sort', $asc = 'ASC')
{
    return \App\Models\Category::whereNull('parent_id')->where('hide', 0)->limit($limit)->orderBy($orderBy, $asc)->get();
}

/**
 * get group's posts by setting key
 *
 * @param  int  $limit
 * @param  string  $order
 * @param  string  $dir
 * @return \App\Models\Post[]|\Illuminate\Database\Eloquent\Collection|\LaravelIdea\Helper\App\Models\_IH_Post_C
 */
function getSubGroupSetting($key, $limit = 10, $order = 'id', $dir = 'DESC')
{
    return Group::where('id', getSetting($key) ?? 1)->first()
        ->children()->orderBy($order, $dir)->limit($limit)->get();
}

/**
 * calculate gold/silver piece price
 *
 * @param  float|int  $gold  base metal price per gram
 * @param  float|int  $gr  weight in grams
 * @param  float|int  $fee  labor/wage percent
 * @param  float|null  $profitRate  profit as fraction (e.g. 0.07); null uses 0.07
 * @param  float|null  $taxRate  tax as fraction (e.g. 0.09); null uses config vat
 * @return int
 */
function CalcPrice($gold, $gr, $fee, ?float $profitRate = null, ?float $taxRate = null)
{
    return app(\App\Services\ProductPriceCalculator::class)->calculateFromParts(
        $gold,
        (float) $gr,
        (float) $fee,
        $profitRate ?? 0.07,
        $taxRate ?? (float) config('app.xshop.vat', 0.09),
        0,
    );
}

/**
 * get website main categories
 *
 * @return Category[]|\LaravelIdea\Helper\App\Models\_IH_Category_C
 */
function getCategoriesSet($key, $limit = 4, $orderBy = 'sort', $asc = 'ASC')
{
    return \App\Models\Category::whereIn('id', json_decode(getSetting($key) ?? []))->where('hide', 0)->limit($limit)->orderBy($orderBy, $asc)->get();
}

/**
 * get website main categories
 *
 * @return Group[]|\LaravelIdea\Helper\App\Models\_IH_Group_C
 */
function getGroupsSet($key, $limit = 4, $orderBy = 'sort', $asc = 'ASC')
{
    return \App\Models\Group::whereIn('id', json_decode(getSetting($key) ?? []))->where('hide', 0)->limit($limit)->orderBy($orderBy, $asc)->get();
}
