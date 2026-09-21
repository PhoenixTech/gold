<?php

use App\Http\Controllers\Admin\AddressController;
use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\AttachmentController;
use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CkeditorController;
use App\Http\Controllers\Admin\ClipController;
use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\CourierDeliveryController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\EvaluationController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\HelpController;
use App\Http\Controllers\Admin\ImageController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\OrderBoardController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PropController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\RateController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShopVisitController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\SummaryController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\TransportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\XLangController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Payment\GatewayVerifyController;
use App\Http\Controllers\PaymentReceiptController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\VisitorFormController;
use App\Http\Middleware\LangControl;
use App\Http\Middleware\VisitorCounter;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Auth::routes(['register' => false]);

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::prefix(config('app.panel.prefix'))->name('admin.')->group(
    function () {
        Route::group(
            ['middleware' => ['auth']],
            function () {

                Route::get('/', [HomeController::class, 'index'])->name('home');
                Route::get('summary', [SummaryController::class, 'index'])->name('summary.index');
                Route::get('help/{topic?}', [HelpController::class, 'show'])
                    ->name('help')
                    ->where('topic', '[a-z0-9\-]+');

                Route::get('adminlogs', [AdminLogController::class, 'index'])->name('adminlog.index');
                Route::post('adminlogs/cleanup', [AdminLogController::class, 'cleanup'])->name('adminlog.cleanup');
                Route::get('adminlogs/{user}', [AdminLogController::class, 'log'])->name('adminlog.show');
                Route::post('ckeditor/upload', [CkeditorController::class, 'upload'])->name('ckeditor.upload');
                Route::post('images/store/{gallery}', [ImageController::class, 'store'])->name('image.store');
                Route::get('images/destroy/{image}', [ImageController::class, 'destroy'])->name('image.destroy');
                Route::any('logout', [LoginController::class, 'logout'])->name('logout');
                Route::get('rates', [RateController::class, 'index'])->name('rate.index');

                Route::prefix('addresses')->name('address.')->group(
                    function () {
                        Route::get('customer/{item}', [AddressController::class, 'customer'])->name('customer');
                        Route::post('add/{item}', [AddressController::class, 'store'])->name('store');
                        Route::post('update/{item?}', [AddressController::class, 'update'])->name('update');
                        Route::get('destroy/{item?}', [AddressController::class, 'destroy'])->name('destroy');
                    });
                Route::prefix('attachments')->name('attachment.')->group(
                    function () {
                        Route::get('', [AttachmentController::class, 'index'])->name('index');
                        Route::get('create', [AttachmentController::class, 'create'])->name('create');
                        Route::post('store', [AttachmentController::class, 'store'])->name('store');
                        Route::get('show/{item}', [AttachmentController::class, 'show'])->name('show');
                        Route::get('edit/{item}', [AttachmentController::class, 'edit'])->name('edit');
                        Route::post('update/{item}', [AttachmentController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [AttachmentController::class, 'destroy'])->name('destroy');
                        Route::get('detach/{item?}', [AttachmentController::class, 'detach'])->name('detach');
                        Route::post('bulk', [AttachmentController::class, 'bulk'])->name('bulk');
                        Route::post('attaching', [AttachmentController::class, 'attaching'])->name('attaching');
                    });
                Route::prefix('categories')->name('category.')->group(
                    function () {
                        Route::get('', [CategoryController::class, 'index'])->name('index');
                        Route::get('create', [CategoryController::class, 'create'])->name('create');
                        Route::get('omg', [CategoryController::class, 'omg'])->name('omg');
                        Route::post('omg/save', [CategoryController::class, 'omgSave'])->name('omg.save');
                        Route::post('store', [CategoryController::class, 'store'])->name('store');
                        Route::get('edit/{item}', [CategoryController::class, 'edit'])->name('edit');
                        Route::get('show/{item}', [CategoryController::class, 'show'])->name('show');
                        Route::post('update/{item}', [CategoryController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [CategoryController::class, 'destroy'])->name('destroy');
                        Route::get('restore/{item}', [CategoryController::class, 'restore'])->name('restore');
                        Route::post('bulk', [CategoryController::class, 'bulk'])->name('bulk');
                        Route::get('trashed', [CategoryController::class, 'trashed'])->name('trashed');
                        Route::post('sort/save', [CategoryController::class, 'sortSave'])->name('sort-save');
                        Route::get('sort', [CategoryController::class, 'sort'])->name('sort');
                    });
                Route::prefix('clips')->name('clip.')->group(
                    function () {
                        Route::get('', [ClipController::class, 'index'])->name('index');
                        Route::get('create', [ClipController::class, 'create'])->name('create');
                        Route::post('store', [ClipController::class, 'store'])->name('store');
                        Route::get('show/{item}', [ClipController::class, 'show'])->name('show');
                        Route::get('edit/{item}', [ClipController::class, 'edit'])->name('edit');
                        Route::post('update/{item}', [ClipController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [ClipController::class, 'destroy'])->name('destroy');
                        Route::get('restore/{item}', [ClipController::class, 'restore'])->name('restore');
                        Route::post('bulk', [ClipController::class, 'bulk'])->name('bulk');
                        Route::get('trashed', [ClipController::class, 'trashed'])->name('trashed');
                    });
                Route::prefix('contacts')->name('contact.')->group(
                    function () {
                        Route::get('', [ContactController::class, 'index'])->name('index');
                        //                        Route::get('create', [\App\Http\Controllers\Admin\TicketController::class, 'create'])->name('create');
                        Route::post('store', [ContactController::class, 'store'])->name('store');
                        Route::get('show/{item}', [ContactController::class, 'show'])->name('show');
                        Route::post('reply/{item}', [ContactController::class, 'reply'])->name('reply');
                        Route::get('delete/{item}', [ContactController::class, 'destroy'])->name('destroy');
                        Route::post('bulk', [ContactController::class, 'bulk'])->name('bulk');
                    });
                Route::prefix('comments')->name('comment.')->group(
                    function () {
                        Route::get('', [CommentController::class, 'index'])->name('index');
                        Route::get('status/{item}/{status}', [CommentController::class, 'status'])->name('status');
                        Route::get('delete/{item}', [CommentController::class, 'destroy'])->name('destroy');
                        Route::get('reply/{item}', [CommentController::class, 'reply'])->name('reply');
                        Route::post('replying/{item}', [CommentController::class, 'replying'])->name('replying');
                        Route::post('bulk', [CommentController::class, 'bulk'])->name('bulk');
                    });
                Route::prefix('customers')->name('customer.')->group(
                    function () {
                        Route::get('', [App\Http\Controllers\Admin\CustomerController::class, 'index'])->name('index');
                        Route::get('create', [App\Http\Controllers\Admin\CustomerController::class, 'create'])->name('create');
                        Route::post('store', [App\Http\Controllers\Admin\CustomerController::class, 'store'])->name('store');
                        Route::get('show/{item}', [App\Http\Controllers\Admin\CustomerController::class, 'show'])->name('show');
                        Route::get('edit/{item}', [App\Http\Controllers\Admin\CustomerController::class, 'edit'])->name('edit');
                        Route::post('update/{item}', [App\Http\Controllers\Admin\CustomerController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [App\Http\Controllers\Admin\CustomerController::class, 'destroy'])->name('destroy');
                        Route::get('restore/{item}', [App\Http\Controllers\Admin\CustomerController::class, 'restore'])->name('restore');
                        Route::post('bulk', [App\Http\Controllers\Admin\CustomerController::class, 'bulk'])->name('bulk');
                        Route::get('trashed', [App\Http\Controllers\Admin\CustomerController::class, 'trashed'])->name('trashed');
                    });
                Route::prefix('discounts')->name('discount.')->group(
                    function () {
                        Route::get('', [DiscountController::class, 'index'])->name('index');
                        Route::get('create', [DiscountController::class, 'create'])->name('create');
                        Route::post('store', [DiscountController::class, 'store'])->name('store');
                        Route::get('edit/{item}', [DiscountController::class, 'edit'])->name('edit');
                        Route::post('update/{item}', [DiscountController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [DiscountController::class, 'destroy'])->name('destroy');
                        Route::get('restore/{item}', [DiscountController::class, 'restore'])->name('restore');
                        Route::post('bulk', [DiscountController::class, 'bulk'])->name('bulk');
                        Route::get('trashed', [DiscountController::class, 'trashed'])->name('trashed');
                    });
                Route::prefix('evaluations')->name('evaluation.')->group(
                    function () {
                        Route::get('', [EvaluationController::class, 'index'])->name('index');
                        Route::get('create', [EvaluationController::class, 'create'])->name('create');
                        Route::post('store', [EvaluationController::class, 'store'])->name('store');
                        Route::get('edit/{item}', [EvaluationController::class, 'edit'])->name('edit');
                        Route::post('update/{item}', [EvaluationController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [EvaluationController::class, 'destroy'])->name('destroy');
                        Route::get('restore/{item}', [EvaluationController::class, 'restore'])->name('restore');
                        Route::post('bulk', [EvaluationController::class, 'bulk'])->name('bulk');
                        Route::get('trashed', [EvaluationController::class, 'trashed'])->name('trashed');
                    });
                Route::prefix('galleries')->name('gallery.')->group(
                    function () {
                        Route::get('', [GalleryController::class, 'index'])->name('index');
                        Route::get('create', [GalleryController::class, 'create'])->name('create');
                        Route::post('store', [GalleryController::class, 'store'])->name('store');
                        Route::get('show/{item}', [GalleryController::class, 'show'])->name('show');
                        Route::post('title/update', [GalleryController::class, 'updateTitle'])->name('title');
                        Route::get('edit/{item}', [GalleryController::class, 'edit'])->name('edit');
                        Route::post('update/{item}', [GalleryController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [GalleryController::class, 'destroy'])->name('destroy');
                        Route::post('bulk', [GalleryController::class, 'bulk'])->name('bulk');
                    });
                Route::prefix('groups')->name('group.')->group(
                    function () {
                        Route::get('', [GroupController::class, 'index'])->name('index');
                        Route::get('create', [GroupController::class, 'create'])->name('create');
                        Route::post('store', [GroupController::class, 'store'])->name('store');
                        Route::get('edit/{item}', [GroupController::class, 'edit'])->name('edit');
                        Route::post('update/{item}', [GroupController::class, 'update'])->name('update');
                        Route::get('show/{item}', [GroupController::class, 'show'])->name('show');
                        Route::get('delete/{item}', [GroupController::class, 'destroy'])->name('destroy');
                        Route::get('restore/{item}', [GroupController::class, 'restore'])->name('restore');
                        Route::post('bulk', [GroupController::class, 'bulk'])->name('bulk');
                        Route::get('trashed', [GroupController::class, 'trashed'])->name('trashed');
                        Route::post('sort/save', [GroupController::class, 'sortSave'])->name('sort-save');
                        Route::get('sort', [GroupController::class, 'sort'])->name('sort');
                    });
                Route::get('order-board', [OrderBoardController::class, 'index'])->name('order-board.index');
                Route::prefix('invoices')->name('invoice.')->group(
                    function () {
                        Route::get('', [InvoiceController::class, 'index'])->name('index');
                        //                        Route::get('create', [\App\Http\Controllers\Admin\InvoiceController::class, 'create'])->name('create');
                        //                        Route::post('store', [\App\Http\Controllers\Admin\InvoiceController::class, 'store'])->name('store');
                        Route::get('edit/{item}', [InvoiceController::class, 'edit'])->name('edit');
                        Route::get('show/{item}', [InvoiceController::class, 'show'])->name('show');
                        Route::get('print/{item}', [InvoiceController::class, 'print'])->name('print');
                        Route::get('shipping-label/{item}', [InvoiceController::class, 'shippingLabel'])->name('shipping-label');
                        Route::post('update/{item}', [InvoiceController::class, 'update'])->name('update');
                        Route::post('confirm-payment/{item}', [InvoiceController::class, 'confirmPayment'])->name('confirm-payment');
                        Route::post('decline-payment/{item}', [InvoiceController::class, 'declinePayment'])->name('decline-payment');
                        Route::post('resend-delivery-code/{item}', [InvoiceController::class, 'resendDeliveryCode'])->name('resend-delivery-code');
                        Route::get('delete/{item}', [InvoiceController::class, 'destroy'])->name('destroy');
                        Route::get('restore/{item}', [InvoiceController::class, 'restore'])->name('restore');
                        Route::get('remove/ordere/{order}', [InvoiceController::class, 'removeOrder'])->name('remove-order');
                        Route::post('bulk', [InvoiceController::class, 'bulk'])->name('bulk');
                        Route::get('trashed', [InvoiceController::class, 'trashed'])->name('trashed');
                    });
                Route::prefix('langs')->name('lang.')->group(
                    function () {
                        Route::get('/', [XLangController::class, 'index'])->name('index');
                        Route::get('/translates', [XLangController::class, 'translate'])->name('translate');
                        Route::get('/delete/{item}', [XLangController::class, 'destroy'])->name('delete');
                        Route::get('/create', [XLangController::class, 'create'])->name('create');
                        Route::post('/store', [XLangController::class, 'store'])->name('store');
                        Route::get('/edit/{item}', [XLangController::class, 'edit'])->name('edit');
                        Route::post('/update/{item}', [XLangController::class, 'update'])->name('update');
                        Route::post('bulk', [XLangController::class, 'bulk'])->name('bulk');
                        Route::get('/download/{tag}', [XLangController::class, 'download'])->name('download');
                        Route::get('/ai/{tag}', [XLangController::class, 'ai'])->name('ai');
                        Route::post('/upload/{tag}', [XLangController::class, 'upload'])->name('upload');
                        Route::get('/model/translate/{id}/{model}', [XLangController::class, 'translateModel'])->name('model');
                        Route::post('/model/translate/save/{id}/{model}', [XLangController::class, 'translateModelSave'])->name('modelSave');
                        Route::get('/model/ai/{id}/{model}/{field}/{lang}', [XLangController::class, 'translateModelAi'])->name('aiText');
                        Route::get('restore/{item}', [XLangController::class, 'restore'])->name('restore');
                        Route::get('trashed', [XLangController::class, 'trashed'])->name('trashed');

                    });
                Route::prefix('menus')->name('menu.')->group(
                    function () {
                        Route::get('', [MenuController::class, 'index'])->name('index');
                        Route::get('create', [MenuController::class, 'create'])->name('create');
                        Route::post('store', [MenuController::class, 'store'])->name('store');
                        Route::get('show/{item}', [MenuController::class, 'show'])->name('show');
                        Route::get('edit/{item}', [MenuController::class, 'edit'])->name('edit');
                        Route::post('update/{item}', [MenuController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [MenuController::class, 'destroy'])->name('destroy');
                        Route::get('restore/{item}', [MenuController::class, 'restore'])->name('restore');
                        Route::post('bulk', [MenuController::class, 'bulk'])->name('bulk');
                        Route::get('trashed', [MenuController::class, 'trashed'])->name('trashed');
                        Route::post('sort/save', [MenuController::class, 'sortSave'])->name('sort-save');
                        Route::get('sort/{item}', [MenuController::class, 'sort'])->name('sort');
                    });
                Route::prefix('posts')->name('post.')->group(
                    function () {
                        Route::get('', [PostController::class, 'index'])->name('index');
                        Route::get('create', [PostController::class, 'create'])->name('create');
                        Route::post('store', [PostController::class, 'store'])->name('store');
                        Route::get('show/{item}', [PostController::class, 'show'])->name('show');
                        Route::get('edit/{item}', [PostController::class, 'edit'])->name('edit');
                        Route::post('update/{item}', [PostController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [PostController::class, 'destroy'])->name('destroy');
                        Route::get('restore/{item}', [PostController::class, 'restore'])->name('restore');
                        Route::post('bulk', [PostController::class, 'bulk'])->name('bulk');
                        Route::get('trashed', [PostController::class, 'trashed'])->name('trashed');
                        Route::get('group/edit/{id?}', [PostController::class, 'groupEdit'])->name('group-edit');
                        Route::post('group/save/{item}', [PostController::class, 'groupSave'])->name('group-save');
                    });
                Route::prefix('products')->name('product.')->group(
                    function () {
                        Route::get('', [ProductController::class, 'index'])->name('index');
                        Route::get('create', [ProductController::class, 'create'])->name('create');
                        Route::post('store', [ProductController::class, 'store'])->name('store');
                        Route::get('show/{item}', [ProductController::class, 'show'])->name('show');
                        Route::post('title/update', [ProductController::class, 'updateTitle'])->name('title');
                        Route::get('edit/{item?}', [ProductController::class, 'edit'])->name('edit');
                        Route::post('update/{item?}', [ProductController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [ProductController::class, 'destroy'])->name('destroy');
                        Route::get('restore/{item}', [ProductController::class, 'restore'])->name('restore');
                        Route::post('bulk', [ProductController::class, 'bulk'])->name('bulk');
                        Route::get('trashed', [ProductController::class, 'trashed'])->name('trashed');
                        Route::get('category/edit/{id?}', [ProductController::class, 'categoryEdit'])->name('category-edit');
                        Route::post('category/save/{item}', [ProductController::class, 'categorySave'])->name('category-save');

                    });
                Route::prefix('stock')->name('stock.')->group(
                    function () {
                        Route::get('', [StockController::class, 'index'])->name('index');
                        Route::get('edit/{item}', fn ($item) => redirect()->route('admin.product.edit', $item))->name('edit');
                        Route::get('product/{product}/pieces', [StockController::class, 'pieces'])->name('pieces');
                        Route::post('piece/{quantity}/toggle-scrap', [StockController::class, 'togglePieceScrap'])->name('piece.toggle-scrap');
                    });
                Route::prefix('props')->name('prop.')->group(
                    function () {
                        Route::get('', [PropController::class, 'index'])->name('index');
                        Route::get('create', [PropController::class, 'create'])->name('create');
                        Route::post('store', [PropController::class, 'store'])->name('store');
                        Route::get('show/{item}', [PropController::class, 'show'])->name('show');
                        Route::post('title/update', [PropController::class, 'updateTitle'])->name('title');
                        Route::get('edit/{item}', [PropController::class, 'edit'])->name('edit');
                        Route::post('update/{item}', [PropController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [PropController::class, 'destroy'])->name('destroy');
                        Route::get('restore/{item}', [PropController::class, 'restore'])->name('restore');

                        Route::post('bulk', [PropController::class, 'bulk'])->name('bulk');
                        Route::get('trashed', [PropController::class, 'trashed'])->name('trashed');

                        Route::post('sort/save', [PropController::class, 'sortSave'])->name('sort-save');
                        Route::get('sort', [PropController::class, 'sort'])->name('sort');
                    });
                Route::prefix('questions')->name('question.')->group(
                    function () {
                        Route::get('', [QuestionController::class, 'index'])->name('index');
                        //                        Route::get('create', [\App\Http\Controllers\Admin\TransportController::class, 'create'])->name('create');
                        //                        Route::post('store', [\App\Http\Controllers\Admin\TransportController::class, 'store'])->name('store');
                        Route::get('edit/{item}', [QuestionController::class, 'edit'])->name('edit');
                        Route::post('update/{item}', [QuestionController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [QuestionController::class, 'destroy'])->name('destroy');
                        //                        Route::get('restore/{item}', [\App\Http\Controllers\Admin\QuestionController::class, 'restore'])->name('restore');
                        Route::post('bulk', [QuestionController::class, 'bulk'])->name('bulk');
                    });
                Route::prefix('setting')->name('setting.')->group(
                    function () {
                        Route::get('index', [SettingController::class, 'index'])->name('index');
                        Route::post('store', [SettingController::class, 'store'])->name('store');
                        Route::post('update', [SettingController::class, 'update'])->name('update');
                        Route::get('cache/clear', [SettingController::class, 'cacheClear'])->name('cache-clear');
                    }
                );
                Route::prefix('tags')->name('tag.')->group(
                    function () {
                        Route::get('', [TagController::class, 'index'])->name('index');
                    });
                Route::prefix('tickets')->name('ticket.')->group(
                    function () {
                        Route::get('', [TicketController::class, 'index'])->name('index');
                        //                        Route::get('create', [\App\Http\Controllers\Admin\TicketController::class, 'create'])->name('create');
                        Route::post('store', [TicketController::class, 'store'])->name('store');
                        Route::get('edit/{item}', [TicketController::class, 'edit'])->name('edit');
                        Route::post('update/{item}', [TicketController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [TicketController::class, 'destroy'])->name('destroy');
                        Route::post('bulk', [TicketController::class, 'bulk'])->name('bulk');
                    });
                Route::prefix('transports')->name('transport.')->group(
                    function () {
                        Route::get('', [TransportController::class, 'index'])->name('index');
                        Route::get('create', [TransportController::class, 'create'])->name('create');
                        Route::post('store', [TransportController::class, 'store'])->name('store');
                        Route::get('edit/{item}', [TransportController::class, 'edit'])->name('edit');
                        Route::post('update/{item}', [TransportController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [TransportController::class, 'destroy'])->name('destroy');
                        Route::get('restore/{item}', [TransportController::class, 'restore'])->name('restore');
                        Route::post('bulk', [TransportController::class, 'bulk'])->name('bulk');
                        Route::get('trashed', [TransportController::class, 'trashed'])->name('trashed');
                    });
                Route::prefix('bank-accounts')->name('bank-account.')->group(
                    function () {
                        Route::get('', [BankAccountController::class, 'index'])->name('index');
                        Route::get('create', [BankAccountController::class, 'create'])->name('create');
                        Route::post('store', [BankAccountController::class, 'store'])->name('store');
                        Route::get('edit/{item}', [BankAccountController::class, 'edit'])->name('edit');
                        Route::post('update/{item}', [BankAccountController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [BankAccountController::class, 'destroy'])->name('destroy');
                        Route::get('restore/{item}', [BankAccountController::class, 'restore'])->name('restore');
                        Route::get('activate/{item}', [BankAccountController::class, 'activate'])->name('activate');
                        Route::post('bulk', [BankAccountController::class, 'bulk'])->name('bulk');
                        Route::get('trashed', [BankAccountController::class, 'trashed'])->name('trashed');
                    });
                Route::prefix('shop-visits')->name('shop-visit.')->group(
                    function () {
                        Route::get('', [ShopVisitController::class, 'index'])->name('index');
                        Route::get('export', [ShopVisitController::class, 'export'])->name('export');
                        Route::get('show/{item}', [ShopVisitController::class, 'show'])->name('show');
                        Route::get('delete/{item}', [ShopVisitController::class, 'destroy'])->name('destroy');
                        Route::post('bulk', [ShopVisitController::class, 'bulk'])->name('bulk');
                        Route::post('step-one', [VisitorFormController::class, 'storeStepOne'])
                            ->middleware('visitor')
                            ->name('step-one');
                        Route::post('step-two', [VisitorFormController::class, 'storeStepTwo'])
                            ->middleware('visitor')
                            ->name('step-two');
                    });

                Route::get('deliveries/dispatch-sheet', [CourierDeliveryController::class, 'dispatchSheet'])->name('delivery.dispatch-sheet');
                Route::prefix('deliveries')->name('delivery.')->middleware('courier')->group(
                    function () {
                        Route::get('', [CourierDeliveryController::class, 'index'])->name('index');
                        Route::post('{delivery}/accept', [CourierDeliveryController::class, 'accept'])->name('accept');
                        Route::post('{delivery}/reject', [CourierDeliveryController::class, 'reject'])->name('reject');
                        Route::post('{delivery}/confirm', [CourierDeliveryController::class, 'confirm'])->name('confirm');
                        Route::post('{delivery}/fail', [CourierDeliveryController::class, 'fail'])->name('fail');
                    });

                Route::prefix('users')->name('user.')->group(
                    function () {
                        Route::get('', [UserController::class, 'index'])->name('index');
                        Route::get('create', [UserController::class, 'create'])->name('create');
                        Route::post('store', [UserController::class, 'store'])->name('store');
                        Route::get('edit/{item}', [UserController::class, 'edit'])->name('edit');
                        Route::get('log/{item}', [AdminLogController::class, 'log'])->name('log');
                        Route::get('show/{item}', [UserController::class, 'show'])->name('show');
                        Route::post('update/{item}', [UserController::class, 'update'])->name('update');
                        Route::get('delete/{item}', [UserController::class, 'destroy'])->name('destroy');
                        Route::get('restore/{item}', [UserController::class, 'restore'])->name('restore');
                        Route::post('bulk', [UserController::class, 'bulk'])->name('bulk');
                        Route::get('trashed', [UserController::class, 'trashed'])->name('trashed');
                    });

            });

    });

Route::get('/theme/variable', [ThemeController::class, 'cssVariables'])->name('theme.variable.css');

Route::middleware([VisitorCounter::class])
    ->name('client.')->group(function () {
        // index
        Route::get('/', [ClientController::class, 'welcome'])->name('welcome');
        Route::get('/homev1', [ClientController::class, 'homeV1'])->name('homev1');
        Route::get('/old', [ClientController::class, 'oldHome'])->name('old');
        Route::get('/posts', [ClientController::class, 'posts'])->name('posts');
        Route::get('/post/{post}', [ClientController::class, 'post'])->name('post');
        Route::get('/customer/sign-out', [ClientController::class, 'signOut'])->name('sign-out');
        Route::post('/customer/sign-in/do', [ClientController::class, 'singInDo'])->name('sign-in-do');
        Route::get('/customer/sign-in', [ClientController::class, 'signIn'])->name('sign-in');
        Route::get('/customer/sign-up', [ClientController::class, 'signUp'])->name('sign-up');
        Route::post('/customer/sign-up/now', [ClientController::class, 'signUpNow'])->name('sign-up-now');
        Route::get('/customer/send/auth-code', [ClientController::class, 'sendSms'])->name('send-sms');
        Route::get('/customer/check/auth-code', [ClientController::class, 'checkAuth'])->name('check-auth');
        Route::get('/customer/profile', [CustomerController::class, 'profile'])->name('customer.profile');
        Route::post('/customer/rate', [ClientController::class, 'rate'])->name('rate');
        Route::get('/compare', [ClientController::class, 'compare'])->name('compare');
        Route::get('/contact-us', [ClientController::class, 'contact'])->name('contact');
        Route::post('/contact-us/submit', [ClientController::class, 'sendContact'])->name('send-contact');
        Route::get('/galleries', [ClientController::class, 'galleries'])->name('galleries');
        Route::get('/videos', [ClientController::class, 'clips'])->name('clips');
        Route::post('/card/check', [CardController::class, 'check'])
            ->middleware('auth:customer')
            ->name('card.check');
        Route::post('/card/complete-profile', [CardController::class, 'completeCheckoutProfile'])
            ->middleware('auth:customer')
            ->name('card.complete-profile');
        Route::get('/card/discount/{code?}', [CardController::class, 'discount'])->name('card.discount');
        Route::get('/card', [CardController::class, 'index'])->name('card');
        Route::get('/cardClear', [CardController::class, 'clearing'])->name('card.clear');
        Route::get('/profile', [CustomerController::class, 'profile'])->name('profile');
        Route::get('/addresses', [CustomerController::class, 'addresses'])->name('addresses');
        Route::post('/address/store', [CustomerController::class, 'addressStore'])->name('address.store');
        Route::post('/address/update/{address?}', [CustomerController::class, 'addressUpdate'])->name('address.update');
        Route::get('/address/destroy/{address?}', [CustomerController::class, 'addressDestroy'])->name('address.destroy');
        Route::post('/profile/save', [CustomerController::class, 'save'])->name('profile.save');
        Route::post('/ticket/submit', [CustomerController::class, 'submitTicket'])->name('ticket.submit');
        Route::post('/ticket/answer/{ticket}', [CustomerController::class, 'ticketAnswer'])->name('ticket.answer');
        Route::get('/ticket/{ticket}', [CustomerController::class, 'showTicket'])->name('ticket.show');
        Route::get('/invoice/{invoice}', [CustomerController::class, 'invoice'])->name('invoice');
        Route::get('/invoice/{invoice}/receipt', [PaymentReceiptController::class, 'showReceiptForm'])
            ->middleware('auth:customer')
            ->name('invoice.receipt');
        Route::post('/invoice/{invoice}/receipts', [PaymentReceiptController::class, 'store'])
            ->middleware('auth:customer')
            ->name('invoice.receipts.store');
        Route::get('/products', [ClientController::class, 'products'])->name('products');
        Route::get('/products/{category}', [ClientController::class, 'category'])->name('category');
        Route::get('/category/{category}', function ($category) {
            return redirect()->to(route('client.category', $category), 301);
        });
        Route::get('/product/{product?}', [ClientController::class, 'product'])->name('product');
        Route::get('/attachments', [ClientController::class, 'attachments'])->name('attachments');
        Route::get('/attachment/{attachment}', [ClientController::class, 'attachment'])->name('attachment');
        Route::get('/tag/{slug}', [ClientController::class, 'tag'])->name('tag');
        Route::get('/group/{slug}', [ClientController::class, 'group'])->name('group');
        Route::get('/video/{clip}', [ClientController::class, 'clip'])->name('clip');
        Route::get('/gallery/{gallery}', [ClientController::class, 'gallery'])->name('gallery');
        Route::get('/search', [ClientController::class, 'search'])->name('search');
        Route::get('attach/download/{attachment}', [ClientController::class, 'attachDl'])->name('attach-dl');
        Route::get('pay/{invoice}', [ClientController::class, 'pay'])->name('pay');

        Route::get('product/fav/toggle/{product?}', [CustomerController::class, 'ProductFavToggle'])->name('product-fav-toggle');
        Route::get('product/bookmark/toggle/{product?}', [CustomerController::class, 'ProductBookmarkToggle'])->name('product-bookmark-toggle');
        Route::get('product/compare/toggle/{product?}', [CardController::class, 'productCompareToggle'])->name('product-compare-toggle');
        Route::get('card/toggle/{product?}', [CardController::class, 'productCardToggle'])->name('product-card-toggle');

        Route::post('/comment/submit', [ClientController::class, 'submitComment'])->name('comment.submit');
    });

Route::get('/sitemap.xml', [ClientController::class, 'sitemap'])->name('sitemap');
Route::get('/sitemap/products.xml', [ClientController::class, 'sitemapProducts'])->name('sitemap.products');
Route::get('/sitemap/posts.xml', [ClientController::class, 'sitemapPosts'])->name('sitemap.posts');
Route::get('/sitemap/clips.xml', [ClientController::class, 'sitemapClips'])->name('sitemap.clips');
Route::get('/sitemap/galleries.xml', [ClientController::class, 'sitemapGalleries'])->name('sitemap.galleries');
Route::get('/sitemap/attachments.xml', [ClientController::class, 'sitemapAttachments'])->name('sitemap.attachments');
Route::get('/sitemap/categories.xml', [ClientController::class, 'sitemapGroupCategory'])->name('sitemap.categories');
Route::get('/rss/post.xml', [ClientController::class, 'postRss'])->name('rss.post');
Route::get('/rss/product.xml', [ClientController::class, 'productRss'])->name('rss.product');

// to developer test
Route::get('login/as/{mobile}', function ($mobile) {
    if (auth()->check() && auth()->user()->hasRole('developer')) {
        if ($mobile = 1) {
            return Auth::guard('customer')
                ->loginUsingId(Customer::inRandomOrder()->first()->id);
        } else {
            return Auth::guard('customer')
                ->loginUsingId(Customer::where('mobile', $mobile)->first()->id);
        }
    } else {
        return abort(403);
    }
})->name('login.as');

// quick local logins (dev only)
Route::get('quick-login/admin', function () {
    if (! app()->environment('local')) {
        return abort(404);
    }
    Auth::loginUsingId(User::where('role', 'DEVELOPER')->firstOrFail()->id);

    return redirect()->route('admin.home');
})->name('quick-login.admin');

Route::get('quick-login/customer', function () {
    if (! app()->environment('local')) {
        return abort(404);
    }
    $customer = Customer::whereNotNull('name')->orderByDesc('id')->first()
        ?? Customer::firstOrFail();
    Auth::guard('customer')->loginUsingId($customer->id);

    return redirect()->route('client.profile');
})->name('quick-login.customer');

Route::get('test', function () {
    $p = Product::first();

    return $p->evaluations();
})->name('test');

Route::get('whoami', function () {
    if (! auth('customer')->check()) {
        return 'You are nothing';
    }

    return Auth::guard('customer')->user();
})->name('whoami');

// Route::get('/payment/redirect/bank/{invoice}/{gateway}', \App\Http\Controllers\Payment\GatewayRedirectController::class)->name('redirect.bank');
Route::any('/payment/check/{invoice_hash}/{gateway}', GatewayVerifyController::class)->name('pay.check');

Route::any('{lang}/{any}', [ClientController::class, 'lang'])
    ->where('any', '.*')
    ->where('lang', '[A-Za-z]{2}')
    ->middleware([LangControl::class, VisitorCounter::class]);
Route::any('{lang}', [ClientController::class, 'langIndex'])
    ->where('lang', '[A-Za-z]{2}')
    ->middleware([LangControl::class, VisitorCounter::class]);

Route::any('under-construction', [ClientController::class, 'underConstruction'])->name('client.under-construction');
