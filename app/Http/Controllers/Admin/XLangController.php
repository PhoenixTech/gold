<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminModel;
use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\XLangSaveRequest;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\City;
use App\Models\Clip;
use App\Models\Discount;
use App\Models\Gallery;
use App\Models\Group;
use App\Models\Image;
use App\Models\Item;
use App\Models\Post;
use App\Models\Product;
use App\Models\Prop;
use App\Models\Setting;
use App\Models\State;
use App\Models\Tag;
use App\Models\Transport;
use App\Models\XLang;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Spatie\Image\Image as SpatieImage;

class XLangController extends Controller
{
    use ResolvesAdminModel;
    use RespondsWithAdmin;

    public array $allowedModels = [
        Attachment::class,
        Discount::class,
        Product::class,
        Category::class,
        Post::class,
        Group::class,
        Item::class,
        Gallery::class,
        Clip::class,
        Prop::class,
        Setting::class,
        Image::class,
        State::class,
        City::class,
        Transport::class,
        Tag::class,
    ];

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(XLang::class)
            ->columns(['name', 'tag', 'emoji', 'is_default'], ['id', 'img'])
            ->searchable([])
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-close-line'],
            ])
            ->build($request);

        return view('admin.xlangs.xlang-list', $tableData);
    }

    public function create(): View
    {
        return view('admin.xlangs.xlang-form');
    }

    public function store(XLangSaveRequest $request): JsonResponse|RedirectResponse
    {
        $xlang = new XLang;
        $this->saveLangData($xlang, $request);

        logAdmin(__METHOD__, XLang::class, $xlang->id);

        return $this->respondAfterSave($request, $xlang, __('As you wished created successfully'), 'admin.lang.edit');
    }

    public function edit(XLang|string|int $item): View
    {
        $item = $this->resolveLang($item);

        return view('admin.xlangs.xlang-form', compact('item'));
    }

    public function update(XLangSaveRequest $request, XLang|string|int $item): JsonResponse|RedirectResponse
    {
        $item = $this->resolveLang($item);
        $this->saveLangData($item, $request);

        logAdmin(__METHOD__, XLang::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.lang.edit');
    }

    public function destroy(XLang|string|int $item): RedirectResponse
    {
        $item = $this->resolveLang($item);

        logAdmin(__METHOD__, XLang::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(XLang::onlyTrashed())
            ->columns(['name', 'tag', 'emoji', 'is_default'], ['id', 'img', 'deleted_at'])
            ->searchable([])
            ->buttons([
                'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
            ])
            ->build($request);

        return view('admin.xlangs.xlang-list', $tableData);
    }

    public function restore($item): RedirectResponse
    {
        $target = XLang::withTrashed()->where('id', $item)->firstOrFail();

        logAdmin(__METHOD__, XLang::class, $target->id);
        $target->restore();

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(XLang::class, $request->input('action'), (array) $request->input('id', []));
    }

    public function translate(): View
    {
        $langs = XLang::all();

        return view('admin.xlangs.xlang-translates', compact('langs'));
    }

    public function download($tag)
    {
        $path = base_path('resources/lang/'.$tag.'.json');

        return response()->download($path, $tag.'.json');
    }

    public function upload($tag, Request $request): RedirectResponse
    {
        $path = base_path('resources/lang/'.$tag.'.json');
        if (! $request->hasFile('json')) {
            return redirect()->back();
        }

        $data = file_get_contents($request->file('json')->getRealPath());
        if (json_decode($data) === null) {
            return redirect()->back()->withErrors(__('Invalid json file!'));
        }

        file_put_contents($path, $data);

        return redirect()->back()->with(['message' => __('Translate updated')]);
    }

    public function ai($tag): RedirectResponse
    {
        $path = base_path('resources/lang/'.$tag.'.json');
        $file = file_get_contents($path);
        $url = config('app.xlang.api_url').'/json?form=en&to='.$tag;

        $client = new Client(['headers' => ['Content-Type' => 'application/json']]);
        $response = $client->post($url, ['body' => $file]);

        file_put_contents($path, $response->getBody()->getContents());

        return redirect()->back()->with(['message' => __('Translated by ai xstack service :TAG', ['TAG' => $tag])]);
    }

    public function translateModel($id, $model): View
    {
        if (! in_array($model, $this->allowedModels, true)) {
            abort(404);
        }

        $langs = XLang::where('is_default', 0)->get();
        $cls = $model;
        $model = $model::where('id', $id)->firstOrFail();
        $translates = $model->translatable;

        return view('admin.xlangs.xlang-translate-model', compact('model', 'translates', 'langs', 'cls'));
    }

    public function translateModelSave($id, $model, Request $request): RedirectResponse
    {
        if (! in_array($model, $this->allowedModels, true)) {
            abort(404);
        }

        $modelInstance = $model::where('id', $id)->firstOrFail();
        foreach ($request->input('data', []) as $lang => $items) {
            if (is_array($items)) {
                foreach ($items as $k => $item) {
                    if ($item !== null) {
                        $modelInstance->setTranslation($k, $lang, $item);
                    }
                }
            }
        }

        $modelInstance->save();

        return redirect()->back()->with(['message' => __('Translate updated')]);
    }

    public function translateModelAi($id, $model, $tag, $field): RedirectResponse
    {
        if (! in_array($model, $this->allowedModels, true)) {
            abort(404);
        }

        $modelInstance = $model::where('id', $id)->firstOrFail();
        $url = config('app.xlang.api_url').'/text?form='.config('app.xlang_main').'&to='.$tag;

        $client = new Client(['headers' => ['Content-Type' => 'application/x-www-form-urlencoded']]);
        $response = $client->post($url, [
            'form_params' => ['body' => $modelInstance->$field],
        ]);

        if ($response->getStatusCode() !== 200) {
            return redirect()->back()->withErrors(__('API error!'));
        }

        $modelInstance->setTranslation($field, $tag, $response->getBody()->getContents());
        $modelInstance->save();

        return redirect()->back()->with(['message' => __('Translate updated')]);
    }

    protected function resolveLang(XLang|string|int $item): XLang
    {
        return $this->resolveModel(XLang::class, $item);
    }

    protected function saveLangData(XLang $xlang, Request $request): void
    {
        if ($xlang->id === null && $request->input('tag') !== 'en') {
            $configPath = config_path('translator.php');
            $newLangFile = base_path('resources/lang/'.$request->input('tag').'.json');

            if (file_exists($configPath)) {
                $config = file_get_contents($configPath);
                $re = '/\'languages\' \=\> (.*)\,/m';
                if (preg_match_all($re, $config, $matches, PREG_SET_ORDER, 0)) {
                    $oldLangs = $matches[0][1];
                    $newLangs = json_encode(array_unique(array_merge((array) json_decode($oldLangs), [$request->input('tag')])));
                    $newConfig = str_replace($oldLangs, $newLangs, $config);
                    file_put_contents($configPath, $newConfig);
                }
            }

            if (! file_exists($newLangFile)) {
                file_put_contents($newLangFile, '{}');
            }
        }

        $xlang->name = $request->input('name');
        $xlang->tag = $request->input('tag');
        $xlang->rtl = $request->has('rtl');

        $xlang->is_default = $request->has('is_default');
        if ($xlang->is_default) {
            XLang::where('is_default', '1')->update(['is_default' => 0]);
        }

        if (! $request->has('emoji')) {
            $xlang->emoji = $request->input('emoji');
        } else {
            $xlang->emoji = getEmojiLanguagebyCode($xlang->tag);
        }

        if ($request->hasFile('img')) {
            $file = $request->file('img');
            $name = time().'-'.$file->getClientOriginalName();
            $file->storeAs('public/langz', $name);
            $xlang->img = $name;

            $format = strtolower((string) $file->guessExtension()) === 'png' ? 'webp' : $file->guessExtension();
            SpatieImage::load($file->getPathname())
                ->optimize()
                ->format($format)
                ->save(storage_path('app/public/langz/optimized-'.$name));
        }

        $xlang->save();

        if (file_exists(base_path('app/Console/Commands/TranslatorUpdate.php'))) {
            Artisan::call('translator:update');
        }
    }
}
