<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use Illuminate\Contracts\View\View;
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

        $query = Invoice::query()
            ->with(['customer', 'address.state', 'address.city', 'payments', 'paymentReceipts.customer', 'activeDelivery.courier', 'deliveries.courier'])
            ->latest('id');

        $activeCount = (clone $query)->whereNotIn('status', [Invoice::COMPLETED, Invoice::FAILED, Invoice::CANCELED])->count();
        $completedCount = (clone $query)->where('status', Invoice::COMPLETED)->count();
        $allCount = (clone $query)->count();

        if ($scope === 'active') {
            $query->whereNotIn('status', [Invoice::COMPLETED, Invoice::FAILED, Invoice::CANCELED]);
        } elseif ($scope === 'completed') {
            $query->where('status', Invoice::COMPLETED);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('hash', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%")->orWhere('mobile', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                    ->orWhereHas('address', fn ($aq) => $aq->where('address', 'like', "%{$search}%")->orWhereHas('state', fn ($sq) => $sq->where('name', 'like', "%{$search}%")));
            });
        }

        $orders = $query->get()->values()->map(function (Invoice $inv, int $i) {
            $isPickup = $inv->isPickup();
            $isPaid = in_array($inv->status, [Invoice::PAID, Invoice::PROCESSING, Invoice::OUT_FOR_DELIVERY, Invoice::COMPLETED], true) || ($inv->hasUploadedReceipt() && ! in_array($inv->status, [Invoice::CANCELED, Invoice::FAILED], true));
            $isConfirmed = in_array($inv->status, [Invoice::PAID, Invoice::PROCESSING, Invoice::OUT_FOR_DELIVERY, Invoice::COMPLETED], true);
            $isCourier = ! $isPickup && (in_array($inv->status, [Invoice::OUT_FOR_DELIVERY, Invoice::COMPLETED], true) || $inv->activeDelivery !== null);
            $isDelivered = $inv->status === Invoice::COMPLETED || $inv->hasSuccessfulDelivery();

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

            $receipts = $inv->paymentReceipts->sortByDesc('id')->map(fn (PaymentReceipt $r) => [
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
                    'done' => $isConfirmed,
                    'settled_at' => $confirmedAt,
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
                    'delivered_at' => $deliveredDelivery?->delivered_at?->jdate('Y/m/d H:i'),
                ],
            ];

            return [
                'index' => $i + 1,
                'id' => $inv->id,
                'hash' => $inv->hash ?? (string) $inv->id,
                'invoice' => $inv,
                'customer_code' => $inv->customer?->code ?: ('ZK-'.($inv->customer_id ?? $inv->id)),
                'customer_name' => $inv->customer?->name ?: __('Customer'),
                'customer_mobile' => $inv->customer?->mobile ?: '—',
                'province' => $inv->address?->state?->name ?: ($inv->address_alt ? __('Specified in note') : '—'),
                'city' => $inv->address?->city?->name ?: '',
                'address' => $inv->address?->address ?: ($inv->address_alt ?: '—'),
                'postal_code' => $inv->address?->zip ?: '',
                'date_persian' => $inv->created_at?->jdate('Y/m/d') ?? '—',
                'time_persian' => $inv->created_at?->jdate('H:i') ?? '',
                'total_price' => $inv->total_price ?? 0,
                'stages' => [
                    'payment' => [
                        'done' => $isPaid,
                        'text' => $isPaid ? __('Paid') : __('Unpaid'),
                        'title' => $isPaid ? __('Customer has paid or uploaded receipt') : __('Awaiting customer payment'),
                    ],
                    'confirm' => [
                        'done' => $isConfirmed,
                        'text' => $isConfirmed ? __('Confirmed') : __('Pending'),
                        'title' => $isConfirmed ? __('Payment verified by admin') : __('Awaiting admin verification'),
                    ],
                    'settle' => [
                        'done' => $isConfirmed,
                        'text' => $isConfirmed ? __('Settled') : __('Unsettled'),
                        'title' => $isConfirmed ? __('Balance fully settled') : __('Unsettled balance'),
                    ],
                    'courier' => [
                        'done' => $isPickup ? $isConfirmed : $isCourier,
                        'text' => $isPickup ? __('In-person') : ($isCourier ? __('Dispatched') : __('Pending pickup')),
                        'title' => $isPickup ? __('In-person gallery pickup') : ($isCourier ? __('Handed over to courier') : __('Awaiting courier pickup')),
                        'is_pickup' => $isPickup,
                    ],
                    'delivery' => [
                        'done' => $isDelivered,
                        'text' => $isDelivered ? __('Delivered') : ($isPickup ? __('Awaiting visit') : __('In transit')),
                        'title' => $isDelivered ? ($isPickup ? __('Delivered in gallery') : __('Delivered to customer')) : ($isPickup ? __('Awaiting customer visit to gallery') : __('In delivery transit')),
                        'is_pickup' => $isPickup,
                    ],
                ],
                'details' => $details,
            ];
        });

        return view('admin.orders.board', compact('orders', 'scope', 'search', 'activeCount', 'completedCount', 'allCount'));
    }
}
