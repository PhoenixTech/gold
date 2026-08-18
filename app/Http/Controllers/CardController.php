<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment as PaymentModel;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Transport;
use App\Services\CartQuoteService;
use App\Services\ProductPriceCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CardController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (\Session::has('locate')) {
                app()->setLocale(\Session::get('locate'));
            }

            return $next($request);
        });
    }

    public function productCardToggle(Product $product)
    {
        $quantity = request()->filled('quantity') ? (int) request()->input('quantity') : null;

        if ($product->availableQuantities()->exists()) {
            if ($quantity === null) {
                $quantity = $product->firstAvailableQuantity()?->id;
            }

            $stockPiece = Quantity::query()
                ->where('product_id', $product->id)
                ->whereKey($quantity)
                ->where('count', '>', 0)
                ->first();

            if ($stockPiece === null) {
                $msg = __('Selected stock piece is not available');
                if (request()->expectsJson() || request()->ajax()) {
                    return errors([], 422, $msg);
                }

                return redirect()->back()->withErrors($msg);
            }
        }

        $cart = getCartData();
        $cards = $cart['cards'];
        $qs = $cart['qs'];

        $existingIndex = null;
        foreach ($cards as $i => $cardId) {
            if ((int) $cardId === (int) $product->id && ($qs[$i] ?? null) == $quantity) {
                $existingIndex = $i;
                break;
            }
        }

        if ($existingIndex !== null) {
            unset($cards[$existingIndex], $qs[$existingIndex]);
            $cards = array_values($cards);
            $qs = array_values($qs);
            $msg = __('Product removed from card');
        } else {
            $cards[] = $product->id;
            $qs[] = $quantity;
            $msg = __('Product added to card');
        }

        $count = count($cards);
        \Cookie::queue('card', json_encode($cards), 2000);
        \Cookie::queue('q', json_encode($qs), 2000);

        if (auth('customer')->check()) {
            $customer = auth('customer')->user();
            $customer->card = $count > 0 ? json_encode(['cards' => $cards, 'quantities' => $qs]) : null;
            $customer->save();
        }

        app(CartQuoteService::class)->forget();

        if (request()->ajax() || request()->expectsJson()) {
            return success(['count' => $count], $msg);
        }

        return redirect()->back()->with(['message' => $msg]);
    }

    public function index()
    {
        $area = 'card';
        $title = __('Shopping card');
        $subtitle = '';

        return view('client.default-list', compact('area', 'title', 'subtitle'));
    }

    public function check(Request $request)
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        if (! $customer->isCheckoutReady()) {
            return redirect()
                ->route('client.profile')
                ->withErrors(__('Please complete your name, mobile and address before checkout.'));
        }

        $request->validate([
            'product_id' => ['required', 'array'],
            'count' => ['required', 'array'],
            'quantity_id' => ['nullable', 'array'],
            'address_id' => ['required', 'exists:addresses,id'],
            'transport_id' => ['required', 'exists:transports,id'],
            'payment_method' => ['required', 'in:card'],
            'discount_id' => ['nullable', 'exists:discounts,id'],
            'desc' => ['nullable', 'string'],
        ]);

        if (! $customer->addresses()->whereKey($request->address_id)->exists()) {
            throw ValidationException::withMessages([
                'address_id' => __('Selected address is invalid'),
            ]);
        }

        $quote = app(CartQuoteService::class);
        $quote->assertValidForCheckout();

        $total = 0;
        $calculator = app(ProductPriceCalculator::class);
        $activeBankAccount = BankAccount::activeAccount();
        if ($activeBankAccount === null) {
            throw ValidationException::withMessages([
                'payment_method' => __('No active bank account is configured. Please contact support.'),
            ]);
        }

        try {
            $invoice = DB::transaction(function () use ($request, &$total, $calculator, $customer, $quote) {
                $invoice = new Invoice;
                $invoice->customer_id = $customer->id;
                $invoice->count = array_sum($request->count);
                $invoice->address_id = $request->address_id;
                $invoice->desc = $request->desc;
                $invoice->status = Invoice::AWAITING_PAYMENT;

                $transport = Transport::query()->findOrFail($request->input('transport_id'));
                $invoice->transport_id = $transport->id;
                $invoice->transport_price = $transport->price;
                $total += (int) $transport->price;

                if ($request->filled('discount_id')) {
                    $invoice->discount_id = $request->input('discount_id');
                }

                $invoice->save();

                $productsTotal = 0;
                foreach ($request->product_id as $i => $productId) {
                    $product = Product::query()->lockForUpdate()->findOrFail($productId);
                    $order = new Order;
                    $order->product_id = $product->id;
                    $order->invoice_id = $invoice->id;
                    $order->count = (int) $request->count[$i];

                    $quantityId = $request->quantity_id[$i] ?? null;

                    if ($product->availableQuantities()->exists()) {
                        if ($quantityId === null || $quantityId === '') {
                            throw ValidationException::withMessages([
                                'quantity_id' => __('You need to select one stock piece'),
                            ]);
                        }

                        $quantity = Quantity::query()
                            ->where('product_id', $product->id)
                            ->whereKey($quantityId)
                            ->lockForUpdate()
                            ->first();

                        if ($quantity === null || $quantity->count <= 0) {
                            throw ValidationException::withMessages([
                                'quantity_id' => __('Selected stock piece is not available'),
                            ]);
                        }

                        if ($order->count > 1) {
                            throw ValidationException::withMessages([
                                'count' => __('Each stock piece can only be purchased once'),
                            ]);
                        }

                        $order->quantity_id = $quantity->id;
                        $order->price_total = $quote->unitPrice($product, $quantity) * $order->count;
                        $order->data = $quantity->data;
                        $order->save();

                        $quantity->markSold();
                        $calculator->syncProductAggregates($product->fresh());
                    } elseif ($quantityId !== null && $quantityId !== '') {
                        $quantity = Quantity::query()->whereKey($quantityId)->lockForUpdate()->firstOrFail();
                        $order->quantity_id = $quantity->id;
                        $order->price_total = $quote->unitPrice($product, $quantity) * $order->count;
                        $order->data = $quantity->data;
                        $order->save();

                        $quantity->markSold();
                        $calculator->syncProductAggregates($product->fresh());
                    } else {
                        $order->price_total = $quote->unitPrice($product, null) * $order->count;
                        $order->save();
                    }

                    $productsTotal += $order->price_total;
                }

                if ($invoice->discount_id) {
                    $discount = Discount::query()->whereKey($invoice->discount_id)->first();
                    if ($discount) {
                        $productsTotal = $discount->type === 'PERCENT'
                            ? (int) (((100 - $discount->amount) * $productsTotal) / 100)
                            : max(0, $productsTotal - (int) $discount->amount);
                    }
                }

                $total = $productsTotal + (int) $invoice->transport_price;
                $invoice->total_price = $total;
                $invoice->save();

                return $invoice;
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            \Log::error('Checkout exception: '.$exception->getMessage());

            return redirect()->back()->withErrors(__('error in payment. contact admin.'));
        }

        $payableAmount = (int) (($invoice->total_price - $invoice->credit_price) * config('app.currency.factor'));

        $invoice->storePaymentRequest(
            'CARD-'.$invoice->hash.'-'.time(),
            $payableAmount,
            null,
            'CARD',
            'card-to-card'
        );
        $payment = $invoice->payments()->latest('id')->first();
        if ($payment) {
            $payment->status = PaymentModel::PENDING;
            $payment->meta = array_merge($payment->meta ?? [], $activeBankAccount->toPaymentMeta());
            $payment->save();
        }

        self::clear();

        return redirect()
            ->route('client.invoice', $invoice->hash)
            ->with('message', __('Order registered. Please pay by card-to-card and wait for confirmation.'));
    }

    public static function clear()
    {
        if (auth('customer')->check()) {
            $customer = auth('customer')->user();
            $customer->card = null;
            $customer->save();
        }
        \Cookie::expire('card');
        \Cookie::expire('q');
        app(CartQuoteService::class)->forget();

        return true;
    }

    protected function releaseStockPieces(Invoice $invoice): void
    {
        $calculator = app(ProductPriceCalculator::class);

        foreach ($invoice->orders as $order) {
            if (! $order->quantity_id) {
                continue;
            }

            $quantity = Quantity::query()->find($order->quantity_id);
            if ($quantity === null) {
                continue;
            }

            $quantity->count = 1;
            $quantity->save();
            $calculator->syncProductAggregates($quantity->product);
        }
    }

    public function clearing()
    {
        self::clear();

        return __('Card cleared');
    }

    public function discount($code)
    {
        $discount = Discount::where('code', trim($code))->where(function ($query) {
            $query->where('expire', '>=', date('Y-m-d'))
                ->orWhereNull('expire');
        })->first();
        if ($discount == null) {
            return [
                'OK' => false,
                'err' => __("Discount code isn't valid."),
            ];
        }

        if ($discount->type == 'PERCENT') {
            $human = $discount->title.'( '.$discount->amount.'%'.' )';
        } else {
            $human = '- '.$discount->title.'( '.$discount->amount.config('app.currency.symbol').' )';
        }

        return [
            'OK' => true,
            'msg' => __('Discount code is valid.'),
            'data' => $discount,
            'human' => $human,
        ];
    }

    public function productCompareToggle($slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $compares = \Cookie::has('compares') ? (json_decode(\Cookie::get('compares'), true) ?: []) : [];

        $index = array_search($product->id, $compares);
        if ($index !== false) {
            unset($compares[$index]);
            $compares = array_values($compares);
            $msg = __('Product removed from compare');
        } else {
            $compares[] = $product->id;
            $msg = __('Product added to compare');
        }

        \Cookie::queue('compares', json_encode($compares), 2000);

        if (request()->ajax() || request()->expectsJson()) {
            return success(null, $msg);
        }

        return redirect()->back()->with(['message' => $msg]);
    }

    public function completeCheckoutProfile(Request $request)
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/', 'unique:customers,mobile,'.$customer->id],
        ];

        if (! $customer->addresses()->exists()) {
            $rules['address'] = ['required', 'string', 'min:10'];
        }

        $request->validate($rules, [
            'mobile.regex' => __('Mobile number format is invalid'),
        ]);

        $customer->name = $request->input('name');
        $customer->mobile = $request->input('mobile');
        $customer->save();

        if (! $customer->addresses()->exists()) {
            $address = new Address;
            $address->customer_id = $customer->id;
            $address->address = $request->input('address');
            $address->save();
        }

        $customer->load('addresses');

        return success([
            'profile_complete' => $customer->isCheckoutReady(),
            'addresses' => $customer->addresses,
            'customer' => [
                'name' => $customer->name,
                'mobile' => $customer->mobile,
                'email' => $customer->email,
            ],
        ], __('Profile updated successfully'));
    }

    /**
     * @return array{bank_name: string|null, account_holder_name: string|null, card_number: string|null, account_number: string|null, iban: string|null}
     */
    public static function activeBankDisplay(): array
    {
        return BankAccount::displayPayload();
    }

    /**
     * @deprecated Use activeBankDisplay() / BankAccount instead.
     */
    public static function ensureBankSettings(): array
    {
        $bank = self::activeBankDisplay();

        return [
            'bank_card_number' => (string) ($bank['card_number'] ?? ''),
            'bank_sheba' => (string) ($bank['iban'] ?? ''),
            'bank_account_name' => (string) ($bank['account_holder_name'] ?? ''),
            'bank_name' => (string) ($bank['bank_name'] ?? ''),
            'bank_account_number' => (string) ($bank['account_number'] ?? ''),
        ];
    }
}
