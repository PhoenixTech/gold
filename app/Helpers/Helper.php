<?php

use App\Http\Resources\TransportCollection;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Gfx;
use App\Models\Group;
use App\Models\Menu;
use App\Models\Post;
use App\Models\Product;
use App\Models\Rate;
use App\Models\Transport;
use App\Services\BreadcrumbService;
use App\Services\CartStorageService;
use App\Services\LocalizationService;
use App\Services\MenuService;
use App\Services\ProductPriceCalculator;
use App\Services\SettingService;
use App\Services\SmsService;
use App\Services\TableOfContentsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function langIsRTL($langCode): bool
{
    return app(LocalizationService::class)->isRtl((string) $langCode);
}

function getEmojiLanguagebyCode($lang): string
{
    return app(LocalizationService::class)->getEmojiByCode((string) $lang);
}

function hasRoute($name): bool
{
    $routes = explode('.', (string) request()->route()?->getName());
    $routes[count($routes) - 1] = $name;
    $cRoute = implode('.', $routes);

    return Route::has($cRoute);
}

function getRoute($name, $args = []): ?string
{
    $routes = explode('.', (string) request()->route()?->getName());
    $routes[count($routes) - 1] = $name;
    $cRoute = implode('.', $routes);

    if (Route::has($cRoute)) {
        return route($cRoute, $args);
    }

    return null;
}

function sortSuffix($col): string
{
    if (request()->sort == $col) {
        if (request('sortType', 'asc') == 'desc') {
            return '&sortType=asc';
        }

        return '&sortType=desc';
    }

    return '';
}

function arrayNormalizeVueCompatible($array, $translate = false): false|string
{
    $result = [];
    foreach ($array as $index => $item) {
        $result[] = ['id' => $index, 'name' => ($translate ? __($item) : $item)];
    }

    return json_encode($result);
}

function isJson($string): bool
{
    json_decode($string);

    return json_last_error() === JSON_ERROR_NONE;
}

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
    static $gfxCache = null;
    if ($gfxCache !== null) {
        return $gfxCache;
    }

    $defaults = [
        'container' => 'container',
        'dark' => 0,
        'primary' => '#db9a00',
        'secondary' => '#3d3846',
        'background' => '#ffffff',
        'text' => '#111111',
        'font' => 'sans-serif',
    ];

    try {
        $db = Gfx::pluck('value', 'key')->toArray();
        $gfxCache = array_merge($defaults, $db);

        return $gfxCache;
    } catch (Throwable) {
        return $defaults;
    }
}

function queryBuilder($except = null): string
{
    $queries = request()->toArray();
    if ($except != null) {
        unset($queries[$except]);
        unset($queries['sortType']);
    }

    return http_build_query($queries);
}

function sluger($name, $replace_char = '-'): string
{
    $name = str_replace(['&', '+', '@', '*'], ['and', 'plus', 'at', 'star'], $name);
    $name = preg_replace('~[^\pL\d\.]+~u', $replace_char, $name);
    $name = iconv('utf-8', 'utf-8//TRANSLIT', $name);
    $name = trim($name, $replace_char);
    $name = preg_replace('~-+~', $replace_char, $name);
    $name = strtolower($name);

    if (empty($name)) {
        return 'N-A';
    }

    return substr($name, 0, 120);
}

function lastCrump(): void
{
    $routes = explode('.', (string) Route::currentRouteName());
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
        $temp = $routes;
        array_pop($temp);
        $temp = implode('.', $temp).'.';
        $link = route($temp.'index');
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

function showCatNestedControl($cats, $checked = [], $parent = null): string
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
    }

    return "<ul class='ps-3'> $ret </ul>";
}

function showCatNested($cats, $parent = null): string
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
    }

    return "<ul class='ps-3'> $ret </ul>";
}

function getModelName($modelable_type, $modelable_id): string
{
    $r = explode('\\', $modelable_type);

    return __($r[count($r) - 1]).':'.$modelable_id;
}

function getModelLink($modelable_type, $modelable_id): string
{
    $r = explode('\\', $modelable_type);
    $model = strtolower($r[count($r) - 1]);
    $name = 'admin.'.$model.'.show';
    if (Route::has($name)) {
        return route($name, $modelable_id);
    }

    return '';
}

