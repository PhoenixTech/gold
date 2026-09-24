<?php

namespace App\Http\Controllers;

use App\Contracts\Payment;
use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Requests\ContactSubmitRequest;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Clip;
use App\Models\Comment;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Gallery;
use App\Models\Group;
use App\Models\Invoice;
use App\Models\Menu;
use App\Models\Post;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Rate;
use App\Models\User;
use App\Services\ProductPriceCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Plank\Metable\Meta;
use Spatie\Tags\Tag;

class ClientController extends Controller
{
    public function __construct()
    {

        $this->middleware(function ($request, $next) {

            //            if (!auth()->check()){
            //                abort(403);
            //            }
            if ($request->attributes->get('set_lang') != true) {
                app()->setLocale(config('app.locale'));
                \Session::remove('locate');
            } elseif (\Session::has('locate')) {
                app()->setLocale(\Session::get('locate'));
            }

            return $next($request);
        });

    }

    public $paginate = 12;

    /**
     * Build the Gold and Silver category tabs for homepage.
     */
    protected function getHomeCategoryTabs()
    {
        $allCategories = Category::where('hide', 0)->orderBy('sort')->get();

        $goldTab = (object) [
            'id' => 'gold',
            'name' => __('Gold'),
            'metal' => 'gold',
            'bg_color' => '#caa867',
            'color' => '#111111',
            'children' => $allCategories,
        ];

        $silverTab = (object) [
            'id' => 'silver',
            'name' => __('Silver'),
            'metal' => 'silver',
            'bg_color' => '#cccccc',
            'color' => '#111111',
            'children' => $allCategories,
        ];

        return collect([$goldTab, $silverTab]);
    }

    protected function getWtfFooterCategories()
    {
        return getWtfFooterCategories();
    }

    public function welcome()
    {
        $title = config('app.name');
        $subtitle = getSetting('subtitle');

        $mainCategories = getCategoriesSet('index_WTFIndex_categories');
        if ($mainCategories->isEmpty() || $mainCategories->every(fn ($c) => $c->children->isEmpty())) {
            $mainCategories = $this->getHomeCategoryTabs();
        }

        $latestProducts = Product::where('status', 1)
            ->with(['category', 'availableQuantities', 'activeDiscounts', 'media'])
            ->orderByDesc('id')
            ->take(8)
            ->get();

        $latestPosts = Post::where('status', 1)
            ->with('mainGroup')
            ->orderByDesc('id')
            ->take(4)
            ->get();

        $footerCategories = $this->getWtfFooterCategories();

        $zarMenuItems = collect(getMenuBySettingItems('index_ZarMenu_menu'));
        if ($zarMenuItems->isEmpty()) {
            $menu = Menu::first();
            $zarMenuItems = ($menu && $menu->items) ? collect($menu->items) : collect();
        }

        $goldPrice = getSetting('gold');

        $introText = getSetting('index_Natalia2Categories_text') ?: getSetting('about');
        $newsText = getSetting('index_NeginNews_text');

        return view('client.home', compact('title', 'subtitle', 'mainCategories', 'footerCategories', 'zarMenuItems', 'goldPrice', 'latestProducts', 'latestPosts', 'introText', 'newsText'));
    }

    public function homeV1()
    {
        $title = config('app.name');
        $subtitle = getSetting('subtitle');

        $mainCategories = getCategoriesSet('index_WTFIndex_categories');
        if ($mainCategories->isEmpty() || $mainCategories->every(fn ($c) => $c->children->isEmpty())) {
            $mainCategories = $this->getHomeCategoryTabs();
        }

        $latestProducts = Product::where('status', 1)
            ->with(['category', 'availableQuantities', 'activeDiscounts', 'media'])
            ->orderByDesc('id')
            ->take(8)
            ->get();

        $latestPosts = Post::where('status', 1)
            ->with('mainGroup')
            ->orderByDesc('id')
            ->take(4)
            ->get();

        $introText = getSetting('index_Natalia2Categories_text') ?: getSetting('about');
        $newsText = getSetting('index_NeginNews_text');

        return view('client.homev1', compact('title', 'subtitle', 'mainCategories', 'latestProducts', 'latestPosts', 'introText', 'newsText'));
    }

