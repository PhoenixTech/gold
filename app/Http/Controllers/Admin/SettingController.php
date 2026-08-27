<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingSaveRequest;
use App\Models\Category;
use App\Models\Group;
use App\Models\Menu;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Spatie\Image\Image;

class SettingController extends Controller
{
    /**
     * Settings that belong to the "Gold & silver shop" tab even though they
     * live in the General section of the database.
     */
    private const GOLD_SHOP_KEYS = ['min', 'offline_payment_hours', 'cart_quote_minutes'];

    /**
     * Developer-only keys that are managed elsewhere and must not be shown
     * on the settings page (market rates are automatic, bank accounts have
     * their own management page).
     */
    private const HIDDEN_KEYS = [
        'gold', 'gold24', 'silver', 'dollar',
        'bank_card_number', 'bank_sheba', 'bank_account_name',
    ];

    /**
     * Display a listing of the resource, grouped into clear tabs.
     */
    public function index()
    {
        $settings = Setting::where('active', true)
            ->orderBy('section')
            ->orderBy('id')
            ->get()
            ->reject(fn (Setting $setting) => in_array($setting->key, self::HIDDEN_KEYS));

        // Explicit tab layout: the ordered groups below are rendered as tabs;
        // every setting that is not picked by a group keeps its own section tab
        // so developer-created sections (or future keys) never get lost.
        $generalKeys = ['subtitle', 'under', 'email', 'tel', 'copyright',
            'social_twitter', 'social_facebook', 'social_instagram',
            'social_linkedin', 'social_youtube', 'social_telegram', 'social_whatsapp'];
        $themeKeys = ['css'];

        $tabbedKeys = array_merge(self::GOLD_SHOP_KEYS, $generalKeys, $themeKeys);

        $tabs = [
            [
                'id' => 'goldshop',
                'label' => __('Gold & silver shop'),
                'icon' => 'ri-copper-coin-line',
                'intro' => __('These options control pricing & payments for gold/silver products. Market prices (18K, 24K, silver, dollar) are updated automatically.'),
                'settings' => $settings->whereIn('key', self::GOLD_SHOP_KEYS)->values(),
            ],
            [
                'id' => 'general',
                'label' => __('General site settings'),
                'icon' => 'ri-settings-3-line',
                'intro' => null,
                'settings' => $settings->filter(function (Setting $setting) use ($generalKeys) {
                    return $setting->section === 'General'
                        && ! in_array($setting->key, self::GOLD_SHOP_KEYS)
                        && in_array($setting->key, $generalKeys);
                })->values(),
            ],
            [
                'id' => 'seo',
                'label' => __('SEO'),
                'icon' => 'ri-search-eye-line',
                'intro' => null,
                'settings' => $settings->where('section', 'SEO')->values(),
            ],
            [
                'id' => 'media',
                'label' => __('Media & images'),
                'icon' => 'ri-image-2-line',
                'intro' => null,
                'settings' => $settings->where('section', 'Media')->values(),
            ],
            [
                'id' => 'template',
                'label' => __('Theme & appearance'),
                'icon' => 'ri-palette-line',
                'intro' => null,
                'settings' => $settings->filter(fn (Setting $setting) => $setting->section === 'theme'
                    || ($setting->section === 'General' && $setting->key === 'css'))->values(),
            ],
            [
                'id' => 'sms',
                'label' => __('SMS messages'),
                'icon' => 'ri-message-2-line',
                'intro' => null,
                'settings' => $settings->where('section', 'SMS')->values(),
            ],
        ];

        // Anything left over (unknown sections or unmapped General keys such as
        // freshly created developer settings) gets its own "Other" tab so it
        // stays editable on this page.
        $rest = $settings->reject(fn (Setting $setting) => in_array($setting->key, $tabbedKeys)
            || in_array($setting->section, ['SEO', 'Media', 'SMS'])
            || $setting->section === 'theme');
        if ($rest->isNotEmpty()) {
            $tabs[] = [
                'id' => 'other',
                'label' => __('Other settings'),
                'icon' => 'ri-more-line',
                'intro' => null,
                'settings' => $rest->values(),
            ];
        }

        // Drop empty tabs (e.g. a fresh install without SMS templates).
        $tabs = array_values(array_filter($tabs, fn ($tab) => $tab['settings']->isNotEmpty()));

        $cats = Category::all(['id', 'name'])->toArray();
        $menus = Menu::all(['id', 'name']);
        $groups = Group::all(['id', 'name'])->toArray();
        $catz = array_merge([['id' => 0, 'name' => __('All')]], $cats);
        $groupz = array_merge([['id' => 0, 'name' => __('All')]], $groups);

        return view('admin.commons.setting',
            compact('tabs', 'cats', 'groups', 'menus', 'catz', 'groupz'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SettingSaveRequest $request)
    {
        //
        $set = new Setting;
        $set->title = $request->title;
        $set->key = $request->key;
        $set->section = $request->section;
        $set->type = $request->type;
        $set->size = $request->size;
        $set->save();
        logAdmin(__METHOD__, __CLASS__, $set->id);

        return redirect()->back()->with(['message' => __('Setting added to website')]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Setting $setting)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Setting $setting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $all = $request->all();
        foreach ($all as $key => $val) {
            $set = Setting::where('key', $key)->first();
            if ($set == null) {
                continue;
            }
            if ($set->type == 'PRODUCT_QUERY' || $set->type == 'POST_QUERY') {
                $set->value = implode(',', $val);
                $set->raw = implode(',', $val);
                $set->save();
            } elseif ($set != null && ! $request->hasFile($key)) {

                $set->value = validateSettingRequest($set, $val);
                $set->raw = validateSettingRequest($set, $val);
                // need to test
                if (config('app.xlang.active') && config('app.xlang.main') != 'en' && (
                    $set->type != 'TEXT' && $set->type != 'EDITOR' && $set->type != 'LONGTEXT')) {
                    $set->setTranslation('value', 'en', $val);
                }
                $set->save();
            }
        }
        $files = $request->allFiles();
        if (isset($files['file'])) {
            $format = getSetting('optimize');
            foreach ($files['file'] as $index => $file) {
                if (($file->guessExtension() == 'jpg' || $file->guessExtension() == 'png') && ($index != 'site_image')) {

                    $i = Image::load($file->getRealPath())
                        ->optimize()
                        ->format($format);

                    $file->move(public_path('upload/images/'), str_replace('_', '.', $index)); // store('/images/'.,['disk' => 'public']);
                    $optimizedFile = public_path('upload/images/optimized-').str_replace('_', '.', $index);
                    $optimizedFile = str_replace(['jpg', 'png', 'gif'], 'webp', $optimizedFile);
                    $i->save($optimizedFile);
                } elseif ($file->guessExtension() == 'mp4' || $file->guessExtension() == 'mp3') {
                    $file->move(public_path('upload/media/'), str_replace('_', '.', $index)); // store('/images/'.,['disk' => 'public']);
                } elseif ($file->guessExtension() == 'webp') {
                    $file->move(public_path('upload/images/'), str_replace('_', '.', $index)); // store('/images/'.,['disk' => 'public']);
                } else {

                    $file->move(public_path('upload/file/'), str_replace('_', '.', $index)); // store('/images/'.,['disk' => 'public']);
                }
            }
        }

        if ($request->has('build')) {
            Artisan::call('build');
        }
        logAdmin(__METHOD__, __CLASS__, null);

        return redirect()->back()->with(['message' => __('Setting of website updated')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Setting $setting)
    {
        //
    }

    public function cacheClear()
    {
        $f = Setting::where('key', 'cache_number')->first();
        $f->value += 1;
        $f->save();
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');

        return redirect()->back()->with(['message' => __('Cache cleared')]);
    }

    public function liveEdit($slug)
    {
        $settings = Setting::where('active', true)->where('key', 'LIKE', $slug.'%')
            ->orderBy('section')->get();
        $cats = Category::all(['id', 'name'])->toArray();
        $catz = array_merge([['id' => 0, 'name' => __('All')]], $cats);
        $menus = Menu::all(['id', 'name']);
        $groups = Group::all(['id', 'name'])->toArray();
        $groupz = array_merge([['id' => 0, 'name' => __('All')]], $groups);

        return view('admin.commons.live',
            compact('settings', 'cats', 'groups', 'menus', 'catz', 'groupz'));
    }
}