function getAction($act): string
{
    $r = explode('::', $act);

    return __(ucfirst($r[count($r) - 1]));
}

function getAdminRoutes(): array
{
    $routes = [];
    foreach (Route::getRoutes() as $r) {
        if (str_contains($r->getName() ?? '', 'admin')) {
            $routes[] = [
                'name' => $r->getName(),
                'url' => $r->uri(),
            ];
        }
    }

    return $routes;
}

function getClientRoutes(): array
{
    $routes = [];
    foreach (Route::getRoutes() as $r) {
        if (! str_contains($r->getName() ?? '', 'admin')) {
            $routes[] = [
                'name' => $r->getName(),
                'url' => $r->uri(),
            ];
        }
    }

    return $routes;
}

function modelWithCustomAttrs($model): array
{
    $data = $model->toArray();
    $attrs = $model->getMutatedAttributes();
    $attrs = array_diff($attrs, ['translations']);
    foreach ($attrs as $attr) {
        $data[$attr] = $model->getAttribute($attr);
    }

    return $data;
}

function getMaxUploadSize(): int
{
    $uploadMaxSize = returnBytes(ini_get('upload_max_filesize'));
    $postMaxSize = returnBytes(ini_get('post_max_size'));

    return min($uploadMaxSize, $postMaxSize);
}

function returnBytes($val): int
{
    $last = strtolower($val[strlen($val) - 1]);
    $val = (int) trim(strtolower($val), 'kgm');
    switch ($last) {
        case 'g':
            $val *= 1024 * 1024 * 1024;
            break;
        case 'm':
            $val *= 1024 * 1024;
            break;
        case 'k':
            $val *= 1024;
            break;
    }

    return $val;
}

function formatFileSize($size): string
{
    if ($size < 1024) {
        return $size.' bytes';
    } elseif ($size < 1048576) {
        return number_format($size / 1024, 1).' KB';
    } elseif ($size < 1073741824) {
        return number_format($size / 1048576, 1).' MB';
    }

    return number_format($size / 1073741824, 1).' GB';
}

function generateUniqueID($length = 8): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $uniqueID = '';

    for ($i = 0; $i < $length; $i++) {
        $uniqueID .= $chars[rand(0, strlen($chars) - 1)];
    }

    return $uniqueID;
}

function commentStatuses(): array
{
    return [
        ['name' => __('Approved'), 'id' => '1'],
        ['name' => __('Rejected'), 'id' => '-1'],
        ['name' => __('Pending'), 'id' => '0'],
    ];
}

function validateSettingRequest($setting, $newValue)
{
    if (! $setting->is_basic) {
        return $newValue;
    }

    switch ($setting->key) {
        case 'optimize':
            if ($newValue != 'jpg' && $newValue != 'webp') {
                return 'webp';
            }

            return $newValue;
        case 'gallery_thumb':
        case 'post_thumb':
        case 'product_thumb':
        case 'product_image':
            $temp = explode('x', $newValue);
            if (count($temp) != 2) {
                return '500x500';
            }
            if ((int) $temp[0] < 50 || (int) $temp[1] < 50) {
                return '500x500';
            }
    }

    return $newValue;
}

function clearSettingsCache(): void
{
    app(SettingService::class)->clearCache();
}

function getAllSettings($fresh = false)
{
    return app(SettingService::class)->all((bool) $fresh);
}

function getSetting($key)
{
    return app(SettingService::class)->get((string) $key);
}

function imageSizeConvertValidate($size)
{
    return validateSettingRequest((object) ['is_basic' => 1, 'key' => 'gallery_thumb'], $size);
}

function nestedWithData($items, $parent_id = null): array
{
    $nested = [];

    foreach ($items as $item) {
        if ($item['parent'] == $parent_id) {
            $children = nestedWithData($items, $item['id']);
            if ($children) {
                $item['children'] = $children;
            }
            $nested[] = $item;
        }
    }

    return $nested;
}

function getSettingsGroup($group): array
{
    return app(SettingService::class)->group((string) $group);
}