    public function oldHome()
    {
        $title = config('app.name');
        $subtitle = getSetting('subtitle');

        $mainCategories = getCategoriesSet('index_WTFIndex_categories');
        if ($mainCategories->isEmpty() || $mainCategories->every(fn ($c) => $c->children->isEmpty())) {
            $mainCategories = $this->getHomeCategoryTabs();
        }

        $footerCategories = $this->getWtfFooterCategories();

        $zarMenuItems = collect(getMenuBySettingItems('index_ZarMenu_menu'));
        if ($zarMenuItems->isEmpty()) {
            $menu = Menu::first();
            $zarMenuItems = ($menu && $menu->items) ? collect($menu->items) : collect();
        }

        $nataliaText = getSetting('index_Natalia2Categories_text') ?: getSetting('about');
        $neginTitle = getSetting('index_NeginNews_title');
        $neginText = getSetting('index_NeginNews_text');
        $goldPrice = getSetting('gold');
        $socials = getSettingsGroup('social_') ?: [];

        return view('client.old', compact(
            'title',
            'subtitle',
            'mainCategories',
            'footerCategories',
            'zarMenuItems',
            'nataliaText',
            'neginTitle',
            'neginText',
            'goldPrice',
            'socials'
        ));
    }

    public function post($slug)
    {

        $post = Post::where('slug', $slug)->with('mainGroup')->firstOrFail();

        if ($post->status == 0 && ! auth()->check()) {
            return abort(403);
        }
        $area = 'post';
        $title = $post->title;
        $subtitle = $post->subtitle;
        $breadcrumb = [
            __('Posts') => postsUrl(),
            $post->mainGroup->name => $post->mainGroup->webUrl(),
            $post->title => null,
        ];

        return view('client.posts.show', compact('post', 'title', 'subtitle', 'breadcrumb'));
    }

    public function clip($slug)
    {

        $clip = Clip::where('slug', $slug)->firstOrFail();

        if ($clip->status == 0 && ! auth()->check()) {
            return abort(403);
        }
        $title = $clip->title;
        $subtitle = '';
        $breadcrumb = [
            __('Video clips') => clipsUrl(),
            $clip->title => null,
        ];
        $model = $clip;

        return view('client.clips.show', compact('clip', 'title', 'subtitle', 'breadcrumb', 'model'));
    }

    public function gallery($slug)
    {

        $gallery = Gallery::where('slug', $slug)->firstOrFail();
        if ($gallery->status == 0 && ! auth()->check()) {
            return abort(403);
        }
        $title = $gallery->title;
        $subtitle = \Str::limit(strip_tags($gallery->description), 15);
        $gallery->increment('view');
        $breadcrumb = [
            __('Galleries') => gallariesUrl(),
            $gallery->title => null,
        ];

        return view('client.galleries.show', compact('gallery', 'title', 'subtitle', 'breadcrumb'));
    }

    public function posts()
    {
        $title = __('Posts list');
        $subtitle = '';
        $posts = Post::where('status', 1)
            ->with('mainGroup')
            ->orderByDesc('id')->paginate($this->paginate);

        return view('client.posts.index', compact('posts', 'title', 'subtitle'));
    }

