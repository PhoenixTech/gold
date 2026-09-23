<?php

namespace App\Console\Commands;

use App\Enums\DeliveryStatus;
use App\Enums\QuantityPieceStatus;
use App\Http\Controllers\Admin\OrderBoardController;
use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Transport;
use App\Models\User;
use App\Services\DeliveryService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\Models\Role;

class CaptureInvoiceWorkflowScreenshots extends Command
{
    protected $signature = 'invoice:capture-workflow-screenshots {--pad : Use zero-padded 2-digit index (01-10)}';

    protected $description = 'Capture dual-perspective screenshots for all 10 invoice workflow statuses';

    public function handle(): int
    {
        $this->info('Starting invoice workflow screenshot capture...');

        $chromeBinary = $this->resolveChromeBinary();
        if (! $chromeBinary) {
            $this->error('Google Chrome binary not found.');

            return self::FAILURE;
        }

        $screenshotsDir = storage_path('app/workflow-screenshots');
        $previewsDir = public_path('workflow-previews');

        File::ensureDirectoryExists($screenshotsDir);
        File::ensureDirectoryExists($previewsDir);

        $admin = $this->resolveAdminUser();
        $courier = $this->resolveCourierUser();
        $customer = $this->resolveCustomer();
        $address = $this->resolveAddress($customer);
        $product = $this->resolveProduct();
        $quantity = $this->resolveQuantity($product);
        $bankAccount = $this->resolveBankAccount();
        $courierTransport = $this->resolveCourierTransport();
        $standardTransport = $this->resolveStandardTransport();

        $this->ensureSlipImagePlaceholder();

        $statuses = $this->statusDefinitions();
        $capturedFiles = [];

        View::share('errors', new ViewErrorBag);

        $usePadding = (bool) $this->option('pad');

        foreach ($statuses as $def) {
            $index = $def['index'];
            $statusKey = $def['key'];
            $statusLower = strtolower($statusKey);
            $indexFormatted = $usePadding ? sprintf('%02d', $index) : (string) $index;

            $this->line("Processing [{$index}/10] {$statusKey}...");

            $invoice = $this->createFixture(
                $def,
                $customer,
                $address,
                $product,
                $quantity,
                $bankAccount,
                $courierTransport,
                $standardTransport,
                $courier
            );

            $qr = $this->generateQr($invoice);

            $customerHtmlFile = "customer_{$statusLower}.html";
            $adminEditHtmlFile = "admin_edit_{$statusLower}.html";
            $adminShowHtmlFile = "admin_show_{$statusLower}.html";

            $customerPngFile = "customer_{$indexFormatted}_{$statusLower}.png";
            $adminEditPngFile = "admin_edit_{$indexFormatted}_{$statusLower}.png";
            $adminShowPngFile = "admin_show_{$indexFormatted}_{$statusLower}.png";
            $adminLegacyPngFile = "admin_{$indexFormatted}_{$statusLower}.png";

            auth('customer')->login($customer);
            View::share('errors', new ViewErrorBag);
            $customerHtml = view('client.customer.invoice', [
                'title' => __('Invoice'),
                'subtitle' => __('Invoice ID:').' '.$invoice->hash,
                'invoice' => $invoice,
                'qr' => $qr,
            ])->render();
            File::put($previewsDir.'/'.$customerHtmlFile, $customerHtml);

            auth()->login($admin);
            View::share('errors', new ViewErrorBag);

            $editRequest = Request::create(route('admin.invoice.edit', $invoice));
            $editRoute = app('router')->getRoutes()->match($editRequest);
            $editRequest->setRouteResolver(fn () => $editRoute);
            app()->instance('request', $editRequest);

            $invoice->loadMissing([
                'customer.addresses',
                'orders.product',
                'orders.quantity',
                'payments',
                'paymentReceipts',
                'transport',
                'activeDelivery.courier',
            ]);

            $couriers = User::query()->couriers()->orderBy('name')->get();
            $bankAccounts = BankAccount::query()->where('is_active', true)->get();

            $adminEditHtml = view('admin.invoices.invoice-form', [
                'item' => $invoice,
                'couriers' => $couriers,
                'bankAccounts' => $bankAccounts,
            ])->render();
            File::put($previewsDir.'/'.$adminEditHtmlFile, $adminEditHtml);

            $adminShowHtml = view('admin.invoices.invoice-show', [
                'title' => __('Invoice').' #'.$invoice->hash,
                'subtitle' => __('Invoice ID:').' '.$invoice->hash,
                'invoice' => $invoice,
                'qr' => $qr,
                'autoPrint' => false,
            ])->render();
            File::put($previewsDir.'/'.$adminShowHtmlFile, $adminShowHtml);

            $baseUrl = rtrim(config('app.url', 'http://zhonella.test'), '/');
            $customerUrl = $baseUrl.'/workflow-previews/'.$customerHtmlFile;
            $adminEditUrl = $baseUrl.'/workflow-previews/'.$adminEditHtmlFile;
            $adminShowUrl = $baseUrl.'/workflow-previews/'.$adminShowHtmlFile;

            $customerPngPath = $screenshotsDir.'/'.$customerPngFile;
            $adminEditPngPath = $screenshotsDir.'/'.$adminEditPngFile;
            $adminShowPngPath = $screenshotsDir.'/'.$adminShowPngFile;
            $adminLegacyPngPath = $screenshotsDir.'/'.$adminLegacyPngFile;

            $this->captureScreenshot($chromeBinary, $customerUrl, $customerPngPath);
            $this->captureScreenshot($chromeBinary, $adminEditUrl, $adminEditPngPath);
            $this->captureScreenshot($chromeBinary, $adminShowUrl, $adminShowPngPath);
            File::copy($adminEditPngPath, $adminLegacyPngPath);

            $this->assertValidScreenshot($customerPngPath);
            $this->assertValidScreenshot($adminEditPngPath);
            $this->assertValidScreenshot($adminShowPngPath);

            $capturedFiles[] = [
                'index' => $indexFormatted,
                'def' => $def,
                'invoice' => $invoice,
                'customer_file' => $customerPngFile,
                'admin_edit_file' => $adminEditPngFile,
                'admin_show_file' => $adminShowPngFile,
                'admin_file' => $adminLegacyPngFile,
                'customer_size' => filesize($customerPngPath),
                'admin_edit_size' => filesize($adminEditPngPath),
                'admin_show_size' => filesize($adminShowPngPath),
                'admin_size' => filesize($adminEditPngPath),
            ];

            File::delete($previewsDir.'/'.$customerHtmlFile);
            File::delete($previewsDir.'/'.$adminEditHtmlFile);
            File::delete($previewsDir.'/'.$adminShowHtmlFile);
        }

        $this->line('Capturing admin order board (dashboard/order-board)...');

        $boardPngFile = 'admin_order_board.png';
        $boardPngPath = $screenshotsDir.'/'.$boardPngFile;
        $boardHtmlFile = 'admin_order_board.html';

        $boardRequest = Request::create(route('admin.order-board.index'));
        $boardRoute = app('router')->getRoutes()->match($boardRequest);
        $boardRequest->setRouteResolver(fn () => $boardRoute);
        app()->instance('request', $boardRequest);

        auth()->login($admin);
        View::share('errors', new ViewErrorBag);

        $orderBoardController = app(OrderBoardController::class);
        $boardHtml = $orderBoardController->index($boardRequest)->render();
        File::put($previewsDir.'/'.$boardHtmlFile, $boardHtml);

        $baseUrl = rtrim(config('app.url', 'http://zhonella.test'), '/');
        $boardUrl = $baseUrl.'/workflow-previews/'.$boardHtmlFile;

        $this->captureScreenshot($chromeBinary, $boardUrl, $boardPngPath);
        $this->assertValidScreenshot($boardPngPath);
        File::delete($previewsDir.'/'.$boardHtmlFile);

        $boardData = [
            'file' => $boardPngFile,
            'size' => filesize($boardPngPath),
            'url' => route('admin.order-board.index', [], false),
        ];

        File::deleteDirectory($previewsDir);

        $this->generateHtmlIndex($screenshotsDir, $capturedFiles, $boardData);
        $this->generateMarkdownIndex($screenshotsDir, $capturedFiles, $boardData);

        $totalImages = (count($capturedFiles) * 3) + 1;
        $this->info("Workflow screenshots captured successfully. Total images: {$totalImages}");

        return self::SUCCESS;
    }

