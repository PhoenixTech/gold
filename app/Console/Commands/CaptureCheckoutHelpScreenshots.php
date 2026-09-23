<?php

namespace App\Console\Commands;

use App\Enums\DeliveryStatus;
use App\Enums\QuantityPieceStatus;
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
use App\Services\CartQuoteService;
use App\Services\DeliveryService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\Models\Role;

class CaptureCheckoutHelpScreenshots extends Command
{
    protected $signature = 'checkout:capture-help-screenshots';

    protected $description = 'Capture 6 customer-facing mobile screenshots for admin checkout help documentation';

    public function handle(): int
    {
        $this->info('Starting customer mobile checkout screenshots capture...');

        $chromeBinary = $this->resolveChromeBinary();
        if (! $chromeBinary) {
            $this->error('Google Chrome binary not found.');

            return self::FAILURE;
        }

        $outputDir = public_path('workflow-screenshots/mobile');
        $previewsDir = public_path('workflow-previews-mobile');

        File::ensureDirectoryExists($outputDir);
        File::ensureDirectoryExists($previewsDir);

        $customer = $this->resolveCustomer();
        $address = $this->resolveAddress($customer);
        $product = $this->resolveProduct();
        $quantity = $this->resolveQuantity($product);
        $bankAccount = $this->resolveBankAccount();
        $courierTransport = $this->resolveCourierTransport();
        $standardTransport = $this->resolveStandardTransport();
        $courier = $this->resolveCourierUser();

        $this->ensureSlipImagePlaceholder();
        View::share('errors', new ViewErrorBag);

        $baseUrl = rtrim(config('app.url', 'http://zhonella.test'), '/');

        $steps = [
            [
                'step' => 1,
                'file' => 'checkout_mobile_step_01_cart.png',
                'html_file' => 'step_01_cart.html',
                'type' => 'cart',
            ],
            [
                'step' => 2,
                'file' => 'checkout_mobile_step_02_waiting_receipt.png',
                'html_file' => 'step_02_waiting_receipt.html',
                'type' => 'invoice',
                'status' => 'WAITING_RECEIPT',
            ],
            [
                'step' => 3,
                'file' => 'checkout_mobile_step_03_receipt_form.png',
                'html_file' => 'step_03_receipt_form.html',
                'type' => 'receipt_form',
            ],
            [
                'step' => 4,
                'file' => 'checkout_mobile_step_04_waiting_confirmation.png',
                'html_file' => 'step_04_waiting_confirmation.html',
                'type' => 'invoice',
                'status' => 'WAITING_CONFIRMATION',
            ],
            [
                'step' => 5,
                'file' => 'checkout_mobile_step_05_processing.png',
                'html_file' => 'step_05_processing.html',
                'type' => 'invoice',
                'status' => 'PROCESSING',
            ],
            [
                'step' => 6,
                'file' => 'checkout_mobile_step_06_out_for_delivery.png',
                'html_file' => 'step_06_out_for_delivery.html',
                'type' => 'invoice',
                'status' => 'OUT_FOR_DELIVERY',
            ],
        ];

        foreach ($steps as $stepDef) {
            $this->line("Rendering Step {$stepDef['step']}: {$stepDef['file']}...");

            auth('customer')->login($customer);
            View::share('errors', new ViewErrorBag);

            $html = '';

            if ($stepDef['type'] === 'cart') {
                $customer->card = json_encode([
                    'cards' => [$product->id],
                    'quantities' => [$quantity->id],
                ]);
                $customer->save();

                app(CartQuoteService::class)->ensure();

                $html = view('client.cart.index', [
                    'title' => __('Shopping cart'),
                    'subtitle' => '',
                ])->render();
            } elseif ($stepDef['type'] === 'receipt_form') {
                $invoice = $this->createFixtureInvoice(
                    'WAITING_RECEIPT',
                    $customer,
                    $address,
                    $product,
                    $quantity,
                    $bankAccount,
                    $standardTransport,
                    $courier
                );

                $html = view('client.customer.receipt', [
                    'title' => __('Register Payment Receipt'),
                    'invoice' => $invoice,
                    'bankAccount' => $bankAccount,
                ])->render();
            } else {
                $transport = ($stepDef['status'] === 'OUT_FOR_DELIVERY') ? $courierTransport : $standardTransport;
                $invoice = $this->createFixtureInvoice(
                    $stepDef['status'],
                    $customer,
                    $address,
                    $product,
                    $quantity,
                    $bankAccount,
                    $transport,
                    $courier
                );

                $qr = $this->generateQr($invoice);

                $html = view('client.customer.invoice', [
                    'title' => __('Invoice'),
                    'subtitle' => __('Invoice ID:').' '.$invoice->hash,
                    'invoice' => $invoice,
                    'qr' => $qr,
                ])->render();
            }

            File::put($previewsDir.'/'.$stepDef['html_file'], $html);

            $url = $baseUrl.'/workflow-previews-mobile/'.$stepDef['html_file'];
            $destPath = $outputDir.'/'.$stepDef['file'];

            $this->captureMobileScreenshot($chromeBinary, $url, $destPath);

            if (! File::exists($destPath) || filesize($destPath) < 5000) {
                $this->error("Failed generating {$destPath}");
            } else {
                $sizeKb = round(filesize($destPath) / 1024, 1);
                $this->info("✓ Step {$stepDef['step']} captured: {$destPath} ({$sizeKb} KB)");
            }

            File::delete($previewsDir.'/'.$stepDef['html_file']);
        }

        File::deleteDirectory($previewsDir);

        $this->info('All mobile screenshots generated successfully in '.$outputDir);

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

        return null;
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
                'name' => 'دستبند طلا طرح کارتیه',
                'price' => 18500000,
                'weight' => 3.20,
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
                'weight' => $product->weight ?: 3.20,
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

    private function resolveCourierUser(): User
    {
        Role::findOrCreate('courier', 'web');

        $courier = User::query()->where('role', 'COURIER')->first();
        if (! $courier) {
            $courier = User::factory()->courier()->create([
                'name' => 'پیک اکسپرس ژونلا',
                'email' => 'courier@zhonella.test',
            ]);
        }

        return $courier;
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

    private function createFixtureInvoice(
        string $statusKey,
        Customer $customer,
        Address $address,
        Product $product,
        Quantity $quantity,
        BankAccount $bankAccount,
        Transport $transport,
        User $courier
    ): Invoice {
        $itemPrice = $product->price ?: 18500000;
        $totalPrice = $itemPrice + ($transport->price ?? 0);

        $factory = Invoice::factory();

        $invoice = match ($statusKey) {
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

        if (in_array($statusKey, ['WAITING_RECEIPT', 'WAITING_CONFIRMATION'], true)) {
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
        }

        if ($statusKey === 'PROCESSING') {
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
            $quantity->update(['status' => QuantityPieceStatus::Sold->value]);
        }

        $invoice->loadMissing([
            'customer',
            'address.state',
            'address.city',
            'orders.product',
            'orders.quantity',
            'payments',
            'paymentReceipts',
            'transport',
            'activeDelivery.courier',
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

    private function captureMobileScreenshot(string $chromeBinary, string $url, string $outputPath): void
    {
        $command = sprintf(
            '%s --headless --disable-gpu --hide-scrollbars --window-size=500,920 --screenshot=%s %s',
            escapeshellarg($chromeBinary),
            escapeshellarg($outputPath),
            escapeshellarg($url)
        );

        $process = Process::timeout(30)->run($command);

        if (! $process->successful() && ! File::exists($outputPath)) {
            $this->warn('Chrome warning: '.$process->errorOutput());
        }
    }
}
