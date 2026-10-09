<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryStatus;
use App\Enums\ShopPaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OrderBoardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        abort_unless(auth()->user()->hasAnyAccess('invoice'), 403);

        $scope = $request->input('scope', 'active');
        $search = trim((string) $request->input('q'));
        $galleryAddress = (string) getSetting('address');
        $perPage = min(200, max(10, (int) $request->input('per_page', 50)));

        $baseQuery = Invoice::query()->with([
            'customer',
            'address.state',
            'address.city',
            'transport',
            'payments.supplier',
            'payments.receipts',
            'paymentReceipts.customer',
            'activeDelivery.courier',
            'deliveries.courier',
        ]);

        // The scope tabs must count what the user is actually looking at, so the
        // search predicate is applied before the counts are taken.
        $applySearch = function (Builder $query) use ($search): void {
            if ($search === '') {
                return;
            }

            $query->where(function ($q) use ($search) {
                $q->where('hash', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('mobile', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('address', fn ($aq) => $aq->where('address', 'like', "%{$search}%")->orWhereHas('state', fn ($sq) => $sq->where('name', 'like', "%{$search}%")));
            });
        };

        $searchQuery = clone $baseQuery;
        $applySearch($searchQuery);

        $activeCount = (clone $searchQuery)->whereNotIn('status', [Invoice::COMPLETED, Invoice::FAILED, Invoice::CANCELED])->count();
        $completedCount = (clone $searchQuery)->where('status', Invoice::COMPLETED)->count();
        $allCount = (clone $searchQuery)->count();

        $query = clone $baseQuery;
        $applySearch($query);

        if ($scope === 'active') {
            $query->whereNotIn('status', [Invoice::COMPLETED, Invoice::FAILED, Invoice::CANCELED]);
        } elseif ($scope === 'completed') {
            $query->where('status', Invoice::COMPLETED);
        }

        $orders = $query->latest('id')
            ->paginate($perPage)
            ->through(fn (Invoice $inv) => $this->toBoardOrder($inv, $galleryAddress));

        return view('admin.orders.board', compact(
            'orders',
            'scope',
            'search',
            'perPage',
            'activeCount',
            'completedCount',
            'allCount',
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function toBoardOrder(Invoice $inv, string $galleryAddress): array
    {
        $isPickup = $inv->isPickup();
        $isReadyForPickup = $inv->status === Invoice::READY_FOR_PICKUP;
        $isCollected = $inv->status === Invoice::COMPLETED;
        $isMotorcycle = $inv->requiresDeliveryCode();
        $isConfirmed = in_array($inv->status, Invoice::successfulStatuses(), true);
        $isClosed = in_array($inv->status, [Invoice::FAILED, Invoice::CANCELED], true);
        $hasReceipt = $inv->hasUploadedReceipt();

        // A postal shipment never creates a Delivery row, so "is a courier
        // involved" has to be derived from the transport, not from deliveries.
        $hasDelivery = $inv->activeDelivery !== null
            || in_array($inv->status, [Invoice::OUT_FOR_DELIVERY, Invoice::COMPLETED], true);
        $isCourier = ! $isPickup && $isMotorcycle && $hasDelivery;
        $isPostal = ! $isPickup && $inv->transport !== null && ! $isMotorcycle;

        $isDelivered = $isPickup ? $isCollected : ($inv->status === Invoice::COMPLETED || $inv->hasSuccessfulDelivery());
        $fulfillmentReady = $isPickup && ($isReadyForPickup || $isCollected);

        // Money actually received vs. what the customer owes. This is what makes
        // the settlement stage distinct from the confirmation stage.
        $receiptsAmount = (int) $inv->receivedAmount();
        $invoiceTotal = (int) $inv->total_price;
        $remainingBalance = max(0, $invoiceTotal - $receiptsAmount);
        $isSettled = $isConfirmed && $remainingBalance === 0;

        $confirmedPayment = $inv->payments
            ->where('status', Payment::SUCCESS)
            ->sortByDesc('id')
            ->first();
        $confirmMeta = $confirmedPayment?->meta ?? [];
        $confirmedAt = null;
        if ($confirmedPayment) {
            $confirmedAt = isset($confirmMeta['confirmed_at'])
                ? Carbon::parse($confirmMeta['confirmed_at'])->jdate('Y/m/d H:i')
                : $confirmedPayment->updated_at?->jdate('Y/m/d H:i');
        }
        $confirmedByName = $confirmMeta['confirmed_by_name'] ?? null;
        $acceptedReceipt = $confirmedPayment
            ? $inv->paymentReceipts->firstWhere('payment_id', $confirmedPayment->id)
            : null;

        $enteredPayments = $inv->payments
            ->filter(fn (Payment $p) => $p->status === Payment::SUCCESS || ($p->meta['channel'] ?? '') === Payment::CHANNEL_IN_STORE)
            ->sortByDesc('id')
            ->values()
            ->map(function (Payment $p) use ($inv) {
                $meta = $p->meta ?? [];
                $methodEnum = ! empty($meta['method']) ? ShopPaymentMethod::tryFrom($meta['method']) : null;
                $methodLabel = $methodEnum?->label() ?? ($meta['method'] ?? $p->type);
                $slipReceipt = $inv->paymentReceipts->firstWhere('payment_id', $p->id) ?? $p->receipts->first();

                $paymentDate = $meta['payment_date'] ?? $p->created_at?->jdate('Y/m/d');
                $paymentTime = $meta['payment_time'] ?? $p->created_at?->format('H:i');
                $dateFormatted = trim(($paymentDate ?: '').' '.($paymentTime ?: ''));

                return [
                    'id' => $p->id,
                    'method' => $methodLabel,
                    'method_value' => $meta['method'] ?? strtolower($p->type),
                    'amount' => (int) $p->amount,
                    'status' => $p->status,
                    'reference' => $p->reference_id,
                    'date' => $dateFormatted ?: '—',
                    'supplier_name' => $p->supplier?->name ?? ($meta['supplier_name'] ?? null),
                    'bank_account_name' => $meta['bank_account_name'] ?? null,
                    'card_number' => $meta['card_number'] ?? null,
                    'recorded_by' => $meta['confirmed_by_name'] ?? null,
                    'slip' => $slipReceipt ? [
                        'url' => $slipReceipt->url(),
                        'is_image' => $slipReceipt->isImage(),
                        'name' => $slipReceipt->original_name ?: basename($slipReceipt->path),
                        'size' => $slipReceipt->size ? number_format($slipReceipt->size / 1024, 1).' KB' : '',
                    ] : null,
                ];
            });

        $operatorSlipIds = $inv->payments
            ->where('meta.channel', Payment::CHANNEL_IN_STORE)
            ->pluck('receipts.*.id')
            ->flatten()
            ->filter()
            ->all();

        $receipts = $inv->paymentReceipts
            ->reject(fn (PaymentReceipt $r) => in_array($r->id, $operatorSlipIds, true))
            ->sortByDesc('id')
            ->map(fn (PaymentReceipt $r) => [
                'url' => $r->url(),
                'is_image' => $r->isImage(),
                'name' => $r->original_name ?: basename($r->path),
                'size' => $r->size ? number_format($r->size / 1024, 1).' KB' : '',
                'date' => $r->created_at?->jdate('Y/m/d H:i') ?? '—',
                'uploader' => $r->customer?->name ?? '—',
            ])->values();

        $delivery = $inv->activeDelivery ?: $inv->deliveries->sortByDesc('id')->first();
        $deliveredDelivery = $inv->deliveries
            ->where('status', DeliveryStatus::Delivered->value)
            ->sortByDesc('id')
            ->first();

        $details = [
            'payment' => [
                'entered_payments' => $enteredPayments,
                'receipts' => $receipts,
                'pending_amount' => $inv->payments->where('status', Payment::PENDING)->sortByDesc('id')->first()?->amount,
                'declined_at' => isset($inv->meta['declined_at'])
                    ? Carbon::parse($inv->meta['declined_at'])->jdate('Y/m/d H:i')
                    : null,
                'decline_reason' => $inv->meta['decline_reason'] ?? null,
            ],
            'confirm' => [
                'done' => $isConfirmed,
                'confirmed_at' => $confirmedAt,
                'confirmed_by' => $confirmedByName,
                'receipt' => $acceptedReceipt ? [
                    'url' => $acceptedReceipt->url(),
                    'is_image' => $acceptedReceipt->isImage(),
                    'name' => $acceptedReceipt->original_name ?: basename($acceptedReceipt->path),
                ] : null,
                'reference' => $confirmedPayment?->reference_id,
            ],
            'settle' => [
                'done' => $isSettled,
                'received' => $receiptsAmount,
                'invoice_total' => $invoiceTotal,
                'remaining' => $remainingBalance,
                'settled_at' => $isSettled ? $confirmedAt : null,
                'confirmed_by' => $confirmedByName,
            ],
            'courier' => $delivery ? [
                'name' => $delivery->courier?->name ?? '—',
                'email' => $delivery->courier?->email,
                'status' => $delivery->status->value,
                'accepted_at' => $delivery->accepted_at?->jdate('Y/m/d H:i'),
                'failed_attempts' => $delivery->failed_attempts,
            ] : null,
            'delivery' => [
                'done' => $isDelivered,
                'delivered_at' => $isPickup ? null : $deliveredDelivery?->delivered_at?->jdate('Y/m/d H:i'),
            ],
        ];

        $pickupLocation = $galleryAddress !== '' ? $galleryAddress : __('Gallery address is not configured.');
        $province = $inv->address?->state?->name ?: ($inv->address_alt ? __('Specified in note') : '—');
        $address = $inv->address?->address ?: ($inv->address_alt ?: '—');

        if ($isPickup) {
            $province = __('Store pickup');
            $address = $pickupLocation;
            $fulfillmentText = $fulfillmentReady ? __('Ready for pickup') : __('Preparing pickup');
            $fulfillmentTitle = $fulfillmentReady
                ? __('Order is ready for store pickup.')
                : __('Order is being prepared for store pickup.');
            $handoverText = __('Not ready for pickup');
            $handoverTitle = __('Not ready for pickup');

            if ($isReadyForPickup) {
                $handoverText = __('Awaiting customer collection');
                $handoverTitle = __('Awaiting customer collection');
            }

            if ($isCollected) {
                $handoverText = __('Collected');
                $handoverTitle = __('Collected at store');
            }
        } elseif ($isPostal) {
            $fulfillmentText = $hasDelivery ? __('Dispatched') : __('Pending dispatch');
            $fulfillmentTitle = $hasDelivery ? __('Handed over to the postal service') : __('Preparing the parcel for dispatch');
            $handoverText = $isDelivered ? __('Delivered') : __('In transit');
            $handoverTitle = $isDelivered ? __('Delivered to customer') : __('In delivery transit');
        } else {
            $fulfillmentText = $isCourier ? __('Dispatched') : __('Pending courier');
            $fulfillmentTitle = $isCourier ? __('Handed over to courier') : __('Awaiting courier pickup');
            $handoverText = $isDelivered ? __('Delivered') : __('In transit');
            $handoverTitle = $isDelivered ? __('Delivered to customer') : __('In delivery transit');
        }

        return [
            'id' => $inv->id,
            'hash' => $inv->hash ?? (string) $inv->id,
            'invoice' => $inv,
            'customer_code' => $inv->customer?->code ?: ('ZK-'.($inv->customer_id ?? $inv->id)),
            'customer_name' => $inv->customer?->name ?: __('Customer'),
            'customer_mobile' => $inv->customer?->mobile ?: '—',
            'is_third_party' => (bool) $inv->is_third_party,
            'recipient_name' => $inv->recipient_name,
            'recipient_mobile' => $inv->recipient_mobile,
            'contact_name' => ($inv->is_third_party && $inv->recipient_name) ? $inv->recipient_name : $inv->customer?->name,
            'contact_mobile' => ($inv->is_third_party && $inv->recipient_mobile) ? $inv->recipient_mobile : ($inv->customer?->mobile ?: '—'),
            'province' => $province,
            'city' => $inv->address?->city?->name ?: '',
            'address' => $address,
            'postal_code' => $inv->address?->zip ?: '',
            'pickup_location' => $pickupLocation,
            'is_pickup' => $isPickup,
            'is_motorcycle' => $isMotorcycle,
            'is_postal' => $isPostal,
            'date_persian' => $inv->created_at?->jdate('Y/m/d') ?? '—',
            'time_persian' => $inv->created_at?->jdate('H:i') ?? '',
            'sortable_date' => (string) $inv->created_at?->timestamp,
            'total_price' => $invoiceTotal,
            'stages' => [
                'payment' => $this->paymentStage($isConfirmed, $hasReceipt, $isClosed, $invoiceTotal, $receiptsAmount, $enteredPayments->isNotEmpty()),
                'confirm' => [
                    'done' => $isConfirmed,
                    'state' => $isClosed ? 'closed' : ($isConfirmed ? 'done' : 'pending'),
                    'text' => $isConfirmed ? __('Confirmed') : __('Pending'),
                    'title' => $isConfirmed ? __('Payment verified by admin') : __('Awaiting admin verification'),
                ],
                'settle' => $this->settleStage($isSettled, $hasReceipt, $isClosed, $remainingBalance, $invoiceTotal),
                'courier' => [
                    'done' => $isPickup ? $fulfillmentReady : $hasDelivery,
                    'text' => $fulfillmentText,
                    'title' => $fulfillmentTitle,
                    'is_pickup' => $isPickup,
                ],
                'delivery' => [
                    'done' => $isDelivered,
                    'text' => $handoverText,
                    'title' => $handoverTitle,
                    'is_pickup' => $isPickup,
                ],
            ],
            'details' => $details,
        ];
    }

    /**
     * Payment is three-state: verified, evidence-received, or nothing. Marking a
     * merely-uploaded receipt as "Paid" alongside a pending confirmation badge
     * made the operational board contradict itself.
     *
     * @return array<string, mixed>
     */
    private function paymentStage(bool $isConfirmed, bool $hasReceipt, bool $isClosed, int $total, int $received, bool $hasEnteredPayments = false): array
    {
        if ($isClosed) {
            return [
                'done' => false,
                'state' => 'closed',
                'text' => __('Canceled'),
                'title' => __('This order was closed before payment was completed.'),
            ];
        }

        if ($isConfirmed) {
            return [
                'done' => true,
                'state' => 'done',
                'text' => __('Paid'),
                'title' => __('Payment confirmed by admin or gateway.'),
            ];
        }

        if ($hasEnteredPayments && $received > 0) {
            return [
                'done' => false,
                'state' => 'short',
                'text' => __('Partially paid'),
                'title' => __('Payment of :received of :total Toman entered (:remaining remaining).', [
                    'received' => number_format($received),
                    'total' => number_format($total),
                    'remaining' => number_format(max(0, $total - $received)),
                ]),
            ];
        }

        if ($hasEnteredPayments) {
            return [
                'done' => false,
                'state' => 'awaiting',
                'text' => __('Payment recorded'),
                'title' => __('Payment recorded by operator.'),
            ];
        }

        if ($hasReceipt) {
            return [
                'done' => false,
                'state' => 'awaiting',
                'text' => __('Receipt uploaded'),
                'title' => __('A receipt is uploaded and waiting for admin verification.'),
            ];
        }

        return [
            'done' => false,
            'state' => 'pending',
            'text' => __('Unpaid'),
            'title' => __('Awaiting customer payment.'),
        ];
    }

    /**
     * Settlement is the money check: does the receipt total actually cover the
     * invoice? This used to mirror the confirmation stage verbatim.
     *
     * @return array<string, mixed>
     */
    private function settleStage(bool $isSettled, bool $hasReceipt, bool $isClosed, int $remaining, int $total): array
    {
        if ($isSettled) {
            return [
                'done' => true,
                'state' => 'done',
                'text' => __('Settled'),
                'title' => __('Balance fully settled.'),
            ];
        }

        if ($remaining > 0 && ($hasReceipt || $total - $remaining > 0)) {
            return [
                'done' => false,
                'state' => 'short',
                'text' => __('Short'),
                'title' => __('Receipt covers :received of :total — short by :remaining.', [
                    'received' => number_format($total - $remaining),
                    'total' => number_format($total),
                    'remaining' => number_format($remaining),
                ]),
                'remaining' => $remaining,
            ];
        }

        if ($hasReceipt) {
            return [
                'done' => false,
                'state' => 'awaiting',
                'text' => __('Awaiting verification'),
                'title' => __('Receipt uploaded but not yet verified against the invoice total.'),
            ];
        }

        return [
            'done' => false,
            'state' => $isClosed ? 'closed' : 'pending',
            'text' => __('Nothing uploaded'),
            'title' => __('No payment receipt has been uploaded yet.'),
        ];
    }
}