    private function resolveChromeBinary(): ?string
    {
        $candidates = [
            '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
            '/Applications/Google Chrome Canary.app/Contents/MacOS/Google Chrome Canary',
            '/Applications/Chromium.app/Contents/MacOS/Chromium',
            '/usr/bin/google-chrome',
            '/usr/bin/chromium-browser',
            '/usr/bin/chromium',
        ];

        foreach ($candidates as $path) {
            if (File::exists($path) && is_executable($path)) {
                return $path;
            }
        }

        $process = Process::run('which google-chrome');
        if ($process->successful() && trim($process->output()) !== '') {
            return trim($process->output());
        }

        return null;
    }

    private function resolveAdminUser(): User
    {
        Role::findOrCreate('admin', 'web');

        $admin = User::query()
            ->where(function ($q) {
                $q->where('role', 'ADMIN')
                    ->orWhereHas('roles', fn ($r) => $r->where('name', 'admin'));
            })
            ->first();

        if (! $admin) {
            $admin = User::factory()->create([
                'name' => 'مدیر سامانه ژونلا',
                'email' => 'admin@zhonella.test',
                'role' => 'ADMIN',
            ]);
        }

        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        return $admin;
    }

    private function resolveCourierUser(): User
    {
        Role::findOrCreate('courier', 'web');

        $courier = User::query()
            ->where(function ($q) {
                $q->where('role', 'COURIER')
                    ->orWhereHas('roles', fn ($r) => $r->where('name', 'courier'));
            })
            ->first();

        if (! $courier) {
            $courier = User::factory()->courier()->create([
                'name' => 'پیک اکسپرس ژونلا',
                'email' => 'courier@zhonella.test',
            ]);
        }

        if (! $courier->hasRole('courier')) {
            $courier->assignRole('courier');
        }

        return $courier;
    }

    private function resolveCustomer(): Customer
    {
        $customer = Customer::query()->first();
        if (! $customer) {
            $customer = Customer::factory()->create([
                'name' => 'سارا محمدی',
                'mobile' => '09121234567',
                'national_id' => '0012345678',
            ]);
        }

        return $customer;
    }

    private function resolveAddress(Customer $customer): Address
    {
        $address = Address::query()->where('customer_id', $customer->id)->first();
        if (! $address) {
            $address = new Address;
            $address->customer_id = $customer->id;
            $address->address = 'تهران، خیابان ولیعصر، پلاک ۱۲';
            $address->zip = '1969714321';
            $address->save();
        }

        return $address;
    }

    private function resolveProduct(): Product
    {
        $product = Product::query()->has('quantities')->first();
        if (! $product) {
            $product = Product::factory()->create([
                'name' => 'انگشتر طلا زنانه طرح گلاره',
                'price' => 14500000,
                'weight' => 2.45,
                'status' => 1,
            ]);
        }

        return $product;
    }