function getGrayscaleTextColor($bgColor): string
{
    $hex = str_replace('#', '', $bgColor);
    if (strlen($hex) == 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }

    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

    return ($brightness > 128) ? '#000000' : '#ffffff';
}

function getGroupBySetting($key)
{
    $val = getSetting($key);

    return Group::whereId($val)->first();
}

function getMenuBySetting($key)
{
    return app(MenuService::class)->getBySetting((string) $key);
}

function getMenuBySettingItems($key)
{
    return app(MenuService::class)->getItemsBySetting((string) $key);
}

function getPrimaryMenu($fresh = false): ?Menu
{
    return app(MenuService::class)->getPrimaryMenu((bool) $fresh);
}

function clearMenuCache(): void
{
    app(MenuService::class)->clearMenuCache();
}

function getPrimaryMenuItems($fresh = false): Collection
{
    return app(MenuService::class)->getPrimaryMenuItems((bool) $fresh);
}

function getGroupPostsBySetting($key, $limit = 10, $order = 'id', $dir = 'DESC')
{
    $group = Group::where('id', getSetting($key) ?? 1)->first();
    if (! $group) {
        return collect();
    }

    return $group->posts()->orderBy($order, $dir)->limit($limit)->get();
}

function getCategoryProductBySetting($key, $limit = 10, $order = 'id', $dir = 'DESC')
{
    $category = Category::where('id', getSetting($key) ?? 1)->first();
    if (! $category) {
        return collect();
    }

    return $category->products()->orderBy($order, $dir)->limit($limit)->get();
}

function getProductsQueryBySetting($key, $limit = 10)
{
    $category = Category::where('id', getSetting($key) ?? 1)->first();
    if (! $category) {
        return Product::whereRaw('1 = 0');
    }

    return $category->products()->limit($limit);
}

function getPostsQueryBySetting($key, $limit = 10)
{
    $group = Group::where('id', getSetting($key) ?? 1)->first();
    if (! $group) {
        return Post::whereRaw('1 = 0');
    }

    return $group->posts()->limit($limit);
}

function getCategorySubCatsBySetting($key, $limit = 10, $order = 'id', $dir = 'DESC')
{
    $category = Category::where('id', getSetting($key) ?? 1)->first();
    if (! $category) {
        return collect();
    }

    return $category->children()->orderBy($order, $dir)->limit($limit)->get();
}

function success($data = null, $message = null, $meta = [], $og = [], $twitter = [], $canonical_url = null, $jsonLd = null): JsonResponse
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

function errors($errors, $status = 422, $message = null, $data = null): JsonResponse
{
    return response()->json([
        'OK' => false,
        'errors' => $errors,
        'message' => $message,
        'data' => $data,
    ], $status);
}

function readable($text): string
{
    return ucfirst(trim(str_replace(['-', '_', '.'], ' ', (string) $text)));
}

function homeUrl(): string
{
    return fixUrlLang(route('client.welcome'));
}

function postsUrl(): string
{
    return fixUrlLang(route('client.posts'));
}

function productsUrl(): string
{
    return fixUrlLang(route('client.products'));
}

function clipsUrl(): string
{
    return fixUrlLang(route('client.clips'));
}

function gallariesUrl(): string
{
    return fixUrlLang(route('client.galleries'));
}

function attachmentsUrl(): string
{
    return fixUrlLang(route('client.attachments'));
}

function tagUrl($slug): string
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

function getCartData(): array
{
    return app(CartStorageService::class)->getCartData();
}

function cardItems(): array
{
    return app(CartStorageService::class)->getCardItems();
}

function cardCount(): int
{
    return app(CartStorageService::class)->getCardCount();
}

function transports()
{
    return TransportCollection::collection(Transport::all());
}

function defTrannsport()
{
    return Transport::where('is_default', 1)->first() ?? Transport::first();
}

function vueTranslate($array)
{
    return json_encode($array);
}

function markUpBreadcrumbList($items): string
{
    return app(BreadcrumbService::class)->markupBreadcrumbList((array) $items);
}

function fixUrlLang($url)
{
    if (config('app.xlang.active') && app()->getLocale() != config('app.xlang.main')) {
        $welcome = route('client.welcome');

        return str_replace($welcome, $welcome.'/'.app()->getLocale(), $url);
    }

    return $url;
}

