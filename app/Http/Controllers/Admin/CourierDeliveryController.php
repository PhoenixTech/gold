<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryConfirmRequest;
use App\Http\Requests\DeliveryFailRequest;
use App\Http\Requests\DeliveryRejectRequest;
use App\Models\Delivery;
use App\Services\DeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourierDeliveryController extends Controller
{
    public function index(): View
    {
        $courier = auth()->user();

        $deliveries = Delivery::query()
            ->with([
                'invoice.customer',
                'invoice.address.state',
                'invoice.address.city',
                'invoice.orders.product',
                'invoice.orders.quantity',
                'invoice.transport',
            ])
            ->forCourier($courier)
            ->open()
            ->latest('id')
            ->get();

        $history = Delivery::query()
            ->with([
                'invoice.customer',
                'invoice.address',
            ])
            ->forCourier($courier)
            ->whereNotIn('status', DeliveryStatus::openValues())
            ->latest('id')
            ->limit(20)
            ->get();

        return view('admin.deliveries.index', compact('deliveries', 'history'));
    }

    public function accept(Delivery $delivery, DeliveryService $deliveries): RedirectResponse
    {
        $deliveries->accept($delivery, auth()->user());

        return back()->with('message', __('Delivery accepted. Ask the customer for the SMS code at the door.'));
    }

    public function reject(DeliveryRejectRequest $request, Delivery $delivery, DeliveryService $deliveries): RedirectResponse
    {
        $deliveries->reject($delivery, auth()->user(), $request->validated('reason'));

        return back()->with('message', __('Delivery rejected. The order returned to the shop.'));
    }

    public function confirm(DeliveryConfirmRequest $request, Delivery $delivery, DeliveryService $deliveries): RedirectResponse
    {
        $deliveries->confirm($delivery, auth()->user(), $request->validated('code'));

        return back()->with('message', __('Delivery confirmed. The gold was handed over.'));
    }

    public function fail(DeliveryFailRequest $request, Delivery $delivery, DeliveryService $deliveries): RedirectResponse
    {
        $deliveries->fail($delivery, auth()->user(), $request->validated('reason'));

        return back()->with('message', __('Delivery marked as failed. The order returned to the shop.'));
    }
}