    private function resolveQuantity(Product $product): Quantity
    {
        $quantity = $product->quantities()->first();
        if (! $quantity) {
            $quantity = Quantity::factory()->create([
                'product_id' => $product->id,
                'weight' => $product->weight ?: 2.45,
                'count' => 1,
                'code' => 'ZHN-'.rand(1000, 9999),
                'status' => QuantityPieceStatus::Available->value,
            ]);
        }

        return $quantity;
    }

    private function resolveBankAccount(): BankAccount
    {
        $bankAccount = BankAccount::query()->where('is_active', true)->first();
        if (! $bankAccount) {
            $bankAccount = BankAccount::factory()->create([
                'bank_name' => 'بانک ملی ایران',
                'account_name' => 'گالری طلا و جواهر ژونلا',
                'card_number' => '6037991823456789',
                'account_number' => '0102030405006',
                'iban' => 'IR120170000000102030405006',
                'is_active' => true,
            ]);
        }

        return $bankAccount;
    }

    private function resolveCourierTransport(): Transport
    {
        $transport = Transport::query()->where('requires_delivery_code', true)->first();
        if (! $transport) {
            $transport = new Transport;
            $transport->title = 'پیک موتوری اختصاصی (با کد تحویل)';
            $transport->price = 65000;
            $transport->requires_delivery_code = true;
            $transport->save();
        }

        return $transport;
    }

    private function resolveStandardTransport(): Transport
    {
        $transport = Transport::query()->where('requires_delivery_code', false)->first();
        if (! $transport) {
            $transport = new Transport;
            $transport->title = 'پست پیشتاز';
            $transport->price = 45000;
            $transport->requires_delivery_code = false;
            $transport->save();
        }

        return $transport;
    }

    private function ensureSlipImagePlaceholder(): void
    {
        $dir = storage_path('app/public/payment-receipts');
        File::ensureDirectoryExists($dir);

        $placeholderPath = $dir.'/mock_slip.jpg';
        if (! File::exists($placeholderPath)) {
            $sampleSource = public_path('default.jpg');
            if (File::exists($sampleSource)) {
                File::copy($sampleSource, $placeholderPath);
            } else {
                $img = imagecreatetruecolor(600, 400);
                $bg = imagecolorallocate($img, 240, 243, 246);
                imagefill($img, 0, 0, $bg);
                imagejpeg($img, $placeholderPath, 85);
                imagedestroy($img);
            }
        }
    }

    private function statusDefinitions(): array
    {
        return [
            [
                'index' => 1,
                'key' => 'PENDING',
                'status' => Invoice::PENDING,
                'name_fa' => 'در انتظار پرداخت آنلاین',
                'name_en' => 'Pending Online Payment',
                'stock_state' => 'رزرو شده (Piece Reserved)',
                'badge_class' => 'bg-warning text-dark',
                'desc' => 'سفارش ثبت شده و در انتظار اتصال به درگاه پرداخت اینترنتی است.',
            ],
            [
                'index' => 2,
                'key' => 'AWAITING_PAYMENT',
                'status' => Invoice::AWAITING_PAYMENT,
                'name_fa' => 'در انتظار پرداخت',
                'name_en' => 'Awaiting Payment',
                'stock_state' => 'رزرو شده (Piece Reserved)',
                'badge_class' => 'bg-warning text-dark',
                'desc' => 'فاکتور کارت به کارت ثبت شده و در مهلت قانونی پرداخت قرار دارد.',
            ],
            [
                'index' => 3,
                'key' => 'WAITING_RECEIPT',
                'status' => Invoice::AWAITING_PAYMENT,
                'name_fa' => 'در انتظار ثبت فیش',
                'name_en' => 'Waiting Receipt Upload',
                'stock_state' => 'رزرو شده (Piece Reserved)',
                'badge_class' => 'bg-warning text-dark',
                'desc' => 'پرداخت کارت به کارت با تایمر شمارش معکوس مهلت بارگذاری رسید بانکی.',
            ],
            [
                'index' => 4,
                'key' => 'WAITING_CONFIRMATION',
                'status' => Invoice::AWAITING_PAYMENT,
                'name_fa' => 'در انتظار تایید فیش توسط مدیر',
                'name_en' => 'Waiting Admin Confirmation',
                'stock_state' => 'رزرو شده (Piece Reserved)',
                'badge_class' => 'bg-primary text-white',
                'desc' => 'فیش بانکی بارگذاری شده و چک‌لیست ۴ گانه مدیریت جهت تایید فعال است.',
            ],
            [
                'index' => 5,
                'key' => 'PAID',
                'status' => Invoice::PAID,
                'name_fa' => 'پرداخت شده و تایید شده',
                'name_en' => 'Payment Confirmed',
                'stock_state' => 'فروخته شده (Piece Sold)',
                'badge_class' => 'bg-success text-white',
                'desc' => 'پرداخت با موفقیت تایید گردیده و قطعه طلای رزرو شده به فروخته شده تغییر یافت.',
            ],
            [
                'index' => 6,
                'key' => 'PROCESSING',
                'status' => Invoice::PROCESSING,
                'name_fa' => 'در حال بسته‌بندی در انبار',
                'name_en' => 'Processing & Packaging',
                'stock_state' => 'فروخته شده (Piece Sold)',
                'badge_class' => 'bg-info text-dark',
                'desc' => 'فاکتور در مرحله انبارداری، صدور حواله خروج و بسته‌بندی امن قرار دارد.',
            ],
            [
                'index' => 7,
                'key' => 'OUT_FOR_DELIVERY',
                'status' => Invoice::OUT_FOR_DELIVERY,
                'name_fa' => 'تحویل به پیک موتوری',
                'name_en' => 'Out For Delivery (Courier)',
                'stock_state' => 'فروخته شده (Piece Sold)',
                'badge_class' => 'bg-warning-subtle text-warning-emphasis border border-warning',
                'desc' => 'مرسوله به پیک تحویل داده شده و کد ۴ رقمی امنیتی به مشتری پیامک شده است.',
            ],
            [
                'index' => 8,
                'key' => 'COMPLETED',
                'status' => Invoice::COMPLETED,
                'name_fa' => 'سفارش تکمیل شده و تحویل داده شده',
                'name_en' => 'Completed & Delivered',
                'stock_state' => 'فروخته شده نهایی (Piece Sold)',
                'badge_class' => 'bg-success text-white',
                'desc' => 'پیک کد تایید تحویل را ثبت کرده و سفارش با موفقیت به پایان رسیده است.',
            ],
            [
                'index' => 9,
                'key' => 'CANCELED',
                'status' => Invoice::CANCELED,
                'name_fa' => 'لغو شده توسط مدیر یا خریدار',
                'name_en' => 'Order Canceled',
                'stock_state' => 'آزاد شده به قفسه فروش (Piece Restored)',
                'badge_class' => 'bg-secondary text-white',
                'desc' => 'سفارش لغو شده و موجودی طلای رزرو شده مجددا به چرخه فروش بازگشت.',
            ],
            [
                'index' => 10,
                'key' => 'FAILED',
                'status' => Invoice::FAILED,
                'name_fa' => 'ناموفق / انقضای مهلت پرداخت',
                'name_en' => 'Payment Failed / Expired',
                'stock_state' => 'آزاد شده به قفسه فروش (Piece Restored)',
                'badge_class' => 'bg-danger text-white',
                'desc' => 'مهلت پرداخت به پایان رسیده یا تراکنش ناموفق بود؛ قطعه طلا آزاد شد.',
            ],
        ];
    }

