<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

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
            ->with(['customer', 'address.state', 'address.city', 'payments', 'paymentReceipts', 'activeDelivery', 'deliveries'])
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

        // ponytail: map rows into a compact shape; client-side JS handles live column sorting
        $orders = $query->get()->values()->map(function (Invoice $inv, int $i) {
            $isPaid = ! in_array($inv->status, [Invoice::AWAITING_PAYMENT, Invoice::PENDING], true) || $inv->hasUploadedReceipt();
            $isConfirmed = in_array($inv->status, [Invoice::PAID, Invoice::PROCESSING, Invoice::OUT_FOR_DELIVERY, Invoice::COMPLETED], true);
            $isCourier = in_array($inv->status, [Invoice::OUT_FOR_DELIVERY, Invoice::COMPLETED], true) || $inv->activeDelivery !== null;
            $isDelivered = $inv->status === Invoice::COMPLETED || $inv->hasSuccessfulDelivery();

            return [
                'index' => $i + 1,
                'id' => $inv->id,
                'hash' => $inv->hash ?? (string) $inv->id,
                'invoice' => $inv,
                'customer_code' => $inv->customer?->code ?: ('ZK-' . ($inv->customer_id ?? $inv->id)),
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
                        'done' => $isCourier,
                        'text' => $isCourier ? __('Dispatched') : __('Pending pickup'),
                        'title' => $isCourier ? __('Handed over to courier') : __('Awaiting courier pickup'),
                    ],
                    'delivery' => [
                        'done' => $isDelivered,
                        'text' => $isDelivered ? __('Delivered') : __('In transit'),
                        'title' => $isDelivered ? __('Delivered to customer') : __('In delivery transit'),
                    ],
                ],
            ];
        });

        return view('admin.orders.board', compact('orders', 'scope', 'search', 'activeCount', 'completedCount', 'allCount'));
    }
}
