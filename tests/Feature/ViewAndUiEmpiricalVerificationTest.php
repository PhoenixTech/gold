<?php

namespace Tests\Feature;

use App\Enums\DeliveryStatus;
use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Category;
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
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ViewAndUiEmpiricalVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GfxSeeder::class);
    }

    private function actingAsAdminUser(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');
        $this->actingAs($user, 'web');

        return $user;
    }

    private function createSampleInvoiceRecord(array $attributes = []): Invoice
    {
        $admin = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $admin->id,
            'category_id' => $category->id,
            'name' => 'طلای ۱۸ عیار ژونلا',
            'status' => 1,
            'price' => 5000000,
            'stock_status' => 'IN_STOCK',
            'stock_quantity' => 10,
        ]);

        $quantity = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 2.450,
            'code' => 'ZN-'.rand(1000, 99999),
        ]);

        $customer = Customer::factory()->create([
            'name' => $attributes['customer_name'] ?? 'علی رضایی',
            'mobile' => $attributes['customer_mobile'] ?? ('0912'.rand(1000000, 9999999)),
            'email' => $attributes['customer_email'] ?? ('customer_'.uniqid().'@example.com'),
        ]);

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->address = 'تهران، خیابان ولیعصر، پلاک ۱۲۳';
        $address->zip = '1234567890';
        $address->save();

        $transport = new Transport;
        $transport->title = 'پست پیشتاز';
        $transport->price = 50000;
        $transport->save();

        $invoice = new Invoice;
        $invoice->customer_id = $customer->id;
        $invoice->address_id = $address->id;
        $invoice->transport_id = $transport->id;
        $invoice->transport_price = 50000;
        $invoice->status = $attributes['status'] ?? Invoice::AWAITING_PAYMENT;
        $invoice->total_price = $attributes['total_price'] ?? 5050000;
        $invoice->count = 1;
        $invoice->delivery_type = $attributes['delivery_type'] ?? 'delivery';
        if (isset($attributes['is_third_party'])) {
            $invoice->is_third_party = $attributes['is_third_party'];
            $invoice->recipient_name = $attributes['recipient_name'] ?? null;
            $invoice->recipient_mobile = $attributes['recipient_mobile'] ?? null;
            $invoice->recipient_national_id = $attributes['recipient_national_id'] ?? null;
        }
        $invoice->save();

        $order = new Order;
        $order->invoice_id = $invoice->id;
        $order->product_id = $product->id;
        $order->quantity_id = $quantity->id;
        $order->count = 1;
        $order->price_total = 5000000;
        $order->save();

        return $invoice;
    }

    public function test_admin_invoice_show_renders_confirm_payment_modal_with_five_fields_when_applicable(): void
    {
        $this->actingAsAdminUser();
        $this->withoutVite();

        $bankAccount = BankAccount::factory()->active()->create([
            'bank_name' => 'بانک ملت',
            'card_number' => '6104337788990011',
            'account_holder_name' => 'گالری ژونلا',
        ]);

        $invoice = $this->createSampleInvoiceRecord([
            'status' => Invoice::AWAITING_PAYMENT,
            'total_price' => 1000000,
        ]);

        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->amount = 1000000;
        $payment->type = 'CARD';
        $payment->status = Payment::PENDING;
        $payment->order_id = 'CARD-'.$invoice->hash.'-'.time();
        $payment->save();

        $receipt = new PaymentReceipt;
        $receipt->invoice_id = $invoice->id;
        $receipt->payment_id = $payment->id;
        $receipt->path = 'receipts/receipt.jpg';
        $receipt->original_name = 'receipt.jpg';
        $receipt->amount = 1000000;
        $receipt->payment_date = '1405/06/31';
        $receipt->tracking_number = 'TRK-1001';
        $receipt->bank_account_id = $bankAccount->id;
        $receipt->save();

        $response = $this->get(route('admin.invoice.show', $invoice->hash));

        $response->assertOk();
        $response->assertSee('id="confirmPaymentModal"', false);
        $response->assertSee('data-bs-target="#confirmPaymentModal"', false);
        $response->assertSee('action="'.route('admin.invoice.confirm-payment', $invoice).'"', false);

        $response->assertSee('name="bank_account_id"', false);
        $response->assertSee('name="receipt_info_checked"', false);
        $response->assertSee('name="account_selected"', false);
        $response->assertSee('name="bank_verified"', false);
        $response->assertSee('name="zero_balance"', false);
        $response->assertSee('id="modal-approve-payment-btn"', false);
        $response->assertSee('disabled', false);
        $response->assertDontSee(__('Remaining balance must be zero'));
    }

    public function test_admin_invoice_show_disables_zero_balance_checkbox_when_remaining_balance_positive(): void
    {
        $this->actingAsAdminUser();
        $this->withoutVite();

        $bankAccount = BankAccount::factory()->active()->create();

        $invoice = $this->createSampleInvoiceRecord([
            'status' => Invoice::AWAITING_PAYMENT,
            'total_price' => 2000000,
        ]);

        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->amount = 2000000;
        $payment->type = 'CARD';
        $payment->status = Payment::PENDING;
        $payment->order_id = 'CARD-'.$invoice->hash.'-'.time();
        $payment->save();

        $receipt = new PaymentReceipt;
        $receipt->invoice_id = $invoice->id;
        $receipt->payment_id = $payment->id;
        $receipt->path = 'receipts/partial.jpg';
        $receipt->original_name = 'partial.jpg';
        $receipt->amount = 1000000;
        $receipt->payment_date = '1405/06/31';
        $receipt->tracking_number = 'TRK-PARTIAL';
        $receipt->bank_account_id = $bankAccount->id;
        $receipt->save();

        $response = $this->get(route('admin.invoice.show', $invoice->hash));

        $response->assertOk();
        $response->assertSee('id="confirmPaymentModal"', false);
        $response->assertSee(__('Remaining balance must be zero'));
        $content = $response->getContent();
        $this->assertMatchesRegularExpression('/id="modal_zero_balance"[^>]*disabled/', $content);
    }

    public function test_admin_invoice_show_omits_confirm_payment_modal_when_cannot_confirm(): void
    {
        $this->actingAsAdminUser();
        $this->withoutVite();

        $invoice = $this->createSampleInvoiceRecord([
            'status' => Invoice::PAID,
        ]);

        $response = $this->get(route('admin.invoice.show', $invoice->hash));

        $response->assertOk();
        $response->assertDontSee('id="confirmPaymentModal"', false);
        $response->assertDontSee('data-bs-target="#confirmPaymentModal"', false);
    }

    public function test_shipping_label_renders_gallery_pickup_for_pickup_and_gallery_pickup(): void
    {
        $this->actingAsAdminUser();

        $pickupInvoice = $this->createSampleInvoiceRecord([
            'delivery_type' => 'pickup',
        ]);
        $responsePickup = $this->get(route('admin.invoice.shipping-label', $pickupInvoice->hash));
        $responsePickup->assertOk();
        $responsePickup->assertSee(__('In-person Gallery Pickup'));
        $responsePickup->assertDontSee($pickupInvoice->address->address);

        $galleryInvoice = $this->createSampleInvoiceRecord([
            'delivery_type' => 'gallery_pickup',
        ]);
        $responseGallery = $this->get(route('admin.invoice.shipping-label', $galleryInvoice->hash));
        $responseGallery->assertOk();
        $responseGallery->assertSee(__('In-person Gallery Pickup'));
        $responseGallery->assertDontSee($galleryInvoice->address->address);

        $deliveryInvoice = $this->createSampleInvoiceRecord([
            'delivery_type' => 'delivery',
        ]);
        $responseDelivery = $this->get(route('admin.invoice.shipping-label', $deliveryInvoice->hash));
        $responseDelivery->assertOk();
        $responseDelivery->assertDontSee(__('In-person Gallery Pickup'));
        $responseDelivery->assertSee($deliveryInvoice->address->address);

        $nullDeliveryInvoice = $this->createSampleInvoiceRecord([
            'delivery_type' => null,
        ]);
        $responseNull = $this->get(route('admin.invoice.shipping-label', $nullDeliveryInvoice->hash));
        $responseNull->assertOk();
        $responseNull->assertDontSee(__('In-person Gallery Pickup'));
        $responseNull->assertSee($nullDeliveryInvoice->address->address);
    }

    public function test_shipping_label_renders_third_party_recipient_details(): void
    {
        $this->actingAsAdminUser();

        $invoice = $this->createSampleInvoiceRecord([
            'delivery_type' => 'delivery',
            'is_third_party' => true,
            'recipient_name' => 'سهراب سپهری',
            'recipient_mobile' => '09359876543',
            'recipient_national_id' => '0012345678',
        ]);

        $response = $this->get(route('admin.invoice.shipping-label', $invoice->hash));

        $response->assertOk();
        $response->assertSee('سهراب سپهری');
        $response->assertSee('09359876543');
        $response->assertSee('0012345678');
        $response->assertSee(__('National ID'));
    }

    public function test_dispatch_sheet_renders_gallery_pickup_for_pickup_and_gallery_pickup(): void
    {
        $this->actingAsAdminUser();

        $courier = User::factory()->create(['name' => 'پیک ژونلا']);

        $pickupInvoice = $this->createSampleInvoiceRecord(['delivery_type' => 'pickup']);
        $deliveryPickup = Delivery::create([
            'invoice_id' => $pickupInvoice->id,
            'courier_id' => $courier->id,
            'status' => DeliveryStatus::Accepted,
            'code_hash' => bcrypt('1234'),
        ]);

        $galleryInvoice = $this->createSampleInvoiceRecord(['delivery_type' => 'gallery_pickup']);
        $deliveryGallery = Delivery::create([
            'invoice_id' => $galleryInvoice->id,
            'courier_id' => $courier->id,
            'status' => DeliveryStatus::Accepted,
            'code_hash' => bcrypt('1234'),
        ]);

        $standardInvoice = $this->createSampleInvoiceRecord(['delivery_type' => 'delivery']);
        $deliveryStandard = Delivery::create([
            'invoice_id' => $standardInvoice->id,
            'courier_id' => $courier->id,
            'status' => DeliveryStatus::Accepted,
            'code_hash' => bcrypt('1234'),
        ]);

        $deliveredInvoice = $this->createSampleInvoiceRecord(['delivery_type' => 'delivery']);
        $deliveryFinished = Delivery::create([
            'invoice_id' => $deliveredInvoice->id,
            'courier_id' => $courier->id,
            'status' => DeliveryStatus::Delivered,
            'code_hash' => bcrypt('1234'),
        ]);

        $response = $this->get(route('admin.delivery.dispatch-sheet'));

        $response->assertOk();
        $response->assertSee(__('In-person Gallery Pickup'));
        $response->assertSee($pickupInvoice->hash);
        $response->assertSee($galleryInvoice->hash);
        $response->assertSee($standardInvoice->hash);
        $response->assertSee($standardInvoice->address->address);
        $response->assertDontSee($deliveredInvoice->hash);
    }

    public function test_dispatch_sheet_renders_empty_state_when_no_active_deliveries(): void
    {
        $this->actingAsAdminUser();

        $response = $this->get(route('admin.delivery.dispatch-sheet'));

        $response->assertOk();
        $response->assertSee(__('No active courier deliveries found for today.'));
    }

    public function test_client_invoice_renders_payment_confirmed_banner_for_paid_invoice(): void
    {
        $this->withoutVite();

        $invoice = $this->createSampleInvoiceRecord([
            'status' => Invoice::PAID,
        ]);
        $customer = $invoice->customer;

        $response = $this->actingAs($customer, 'customer')->get(route('client.invoice', $invoice->hash));

        $response->assertOk();
        $response->assertSee('liana-payment-done', false);
        $response->assertSee(__('Payment confirmed'));
        $response->assertDontSee('id="payment-panel"', false);
        $response->assertDontSee('id="receipt-upload"', false);
        $response->assertDontSee(__('Register Payment Receipt'));
    }

    public function test_client_invoice_shows_payment_panel_when_awaiting_payment_card(): void
    {
        $this->withoutVite();

        $invoice = $this->createSampleInvoiceRecord([
            'status' => Invoice::AWAITING_PAYMENT,
        ]);

        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->amount = $invoice->total_price;
        $payment->type = 'CARD';
        $payment->status = Payment::PENDING;
        $payment->order_id = 'CARD-'.$invoice->hash.'-'.time();
        $payment->save();

        $customer = $invoice->customer;

        $response = $this->actingAs($customer, 'customer')->get(route('client.invoice', $invoice->hash));

        $response->assertOk();
        $response->assertSee('id="payment-panel"', false);
        $response->assertSee('id="receipt-upload"', false);
        $response->assertSee(__('Register Payment Receipt'));
        $response->assertDontSee('liana-payment-done', false);
    }

    public function test_persian_translations_for_modified_views_exist_in_fa_json(): void
    {
        $fa = json_decode(file_get_contents(resource_path('lang/fa.json')), true);
        $this->assertIsArray($fa);

        $viewFiles = [
            resource_path('views/admin/invoices/invoice-show.blade.php'),
            resource_path('views/admin/invoices/shipping-label.blade.php'),
            resource_path('views/client/customer/invoice.blade.php'),
        ];

        $missingKeys = [];
        foreach ($viewFiles as $filePath) {
            $content = file_get_contents($filePath);
            preg_match_all("/(?:__|@lang)\(\s*[\x27\x22]([^\x27\x22]+)[\x27\x22]\s*[\),]/", $content, $matches);
            foreach (array_unique($matches[1]) as $key) {
                if (! array_key_exists($key, $fa)) {
                    $missingKeys[$filePath][] = $key;
                }
            }
        }

        $this->assertEmpty($missingKeys, 'Missing translation keys found in views: '.json_encode($missingKeys, JSON_UNESCAPED_UNICODE));
    }

    public function test_dispatch_sheet_translations_audit(): void
    {
        $fa = json_decode(file_get_contents(resource_path('lang/fa.json')), true);
        $filePath = resource_path('views/admin/deliveries/dispatch-sheet.blade.php');
        $content = file_get_contents($filePath);
        preg_match_all("/(?:__|@lang)\(\s*[\x27\x22]([^\x27\x22]+)[\x27\x22]\s*[\),]/", $content, $matches);

        $missingKeys = [];
        foreach (array_unique($matches[1]) as $key) {
            if (! array_key_exists($key, $fa)) {
                $missingKeys[] = $key;
            }
        }

        $this->assertEmpty($missingKeys);
    }
}