    private function createFixture(
        array $def,
        Customer $customer,
        Address $address,
        Product $product,
        Quantity $quantity,
        BankAccount $bankAccount,
        Transport $courierTransport,
        Transport $standardTransport,
        User $courier
    ): Invoice {
        $statusKey = $def['key'];
        $itemPrice = $product->price ?: 14500000;
        $transport = in_array($statusKey, ['OUT_FOR_DELIVERY', 'COMPLETED'], true) ? $courierTransport : $standardTransport;
        $totalPrice = $itemPrice + ($transport->price ?? 0);

        $factory = Invoice::factory();

        $invoice = match ($statusKey) {
            'PENDING' => $factory->pending()->create([
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'transport_id' => $transport->id,
                'transport_price' => $transport->price,
                'total_price' => $totalPrice,
                'count' => 1,
            ]),
            'AWAITING_PAYMENT' => $factory->awaitingPayment()->create([
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'transport_id' => $transport->id,
                'transport_price' => $transport->price,
                'total_price' => $totalPrice,
                'count' => 1,
            ]),
            'WAITING_RECEIPT' => $factory->waitingReceipt()->create([
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'transport_id' => $transport->id,
                'transport_price' => $transport->price,
                'total_price' => $totalPrice,
                'count' => 1,
            ]),
            'WAITING_CONFIRMATION' => $factory->waitingConfirmation()->create([
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'transport_id' => $transport->id,
                'transport_price' => $transport->price,
                'total_price' => $totalPrice,
                'count' => 1,
            ]),
            'PAID' => $factory->paid()->create([
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'transport_id' => $transport->id,
                'transport_price' => $transport->price,
                'total_price' => $totalPrice,
                'count' => 1,
            ]),
            'PROCESSING' => $factory->processing()->create([
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'transport_id' => $transport->id,
                'transport_price' => $transport->price,
                'total_price' => $totalPrice,
                'count' => 1,
            ]),
            'OUT_FOR_DELIVERY' => $factory->outForDelivery()->create([
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'transport_id' => $courierTransport->id,
                'transport_price' => $courierTransport->price,
                'total_price' => $totalPrice,
                'count' => 1,
            ]),
            'COMPLETED' => $factory->completed()->create([
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'transport_id' => $courierTransport->id,
                'transport_price' => $courierTransport->price,
                'total_price' => $totalPrice,
                'count' => 1,
            ]),
            'CANCELED' => $factory->canceled()->create([
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'transport_id' => $transport->id,
                'transport_price' => $transport->price,
                'total_price' => $totalPrice,
                'count' => 1,
            ]),
            'FAILED' => $factory->failed()->create([
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'transport_id' => $transport->id,
                'transport_price' => $transport->price,
                'total_price' => $totalPrice,
                'count' => 1,
            ]),
            default => $factory->create(),
        };

        if ($invoice->orders()->doesntExist()) {
            $order = new Order;
            $order->invoice_id = $invoice->id;
            $order->product_id = $product->id;
            $order->quantity_id = $quantity->id;
            $order->count = 1;
            $order->price_total = $itemPrice;
            $order->save();
        }

        if ($statusKey === 'PENDING') {
            $payment = $invoice->payments()->first();
            if (! $payment) {
                $payment = new Payment;
                $payment->invoice_id = $invoice->id;
                $payment->order_id = $invoice->id;
                $payment->type = 'ONLINE';
                $payment->status = Payment::PENDING;
                $payment->amount = $totalPrice;
                $payment->save();
            }
        }

        if (in_array($statusKey, ['AWAITING_PAYMENT', 'WAITING_RECEIPT'], true)) {
            $payment = $invoice->cardPayment();
            if (! $payment) {
                $payment = new Payment;
                $payment->invoice_id = $invoice->id;
                $payment->order_id = $invoice->id;
                $payment->type = 'CARD';
                $payment->status = Payment::PENDING;
                $payment->amount = $totalPrice;
                $payment->save();
            }
            $invoice->meta = array_merge($invoice->meta ?? [], [
                'offline_deadline_at' => now()->addHours(3)->toDateTimeString(),
            ]);
            $invoice->save();
        }

        if ($statusKey === 'WAITING_CONFIRMATION') {
            $payment = $invoice->cardPayment();
            if (! $payment) {
                $payment = new Payment;
                $payment->invoice_id = $invoice->id;
                $payment->order_id = $invoice->id;
                $payment->type = 'CARD';
                $payment->status = Payment::PENDING;
                $payment->amount = $totalPrice;
                $payment->save();
            }

            $receiptDir = storage_path('app/public/payment-receipts/'.$invoice->id);
            File::ensureDirectoryExists($receiptDir);
            File::copy(storage_path('app/public/payment-receipts/mock_slip.jpg'), $receiptDir.'/slip.jpg');

            if ($invoice->paymentReceipts()->doesntExist()) {
                $receipt = new PaymentReceipt;
                $receipt->payment_id = $payment->id;
                $receipt->invoice_id = $invoice->id;
                $receipt->path = 'payment-receipts/'.$invoice->id.'/slip.jpg';
                $receipt->original_name = 'bank_transfer_slip.jpg';
                $receipt->mime = 'image/jpeg';
                $receipt->size = 245760;
                $receipt->amount = $totalPrice;
                $receipt->payment_date = now()->format('Y-m-d');
                $receipt->payment_time = now()->format('H:i');
                $receipt->tracking_number = 'TRK-'.rand(100000, 999999);
                $receipt->bank_account_id = $bankAccount->id;
                $receipt->uploaded_by_customer_id = $customer->id;
                $receipt->save();
            }

            $invoice->meta = array_merge($invoice->meta ?? [], [
                'offline_deadline_at' => now()->addHours(3)->toDateTimeString(),
            ]);
            $invoice->save();
        }

        if (in_array($statusKey, ['PAID', 'PROCESSING', 'OUT_FOR_DELIVERY', 'COMPLETED'], true)) {
            $payment = $invoice->payments()->first();
            if (! $payment) {
                $payment = new Payment;
                $payment->invoice_id = $invoice->id;
                $payment->order_id = $invoice->id;
                $payment->type = 'CARD';
                $payment->amount = $totalPrice;
            }
            $payment->status = Payment::SUCCESS;
            $payment->save();

            $quantity->update(['status' => QuantityPieceStatus::Sold->value]);
        }

        if ($statusKey === 'OUT_FOR_DELIVERY') {
            $delivery = Delivery::query()->where('invoice_id', $invoice->id)->first();
            if (! $delivery) {
                $delivery = new Delivery;
                $delivery->invoice_id = $invoice->id;
                $delivery->courier_id = $courier->id;
                $delivery->status = DeliveryStatus::Pending;
                $delivery->code_hash = Hash::make('4821');
                $delivery->failed_attempts = 0;
                $delivery->save();
            }
            Cache::put(DeliveryService::codeCacheKey($delivery), '4821', now()->addDays(2));
        }

        if ($statusKey === 'COMPLETED') {
            $delivery = Delivery::query()->where('invoice_id', $invoice->id)->first();
            if (! $delivery) {
                $delivery = new Delivery;
                $delivery->invoice_id = $invoice->id;
                $delivery->courier_id = $courier->id;
                $delivery->status = DeliveryStatus::Delivered;
                $delivery->code_hash = Hash::make('4821');
                $delivery->failed_attempts = 0;
                $delivery->delivered_at = now();
                $delivery->save();
            }
        }

        if (in_array($statusKey, ['CANCELED', 'FAILED'], true)) {
            $quantity->update(['status' => QuantityPieceStatus::Available->value]);
            if ($statusKey === 'FAILED') {
                $invoice->meta = array_merge($invoice->meta ?? [], [
                    'offline_deadline_at' => now()->subHours(4)->toDateTimeString(),
                ]);
                $invoice->save();
            }
        }

        $invoice->loadMissing([
            'customer',
            'address.state',
            'address.city',
            'orders.product',
            'orders.quantity',
            'payments',
            'paymentReceipts',
            'deliveries.courier',
            'activeDelivery.courier',
            'transport',
        ]);

        return $invoice;
    }