    public function products(Request $request)
    {
        if ($request->filled('category')) {
            $catSlug = $request->input('category');

            return redirect()->route('client.category', array_merge(['category' => $catSlug], $request->except(['category'])), 301);
        }

        $metal = $request->filled('metal') ? strtolower($request->input('metal')) : null;
        $targetGroup = $request->filled('target_group') ? strtolower($request->input('target_group')) : null;

        $titles = [
            'gold:women' => __('Women\'s Gold'),
            'gold:men' => __('Men\'s Gold'),
            'gold:children' => __('Children\'s Gold'),
            'silver:women' => __('Women\'s Silver'),
            'silver:men' => __('Men\'s Silver'),
            'silver:children' => __('Children\'s Silver'),
            'gold:' => __('Gold products'),
            'silver:' => __('Silver products'),
            ':women' => __('Women products'),
            ':men' => __('Men products'),
            ':children' => __('Children products'),
        ];
        $title = $titles["{$metal}:{$targetGroup}"] ?? __('Products list');
        $subtitle = '';
        $products = Product::query()
            ->where('status', 1)
            ->with(['category', 'availableQuantities', 'activeDiscounts', 'media'])
            ->filterCatalog($request)
            ->paginate($this->paginate)
            ->withQueryString();

        $categories = Category::query()
            ->where('hide', 0)
            ->where(function ($q) {
                $q->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->with(['children' => function ($q) {
                $q->where('hide', 0);
            }])
            ->withCount(['products' => function ($q) {
                $q->where('status', 1);
            }])
            ->get();

        return view('client.products.index', compact('products', 'title', 'subtitle', 'categories'));
    }

    public function galleries()
    {
        $title = __('Galleries list');
        $subtitle = '';
        $galleries = Gallery::where('status', 1)
            ->orderByDesc('id')->paginate($this->paginate);

        return view('client.galleries.index', compact('galleries', 'title', 'subtitle'));
    }

    public function clips()
    {
        $title = __('Video clips list');
        $subtitle = '';
        $clips = Clip::where('status', 1)
            ->orderByDesc('id')->paginate($this->paginate);

        return view('client.clips.index', compact('clips', 'title', 'subtitle'));
    }

    public function attachments()
    {
        $title = __('Attachments list');
        $subtitle = '';
        $attachments = Attachment::where('is_fillable', 1)
            ->orderByDesc('id')->paginate($this->paginate);

        return view('client.attachments.index', compact('attachments', 'title', 'subtitle'));
    }

    public function attachment($slug)
    {

        $attachment = Attachment::where('slug', $slug)->firstOrFail();
        $title = $attachment->title;
        $subtitle = $attachment->subtitle;
        $breadcrumb = [
            __('Attachments') => attachmentsUrl(),
            $attachment->title => null,
        ];
        $model = $attachment;

        return view('client.attachments.show', compact('attachment', 'title', 'subtitle', 'breadcrumb', 'model'));
    }

    public function tag($slug)
    {

        $tag = Tag::where('slug->'.config('app.locale'), 'like', $slug)->first();
        $posts = Post::withAnyTags([$tag])->where('status', 1)->paginate(100);
        $products = Product::withAnyTags([$tag])->where('status', 1)->paginate(100);
        $clips = Clip::withAnyTags([$tag])->where('status', 1)->paginate(100);
        $title = __('Tag').': '.$tag->name;
        $subtitle = '';

        return view('client.tag', compact('tag', 'posts', 'products', 'clips', 'title', 'subtitle'));
    }

    public function submitComment(Request $request)
    {
        $request->validate([
            'commentable_type' => ['required', 'string', 'min:5'],
            'commentable_id' => ['required', 'integer'],
            'message' => ['required', 'string', 'min:5'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $comment = new Comment;
        if (! auth('web')->check() && ! auth('customer')->check()) {
            $request->validate([
                'name' => ['required', 'string', 'min:2'],
                'email' => ['required', 'email'],
            ]);
            $comment->name = $request->name;
            $comment->email = $request->email;
            $comment->status = 0;
        } else {
            if (auth('customer')->check()) {
                $comment->commentator_type = Customer::class;
                $comment->commentator_id = auth('customer')->id();
                $comment->status = 0;
            } elseif (auth('web')->check()) {
                $comment->commentator_type = User::class;
                $comment->commentator_id = auth('web')->id();
                $comment->status = 1;
            }
        }

        if ($request->input('parent_id') != '') {
            $comment->parent_id = $request->input('parent_id', null);
        }
        $comment->body = $request->input('message');
        $comment->commentable_type = $request->input('commentable_type');
        $comment->commentable_id = $request->input('commentable_id');
        $comment->ip = request()->ip();
        $comment->save();

        if ($request->ajax() || $request->wantsJson()) {
            return success($comment, __('Your comment has been submitted'));
        }

        return redirect()->back()->with(['message' => __('Your comment has been submitted')]);
    }

    public function search(Request $request)
    {
        $q = trim($request->input('q'));
        if (mb_strlen($q) < 3) {
            return abort(403, __('Search word is too short'));
        }
        $q = '%'.$q.'%';
        $posts = Post::where('status', 1)->where(function ($query) use ($q) {
            $query->where('title', 'LIKE', $q)
                ->orWhere('subtitle', 'LIKE', $q)
                ->orWhere('body', 'LIKE', $q);
        })->paginate(100);
        $products = Product::where('status', 1)->where(function ($query) use ($q) {
            $query->where('name', 'LIKE', $q)
                ->orWhere('excerpt', 'LIKE', $q)
                ->orWhere('description', 'LIKE', $q);
        })->paginate(100);
        $clips = Clip::where('status', 1)->where(function ($query) use ($q) {
            $query->where('title', 'LIKE', $q)
                ->orWhere('body', 'LIKE', $q);
        })->paginate(100);
        $title = __('Search for').': '.$request->input('q');
        $subtitle = '';
        $noIndex = true;

        return view('client.tag', compact('posts', 'products', 'clips', 'title', 'subtitle', 'noIndex'));
    }

    public function group($slug)
    {

        $group = Group::where('slug', $slug)->with('parent')->firstOrFail();
        $area = 'group';
        $title = $group->name;
        $subtitle = $group->subtitle;
        $posts = $group->posts()
            ->where('status', 1)
            ->with('mainGroup')
            ->orderByDesc('id')
            ->paginate($this->paginate);

        if ($group->parent_id == null) {
            $breadcrumb = [
                __('Posts') => postsUrl(),
                $group->name => null,
            ];
        } else {
            $breadcrumb = [
                __('Posts') => postsUrl(),
                $group->parent->name => $group->parent->webUrl(),
                $group->name => null,
            ];

        }

        return view('client.posts.group', compact('posts', 'title', 'subtitle', 'group', 'breadcrumb'));
    }

    public function product($slug)
    {

        $product = Product::where('slug', $slug)
            ->with(['category.parent', 'media', 'availableQuantities', 'activeDiscounts'])
            ->firstOrFail();
        if ($product->status == 0 && ! auth()->check()) {
            return abort(403);
        }
        $title = $product->name;
        $subtitle = $product->excerpt; // WIP SEO
        $breadcrumb = [
            __('Products') => productsUrl(),
            $product->category->name => $product->category->webUrl(),
        ];
        if ($product->category->parent_id != null) {
            $breadcrumb[$product->category->parent->name] = $product->category->parent->webUrl();
        }
        $breadcrumb[$product->name] = null;

        return view('client.products.show', compact('product', 'title', 'subtitle', 'breadcrumb'));
    }

    public function category($slug, Request $request)
    {
        $legacyRedirects = [
            'women-gold' => ['metal' => 'gold', 'target_group' => 'women'],
            'men-gold' => ['metal' => 'gold', 'target_group' => 'men'],
            'child-gold' => ['metal' => 'gold', 'target_group' => 'children'],
            'children-gold' => ['metal' => 'gold', 'target_group' => 'children'],
            'women-silver' => ['metal' => 'silver', 'target_group' => 'women'],
            'men-silver' => ['metal' => 'silver', 'target_group' => 'men'],
            'child-silver' => ['metal' => 'silver', 'target_group' => 'children'],
            'children-silver' => ['metal' => 'silver', 'target_group' => 'children'],
            'gift-gold-or-silver' => ['metal' => 'gold'],
            'gift-gold' => ['metal' => 'gold'],
        ];

        if (isset($legacyRedirects[$slug])) {
            return redirect()->route('client.products', array_merge($legacyRedirects[$slug], $request->query()), 301);
        }

        $category = Category::where('slug', $slug)->with(['parent', 'children'])->firstOrFail();
        $subtitle = $category->subtitle;

        $metal = $request->filled('metal') ? strtolower($request->input('metal')) : null;
        $targetGroup = $request->filled('target_group') ? strtolower($request->input('target_group')) : null;
        $metalLabels = ['gold' => __('Gold'), 'silver' => __('Silver')];
        $tgLabels = ['women' => __('Women\'s'), 'men' => __('Men\'s'), 'children' => __('Children\'s'), 'unisex' => __('Unisex')];
        $title = implode(' ', array_filter([$category->name, $metalLabels[$metal] ?? null, $tgLabels[$targetGroup] ?? null]));

        $query = $category->products()
            ->where('status', 1)
            ->with(['category', 'availableQuantities', 'activeDiscounts', 'media'])
            ->filterCatalog($request);

        if ($request->has('meta')) {
            foreach ($category->props()->where('searchable', 1)->get() as $prop) {
                if (isset($request->input('meta')[$prop->name]) && $request->input('meta')[$prop->name] != '' && $request->input('meta')[$prop->name] != '[]') {
                    switch ($prop->type) {
                        case 'checkbox':
                            if ($prop->priceable) {
                                $id = Quantity::where('count', '>', 0)
                                    ->where('data', 'LIKE', '%"'.$prop->name.'":%')
                                    ->pluck('product_id')->toArray();
                                $query->whereIn('id', $id);
                            } else {

                                $query->whereHasMeta($prop->name);
                            }
                            break;
                        case 'number':
                        case 'select':
                        case 'color':
                            if ($prop->priceable) {
                                $id = Quantity::where('count', '>', 0)
                                    ->where('data', 'LIKE', '%"'.$prop->name.'":"'.$request->meta[$prop->name].'"%')
                                    ->pluck('product_id')->toArray();

                                $id = array_merge($id, $query->whereMeta($prop->name, $request->input('meta')[$prop->name])->pluck('id')->toArray());
                                $id = array_unique($id);
                                $query->whereIn('id', $id);
                            } else {
                                $query->whereMeta($prop->name, $request->input('meta')[$prop->name]);
                            }
                            break;
                        case 'text':
                            $query->whereMeta($prop->name, 'LIKE', '%'.$request->input('meta')[$prop->name].'%');
                            break;
                        case 'multi':
                        case 'singlemulti':
                            if ($prop->priceable) {
                                $q = Quantity::where('count', '>', 0);
                                $metas = json_decode($request->meta[$prop->name], true);
                                $q->where(function ($query) use ($metas) {
                                    foreach ($metas as $meta) {
                                        $query->orWhere('data', 'LIKE', '%'.$meta.'%');
                                    }
                                });
                                $query->whereIn('id', $q->pluck('product_id')->toArray());
                            } else {
                                $q = Meta::where('key', $prop->name)->where('metable_type', Product::class);
                                $metas = json_decode($request->meta[$prop->name], true);
                                $q->where(function ($query) use ($metas) {
                                    foreach ($metas as $meta) {
                                        $query->orWhere('value', 'LIKE', '%'.$meta.'%');
                                    }
                                });

                                $query->whereIn('id', $q->pluck('metable_id')->toArray());
                            }

                    }
                }
            }
        }

        $products = $query->paginate($this->paginate)->withQueryString();

        $breadcrumb = [__('Products') => productsUrl()];
        if ($category->parent) {
            $breadcrumb[$category->parent->name] = $category->parent->webUrl();
        }
        if ($title !== $category->name) {
            $breadcrumb[$category->name] = $category->webUrl();
        }
        $breadcrumb[$title] = null;

        return view('client.categories.show', compact('products', 'title', 'subtitle', 'category', 'breadcrumb'));
    }

    public function attachDl($slug)
    {
        $attachment = Attachment::where('slug', $slug)->orWhere('id', $slug)->firstOrFail();
        if (! $attachment->file) {
            abort(404);
        }

        $file = storage_path('app/public/attachments/'.$attachment->file);
        if (file_exists($file)) {
            $attachment->increment('downloads');

            return response()->download($file);
        }

        abort(404);
    }

    public function compare()
    {
        $title = __('Compare products');
        $subtitle = '';
        $ids = json_decode(\Cookie::get('compares'), true);
        $products = Product::whereIn('id', $ids)->where('status', 1)->get();

        return view('client.compare.index', compact('products', 'title', 'subtitle'));
    }

    public function contact()
    {
        $title = __('Contact us');
        $subtitle = '';

        return view('client.contact.index', compact('title', 'subtitle'));
    }

    public function sendContact(ContactSubmitRequest $request)
    {
        $con = new Contact;
        $con->name = $request->full_name;
        $con->email = $request->email;
        $con->mobile = $request->phone;
        $con->subject = $request->subject;
        $con->body = $request->bodya;
        $con->save();

        return redirect()->back()->with(['message' => __('Your message has been successfully sent.')]);
    }

    public function signOut()
    {
        return app(CustomerAuthController::class)->signOut();
    }

    public function signIn(Request $request)
    {
        return app(CustomerAuthController::class)->signIn($request);
    }

    public function signUp(Request $request)
    {
        return app(CustomerAuthController::class)->signUp($request);
    }

    public function signUpNow(Request $request)
    {
        return app(CustomerAuthController::class)->signUpNow($request);
    }

    public function singInDo(Request $request)
    {
        return app(CustomerAuthController::class)->singInDo($request);
    }

    public function sendSms(Request $request)
    {
        return app(CustomerAuthController::class)->sendSms($request);
    }

    public function checkAuth(Request $request)
    {
        return app(CustomerAuthController::class)->checkAuth($request);
    }

    public function sitemap()
    {
        return app(SitemapController::class)->index();
    }

    public function sitemapGroupCategory()
    {
        return app(SitemapController::class)->categories();
    }

    public function sitemapPosts()
    {
        return app(SitemapController::class)->posts();
    }

    public function sitemapProducts()
    {
        return app(SitemapController::class)->products();
    }

    public function sitemapClips()
    {
        return app(SitemapController::class)->clips();
    }

    public function sitemapGalleries()
    {
        return app(SitemapController::class)->galleries();
    }

    public function sitemapAttachments()
    {
        return app(SitemapController::class)->attachments();
    }

    public function lang(Request $request)
    {

        $uri = '/'.$request->path();
        // Iterate through all the defined routes
        $r = null;
        $n = '';
        foreach (Route::getRoutes() as $route) {

            // Just check client routes
            if (substr($route->getName(), 0, 7) != 'client.' || $route->getName() == 'client.welcome') {
                continue;
            }

            $uri2 = str_replace('/', '\\/', $route->uri());
            $uri2 = preg_replace('/\{[a-z]*\}/m', '.*', $uri2);
            // Check if the route matches the given URI
            if (preg_match('/'.$uri2.'/', $uri)) {
                $r = $route->action['controller'];
                $n = $route->uri();
                break;
            }
        }
        if (count(explode('@', $r)) == 1) {
            return abort(404);
        }
        $method = explode('@', $r)[1];
        $segments = $request->segments();
        $routes = explode('/', $n);
        $args = [];
        foreach ($routes as $i => $route) {
            if ($route[0] == '{') {
                $args[] = $segments[$i + 1];
            }
        }
        $args[] = $request;

        return $this->$method(...$args);
    }

    public function langIndex()
    {
        return $this->welcome();
    }

    public function pay($hash)
    {

        $invoice = Invoice::where('hash', $hash)->first();
        //        dd($invoice->created_at->timestamp , (time() - 3600));

        if (! in_array($invoice->status, ['PENDING', 'CANCELED', 'FAILED']) || $invoice->created_at->timestamp < (time() - 3600)) {
            return redirect()->back()->withErrors(__('This payment method is not available.'));
        }

        if (in_array($invoice->status, ['FAILED', 'CANCELED'], true)) {
            if (! $invoice->canRetryOnlinePayment()) {
                return redirect()->back()->withErrors(__('Some items in this order are no longer available in stock.'));
            }

            foreach ($invoice->orders as $order) {
                if ($order->quantity_id) {
                    $quantity = Quantity::query()->whereKey($order->quantity_id)->lockForUpdate()->first();
                    if ($quantity === null || ! $quantity->isAvailable()) {
                        return redirect()->back()->withErrors(__('Some items in this order are no longer available in stock.'));
                    }
                    $quantity->markSold();
                    if ($quantity->product !== null) {
                        app(ProductPriceCalculator::class)->syncProductAggregates($quantity->product);
                    }
                }
            }

            $invoice->status = 'PENDING';
            $invoice->save();
        }
        $activeGateway = config('xshop.payment.active_gateway');
        /** @var Payment $gateway */
        $gateway = app($activeGateway.'-gateway');
        logger()->info('pay controller', ['active_gateway' => $activeGateway, 'invoice' => $invoice->toArray()]);

        if ($invoice->isCompleted()) {
            return redirect()->back()->with('message', __('Invoice payed.'));
        }

        $callbackUrl = route('pay.check', ['invoice_hash' => $invoice->hash, 'gateway' => $gateway->getName()]);
        $payment = null;
        try {
            $response = $gateway->request((($invoice->total_price - $invoice->credit_price) * config('app.currency.factor')), $callbackUrl);
            $payment = $invoice->storePaymentRequest($response['order_id'], (($invoice->total_price - $invoice->credit_price) * config('app.currency.factor')), $response['token'] ?? null, null, $gateway->getName());
            session(['payment_id' => $payment->id]);
            \Session::save();

            return $gateway->goToBank();
        } catch (\Throwable $exception) {
            $invoice->status = 'FAILED';
            $invoice->save();
            $invoice->releaseReservedStock();
            \Log::error('Payment REQUEST exception: '.$exception->getMessage());
            \Log::warning($exception->getTraceAsString());
            $result = false;
            $message = __('error in payment. contact admin.');

            return redirect()->back()->withErrors($message);
        }
    }

    public function rate(Request $request)
    {
        $request->validate([
            'rate.*' => ['required', 'integer'],
            'rateable_id' => ['required', 'integer'],
            'rateable_type' => ['required', 'string'],
        ]);

        $changed = false;
        foreach ($request->rate as $k => $rt) {

            $r = Rate::where('rateable_type', $request->rateable_type)
                ->where('rateable_id', $request->rateable_id)
                ->where('rater_type', Customer::class)
                ->where('rater_id', auth('customer')->id())
                ->where('evaluation_id', $k);
            if ($r->count() != 0) {
                $rate = $r->first();
                $changed = true;
            } else {
                $rate = new Rate;
            }
            if ($rt > 0 && $rt <= 5) {
                $rate->rater_type = Customer::class;
                $rate->rater_id = auth('customer')->id();
                $rate->rateable_type = $request->rateable_type;
                $rate->rateable_id = $request->rateable_id;
                $rate->evaluation_id = $k;
                $rate->rate = $rt;
                $rate->save();
            }

        }
        if ($changed) {
            return [
                'OK' => true,
                'message' => __('Your rate updated'),
            ];
        }

        return [
            'OK' => true,
            'message' => __('Your rate registered'),
        ];
    }

    public function postRss()
    {
        return app(RssFeedController::class)->posts();
    }

    public function productRss()
    {
        return app(RssFeedController::class)->products();
    }

    public function underConstruction()
    {
        $title = __('Under Construction').' - '.config('app.name');

        return view('client.under-construction', compact('title'));
    }

    protected function wantsJsonResponse(Request $request): bool
    {
        return $request->expectsJson()
            || $request->ajax()
            || $request->boolean('embed')
            || str_contains(strtolower((string) $request->header('Accept', '')), 'json')
            || str_contains(strtolower((string) $request->header('Content-Type', '')), 'json');
    }
}