function sendingSMS($text, $number, $args = []): bool
{
    return app(SmsService::class)->send((string) $text, (string) $number, (array) $args);
}

function generateTOC($html): array
{
    return app(TableOfContentsService::class)->generate((string) $html);
}

function generateHeadingID($text, $counter): string
{
    return app(TableOfContentsService::class)->generateHeadingId((string) $text, (int) $counter);
}

function buildTOC($items): string
{
    return app(TableOfContentsService::class)->build((array) $items);
}

function detectRateCustomer($type, $id, $evaluation)
{
    if (! auth('customer')->check()) {
        return 0;
    }
    $rate = Rate::where('rater_id', auth('customer')->id())
        ->where('rater_type', Customer::class)
        ->where('rateable_type', $type)
        ->where('rateable_id', $id)
        ->where('evaluation_id', $evaluation);

    if ($rate->count() == 0) {
        return 0;
    }

    return $rate->first()->rate;
}

function cacheNumber()
{
    return getSetting('cache_number');
}

function getMainCategory($limit = 4, $orderBy = 'sort', $asc = 'ASC')
{
    return Category::whereNull('parent_id')->where('hide', 0)->limit($limit)->orderBy($orderBy, $asc)->get();
}

function getSubGroupSetting($key, $limit = 10, $order = 'id', $dir = 'DESC')
{
    return Group::where('id', getSetting($key) ?? 1)->first()
        ?->children()->orderBy($order, $dir)->limit($limit)->get() ?? collect();
}

function CalcPrice($gold, $gr, $fee, ?float $profitRate = null, ?float $taxRate = null): int
{
    return app(ProductPriceCalculator::class)->calculateFromParts(
        $gold,
        (float) $gr,
        (float) $fee,
        $profitRate ?? 0.07,
        $taxRate ?? (float) config('app.xshop.vat', 0.09),
        0,
    );
}

function getCategoriesSet($key, $limit = 4, $orderBy = 'sort', $asc = 'ASC')
{
    $val = getSetting($key);
    $ids = is_string($val) ? (json_decode($val, true) ?: []) : (is_array($val) ? $val : []);
    if (empty($ids)) {
        return collect();
    }

    return Category::whereIn('id', $ids)->where('hide', 0)->limit($limit)->orderBy($orderBy, $asc)->get();
}

function getGroupsSet($key, $limit = 4, $orderBy = 'sort', $asc = 'ASC')
{
    $val = getSetting($key);
    $ids = is_string($val) ? (json_decode($val, true) ?: []) : (is_array($val) ? $val : []);
    if (empty($ids)) {
        return collect();
    }

    return Group::whereIn('id', $ids)->where('hide', 0)->limit($limit)->orderBy($orderBy, $asc)->get();
}

function getWtfFooterCategories()
{
    $categories = getCategoriesSet('index_WTFFooter_categories');
    if ($categories->isNotEmpty() && $categories->every(fn ($c) => ! empty($c->webUrl()) && $c->webUrl() !== '#')) {
        return $categories;
    }

    return collect([
        (object) [
            'name' => __('Women\'s Gold'),
            'url' => route('client.products', ['metal' => 'gold', 'target_group' => 'women']),
            'img' => Storage::url('categories/1741185465-زنانه.svg'),
            'has_balloon' => false,
        ],
        (object) [
            'name' => __('Men\'s Gold'),
            'url' => route('client.products', ['metal' => 'gold', 'target_group' => 'men']),
            'img' => Storage::url('categories/1741185578-مردانه.svg'),
            'has_balloon' => false,
        ],
        (object) [
            'name' => __('Children\'s Gold'),
            'url' => route('client.products', ['metal' => 'gold', 'target_group' => 'children']),
            'img' => Storage::url('categories/1741185704-بچگانه.svg'),
            'has_balloon' => false,
        ],
        (object) [
            'name' => __('Gift Gold'),
            'url' => route('client.products', ['metal' => 'gold']),
            'img' => Storage::url('categories/1741370193-هدیه طلا.jpg'),
            'has_balloon' => true,
        ],
    ]);
}