    private function generateQr(Invoice $invoice): QRCode
    {
        $options = new QROptions([
            'version' => 5,
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_L,
        ]);

        return new QRCode($options);
    }

    private function captureScreenshot(string $chromeBinary, string $url, string $outputPath): void
    {
        $command = sprintf(
            '%s --headless --disable-gpu --hide-scrollbars --window-size=1400,1000 --screenshot=%s %s',
            escapeshellarg($chromeBinary),
            escapeshellarg($outputPath),
            escapeshellarg($url)
        );

        $process = Process::run($command);

        if (! $process->successful() && ! File::exists($outputPath)) {
            $this->warn('Chrome warning: '.$process->errorOutput());
        }
    }

    private function assertValidScreenshot(string $path): void
    {
        if (! File::exists($path)) {
            throw new \RuntimeException("Screenshot file was not generated: {$path}");
        }

        $size = filesize($path);
        if ($size < 10240) {
            throw new \RuntimeException("Screenshot file is suspiciously small ({$size} bytes): {$path}");
        }
    }

    private function generateHtmlIndex(string $outputDir, array $items, ?array $boardData = null): void
    {
        $boardHtml = '';
        if ($boardData && File::exists($outputDir.'/'.$boardData['file'])) {
            $boardSizeKb = round($boardData['size'] / 1024, 1);
            $boardFile = $boardData['file'];
            $boardHtml = <<<HTML
            <div class="col-12 mb-5 perspective-board">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden border-top border-4 border-primary">
                    <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2.5">
                            <span class="badge bg-primary fs-13 px-3 py-1.5 rounded-pill">داشبورد سفارشات (Order Board)</span>
                            <span class="text-muted fs-13 font-monospace">dashboard/order-board</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-secondary-subtle text-secondary font-monospace fs-11">{$boardSizeKb} KB</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle fs-12">نمای زنده سفارشات فعال و در مسیر</span>
                        </div>
                    </div>
                    <div class="card-body p-3 p-md-4 bg-light bg-opacity-25">
                        <p class="text-muted fs-14 mb-3">تابلوی جامع سفارشات در پنل مدیریت جهت مانیتورینگ بلادرنگ فاکتورهای پرداخت‌شده، تخصیص پیک و وضعیت تحویل.</p>
                        <div class="card border rounded-3 overflow-hidden shadow-xs bg-white text-center p-2">
                            <a href="{$boardFile}" target="_blank" class="preview-lightbox-trigger d-block" data-title="تابلوی سفارشات - Order Board">
                                <img src="{$boardFile}" alt="{$boardFile}" class="img-fluid rounded border shadow-2xs hover-zoom" loading="lazy">
                            </a>
                            <div class="card-footer bg-white border-top py-2 px-3 text-end">
                                <a href="{$boardFile}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 fs-12">
                                    <i class="ri-fullscreen-line me-1"></i> مشاهده در سایز اصلی
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
HTML;
        }

        $cardsHtml = '';
        foreach ($items as $item) {
            $def = $item['def'];
            $idx = $item['index'];
            $statusKey = $def['key'];
            $customerImg = $item['customer_file'];
            $adminEditImg = $item['admin_edit_file'] ?? $item['admin_file'];
            $adminShowImg = $item['admin_show_file'] ?? $item['admin_file'];
            $customerSizeKb = round($item['customer_size'] / 1024, 1);
            $adminEditSizeKb = round(($item['admin_edit_size'] ?? $item['admin_size']) / 1024, 1);
            $adminShowSizeKb = round(($item['admin_show_size'] ?? $item['admin_size']) / 1024, 1);

            $cardsHtml .= <<<HTML
            <div class="col-12 mb-5 status-card" data-status="{$statusKey}">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2.5">
                            <span class="badge rounded-pill bg-dark font-monospace fs-13 px-2.5 py-1">#{$idx}</span>
                            <span class="badge {$def['badge_class']} fs-13 px-3 py-1.5 rounded-pill">{$def['name_fa']}</span>
                            <span class="text-muted fs-13 font-monospace">({$statusKey})</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-light text-secondary border fs-12">{$def['stock_state']}</span>
                        </div>
                    </div>
                    <div class="card-body p-3 p-md-4 bg-light bg-opacity-25">
                        <p class="text-muted fs-14 mb-4">{$def['desc']}</p>
                        
                        <div class="row g-3">
                            <div class="col-lg-4 perspective-customer">
                                <div class="card h-100 border rounded-3 overflow-hidden shadow-xs bg-white">
                                    <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="ri-user-smile-line text-primary fs-5"></i>
                                            <span class="fw-bold fs-13">نمای مشتری (Customer)</span>
                                        </div>
                                        <span class="badge bg-secondary-subtle text-secondary font-monospace fs-11">{$customerSizeKb} KB</span>
                                    </div>
                                    <div class="card-body p-2 text-center">
                                        <a href="{$customerImg}" target="_blank" class="preview-lightbox-trigger d-block" data-title="{$def['name_fa']} - نمای مشتری">
                                            <img src="{$customerImg}" alt="{$customerImg}" class="img-fluid rounded border shadow-2xs hover-zoom" loading="lazy">
                                        </a>
                                    </div>
                                    <div class="card-footer bg-white border-top py-2 px-3 text-end">
                                        <a href="{$customerImg}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 fs-12">
                                            <i class="ri-fullscreen-line me-1"></i> مشاهده اصلی
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 perspective-admin-edit">
                                <div class="card h-100 border rounded-3 overflow-hidden shadow-xs bg-white border-primary-subtle">
                                    <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="ri-edit-box-line text-danger fs-5"></i>
                                            <span class="fw-bold fs-13">ویرایش فاکتور (Admin Edit)</span>
                                        </div>
                                        <span class="badge bg-danger-subtle text-danger font-monospace fs-11">{$adminEditSizeKb} KB</span>
                                    </div>
                                    <div class="card-body p-2 text-center">
                                        <a href="{$adminEditImg}" target="_blank" class="preview-lightbox-trigger d-block" data-title="{$def['name_fa']} - ویرایش فاکتور ادمین">
                                            <img src="{$adminEditImg}" alt="{$adminEditImg}" class="img-fluid rounded border shadow-2xs hover-zoom" loading="lazy">
                                        </a>
                                    </div>
                                    <div class="card-footer bg-white border-top py-2 px-3 text-end">
                                        <a href="{$adminEditImg}" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill px-3 fs-12">
                                            <i class="ri-fullscreen-line me-1"></i> مشاهده اصلی
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-4 perspective-admin-show">
                                <div class="card h-100 border rounded-3 overflow-hidden shadow-xs bg-white">
                                    <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="ri-file-list-3-line text-secondary fs-5"></i>
                                            <span class="fw-bold fs-13">جزئیات / چاپ (Admin Show)</span>
                                        </div>
                                        <span class="badge bg-secondary-subtle text-secondary font-monospace fs-11">{$adminShowSizeKb} KB</span>
                                    </div>
                                    <div class="card-body p-2 text-center">
                                        <a href="{$adminShowImg}" target="_blank" class="preview-lightbox-trigger d-block" data-title="{$def['name_fa']} - جزئیات ادمین">
                                            <img src="{$adminShowImg}" alt="{$adminShowImg}" class="img-fluid rounded border shadow-2xs hover-zoom" loading="lazy">
                                        </a>
                                    </div>
                                    <div class="card-footer bg-white border-top py-2 px-3 text-end">
                                        <a href="{$adminShowImg}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fs-12">
                                            <i class="ri-fullscreen-line me-1"></i> مشاهده اصلی
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
HTML;
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>گالری بصری چرخه فاکتور ژونلا - Dual-Perspective Workflow Gallery</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
    <style>
        body {
            background-color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1e293b;
        }
        .hover-zoom {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .hover-zoom:hover {
            transform: scale(1.015);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
        .sticky-filter-bar {
            position: sticky;
            top: 0;
            z-index: 1020;
            backdrop-filter: blur(8px);
            background-color: rgba(255, 255, 255, 0.92);
        }
        .fs-13 { font-size: 0.8125rem; }
        .fs-12 { font-size: 0.75rem; }
        .fs-11 { font-size: 0.6875rem; }
        .fs-14 { font-size: 0.875rem; }
        .shadow-xs { box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .shadow-2xs { box-shadow: 0 1px 2px rgba(0,0,0,0.03); }
    </style>
</head>
<body class="pb-5">

    <header class="bg-dark text-white py-4 shadow-sm border-bottom border-secondary mb-4">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="ri-gallery-line fs-3 text-warning"></i>
                        <h1 class="h4 mb-0 fw-bold">گالری بصری چرخه فاکتور ژونلا</h1>
                    </div>
                    <p class="text-white-50 fs-13 mb-0">مستندسازی تصویری همه‌جانبه: نمای مشتری، ویرایش فاکتور ادمین، جزئیات و تابلوی سفارشات</p>
                </div>
                <div class="d-flex align-items-center gap-2 font-monospace">
                    <span class="badge bg-primary px-3 py-2 rounded-pill fs-12">۱۰ وضعیت حیات</span>
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fs-12">۳۱ اسکرین‌شات HD</span>
                    <span class="badge bg-success px-3 py-2 rounded-pill fs-12">Native Chrome Headless</span>
                </div>
            </div>
        </div>
    </header>

    <div class="sticky-filter-bar py-3 border-bottom shadow-xs mb-4">
        <div class="container d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="btn-group" role="group" aria-label="Perspective Filter">
                <button type="button" class="btn btn-sm btn-outline-dark active" onclick="setPerspective('all')">
                    <i class="ri-layout-grid-line me-1"></i> همه نماها
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="setPerspective('customer')">
                    <i class="ri-user-smile-line me-1"></i> نمای مشتری
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="setPerspective('admin-edit')">
                    <i class="ri-edit-box-line me-1"></i> ویرایش فاکتور ادمین
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setPerspective('admin-show')">
                    <i class="ri-file-list-3-line me-1"></i> جزئیات ادمین
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="setPerspective('board')">
                    <i class="ri-dashboard-line me-1"></i> تابلوی سفارشات
                </button>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted fs-13">فیلتر وضعیت:</span>
                <select class="form-select form-select-sm" style="width: auto;" onchange="filterStatus(this.value)">
                    <option value="all">همه ۱۰ وضعیت</option>
                    <option value="PENDING">1. PENDING (در انتظار پرداخت آنلاین)</option>
                    <option value="AWAITING_PAYMENT">2. AWAITING_PAYMENT (در انتظار پرداخت)</option>
                    <option value="WAITING_RECEIPT">3. WAITING_RECEIPT (در انتظار ثبت فیش)</option>
                    <option value="WAITING_CONFIRMATION">4. WAITING_CONFIRMATION (در انتظار تایید فیش)</option>
                    <option value="PAID">5. PAID (پرداخت شده)</option>
                    <option value="PROCESSING">6. PROCESSING (در حال بسته‌بندی)</option>
                    <option value="OUT_FOR_DELIVERY">7. OUT_FOR_DELIVERY (تحویل به پیک)</option>
                    <option value="COMPLETED">8. COMPLETED (تکمیل شده)</option>
                    <option value="CANCELED">9. CANCELED (لغو شده)</option>
                    <option value="FAILED">10. FAILED (ناموفق)</option>
                </select>
            </div>
        </div>
    </div>

    <main class="container">
        <div class="row" id="cards-container">
            {$boardHtml}
            {$cardsHtml}
        </div>
    </main>

    <div class="modal fade" id="imageLightboxModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen p-3">
            <div class="modal-content rounded-4 shadow-lg overflow-hidden border-0">
                <div class="modal-header bg-dark text-white border-0 py-2.5 px-4">
                    <h5 class="modal-title fs-14 fw-bold" id="lightboxTitle"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0 bg-dark d-flex align-items-center justify-content-center">
                    <img src="" id="lightboxImg" class="img-fluid" style="max-height: 92vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setPerspective(mode) {
            document.querySelectorAll('.btn-group button').forEach(b => b.classList.remove('active'));
            event.target.classList.add('active');
            
            const cust = document.querySelectorAll('.perspective-customer');
            const edit = document.querySelectorAll('.perspective-admin-edit');
            const show = document.querySelectorAll('.perspective-admin-show');
            const board = document.querySelectorAll('.perspective-board');
            
            if (mode === 'customer') {
                board.forEach(el => el.style.display = 'none');
                edit.forEach(el => el.style.display = 'none');
                show.forEach(el => el.style.display = 'none');
                cust.forEach(el => { el.style.display = 'block'; el.className = 'col-12 perspective-customer'; });
            } else if (mode === 'admin-edit') {
                board.forEach(el => el.style.display = 'none');
                cust.forEach(el => el.style.display = 'none');
                show.forEach(el => el.style.display = 'none');
                edit.forEach(el => { el.style.display = 'block'; el.className = 'col-12 perspective-admin-edit'; });
            } else if (mode === 'admin-show') {
                board.forEach(el => el.style.display = 'none');
                cust.forEach(el => el.style.display = 'none');
                edit.forEach(el => el.style.display = 'none');
                show.forEach(el => { el.style.display = 'block'; el.className = 'col-12 perspective-admin-show'; });
            } else if (mode === 'board') {
                cust.forEach(el => el.style.display = 'none');
                edit.forEach(el => el.style.display = 'none');
                show.forEach(el => el.style.display = 'none');
                board.forEach(el => el.style.display = 'block');
            } else {
                board.forEach(el => el.style.display = 'block');
                cust.forEach(el => { el.style.display = 'block'; el.className = 'col-lg-4 perspective-customer'; });
                edit.forEach(el => { el.style.display = 'block'; el.className = 'col-lg-4 perspective-admin-edit'; });
                show.forEach(el => { el.style.display = 'block'; el.className = 'col-lg-4 perspective-admin-show'; });
            }
        }

        function filterStatus(val) {
            const cards = document.querySelectorAll('.status-card');
            cards.forEach(card => {
                if (val === 'all' || card.dataset.status === val) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        const modalEl = document.getElementById('imageLightboxModal');
        const modal = new bootstrap.Modal(modalEl);
        const lightboxImg = document.getElementById('lightboxImg');
        const lightboxTitle = document.getElementById('lightboxTitle');

        document.querySelectorAll('.preview-lightbox-trigger').forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                lightboxImg.src = trigger.getAttribute('href');
                lightboxTitle.textContent = trigger.getAttribute('data-title');
                modal.show();
            });
        });
    </script>
</body>
</html>
HTML;

        File::put($outputDir.'/index.html', $html);
    }

    private function generateMarkdownIndex(string $outputDir, array $items, ?array $boardData = null): void
    {
        $boardSection = '';
        if ($boardData && File::exists($outputDir.'/'.$boardData['file'])) {
            $boardSize = number_format($boardData['size']);
            $boardSection = <<<MD
## Order Board Overview
- **Route**: `dashboard/order-board`
- **File**: [{$boardData['file']}]({$boardData['file']}) ({$boardSize} bytes)
- **Description**: Live administrative order pipeline showing active and completed orders with live delivery stages.

MD;
        }

        $rows = '';
        foreach ($items as $item) {
            $def = $item['def'];
            $idx = $item['index'];
            $statusKey = $def['key'];
            $customerFile = $item['customer_file'];
            $adminEditFile = $item['admin_edit_file'] ?? $item['admin_file'];
            $adminShowFile = $item['admin_show_file'] ?? $item['admin_file'];
            $customerSize = number_format($item['customer_size']);
            $adminEditSize = number_format($item['admin_edit_size'] ?? $item['admin_size']);
            $adminShowSize = number_format($item['admin_show_size'] ?? $item['admin_size']);

            $rows .= "| {$idx} | `{$statusKey}` | {$def['name_fa']} | Customer (`invoice/{hash}`) | [{$customerFile}]({$customerFile}) | {$customerSize} bytes | {$def['stock_state']} |\n";
            $rows .= "| {$idx} | `{$statusKey}` | {$def['name_fa']} | Admin Edit (`dashboard/invoices/edit/{hash}`) | [{$adminEditFile}]({$adminEditFile}) | {$adminEditSize} bytes | {$def['stock_state']} |\n";
            $rows .= "| {$idx} | `{$statusKey}` | {$def['name_fa']} | Admin Show (`dashboard/invoices/show/{id}`) | [{$adminShowFile}]({$adminShowFile}) | {$adminShowSize} bytes | {$def['stock_state']} |\n";
        }

        $md = <<<MD
# Invoice Lifecycle Workflow Screenshots Index

This directory contains visual documentation for all 10 invoice statuses in the Zhonella gold ecommerce application. Each status captures the customer-facing view (`invoice/{hash}`), the primary admin edit view (`dashboard/invoices/edit/{invoice_hash}`), and the admin show view (`dashboard/invoices/show/{item}`) at 1400x1000 resolution via Google Chrome headless CLI.

{$boardSection}
## Interactive Gallery
Open [index.html](index.html) in any modern browser for side-by-side comparison, filtering, and high-resolution lightbox inspection.

## Screenshots Catalog

| # | Status Key | Status Name | View / Route | Screenshot File | File Size | Stock State |
|---|---|---|---|---|---|---|
{$rows}

## Lifecycle Transition Matrix & Branching Paths

1. **Online Gateway Flow**:
   - `PENDING` -> Payment Gateway -> `PAID` (Piece Marked Sold) or `FAILED` (Stock Restored).
2. **Offline Card-to-Card Flow**:
   - `AWAITING_PAYMENT` / `WAITING_RECEIPT` -> Customer Receipt Upload -> `WAITING_CONFIRMATION`.
   - Admin 4-Point Checklist Approval -> `PAID` / `PROCESSING`.
   - Admin Rejection or Deadline Expiration -> `CANCELED` or `FAILED` (Stock Restored).
3. **Fulfillment & Delivery Flow**:
   - `PAID` -> Admin Packaging -> `PROCESSING` -> Courier Assignment -> `OUT_FOR_DELIVERY` (4-Digit PIN SMS Dispatched).
   - Courier Delivery Code Verification -> `COMPLETED` (`InvoiceCompleted` event dispatched).
4. **Cancellation & Expiration Flow**:
   - Customer or Admin Cancellation -> `CANCELED` (Stock Released to Available).
   - Overdue Offline Payment (`offline:expire`) -> `FAILED` (Stock Released to Available).
MD;

        File::put($outputDir.'/INDEX.md', $md);
    }
}
