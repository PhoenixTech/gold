@php
    /**
     * Renders one admin list cell for a column.
     *
     * Shared by the row-link column and the plain columns so a column can never
     * render differently depending on its position -- the first column used to
     * bypass the switch entirely and print the bare attribute value.
     *
     * @var \Illuminate\Database\Eloquent\Model $item
     * @var string $col
     */
@endphp
@switch($col)
    @case('parent_id')
    {{ $item->parent?->{$cols[0]}??'-' }}
    @break
    @case('status')
    @php
    $stVal = (string) $item->status;
    $stIsPublished = ($stVal === '1' || strtolower($stVal) === 'published');
    $stIsDraft = ($stVal === '0' || strtolower($stVal) === 'draft');
    @endphp
    @if(method_exists($item, 'statusLabel'))
    <span class="{{ $item->statusBadgeClass() }}">
    {{ $item->statusLabel() }}
    </span>
    @elseif($stIsPublished)
    <span class="badge bg-success-subtle text-success border border-success-subtle">
    {{__("Published")}}
    </span>
    @elseif($stIsDraft)
    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
    {{__("Draft")}}
    </span>
    @else
    <span class="badge bg-info-subtle text-info border border-info-subtle">
    {{ __($item->status) }}
    </span>
    @endif
    @break
    @case('user_id')
    @if($item->user != null)
    <a href="{{route('admin.user.edit',$item->user?->email)}}">
    {{ $item->user?->name??'-' }}
    </a>
    @else
    {{__("Removed")}}
    @endif
    @break
    @case('customer_id')
    @if($item->customer != null)
    <a href="{{route('admin.customer.edit',$item->customer?->id)}}">
    {{ $item->customer?->name??'-' }}
    </a>
    @else
    {{__("Removed")}}
    @endif
    @break
    @case('customer_mobile')
    @if($item->customer?->mobile)
    <a href="tel:{{ $item->customer->mobile }}" dir="ltr" class="font-monospace text-dark text-decoration-none" title="{{ __('Call customer') }}">
    {{ $item->customer->mobile }}
    </a>
    @else
    <span class="text-muted">—</span>
    @endif
    @break
    @case('customer_code')
    <span class="font-monospace text-muted">{{ $item->customer?->code ?: '—' }}</span>
    @break
    @case('hash')
    {{-- Traditional invoice number. Usually the row-link column. --}}
    <span class="font-monospace fw-bold text-primary" dir="ltr">#{{ $item->hash ?: $item->id }}</span>
    <small class="text-muted fs-11 d-block">ID {{ $item->id }}</small>
    @break
    @case('items_summary')
    @php
    $pieces = (int) ($item->count ?? 0);
    $totalWeight = (float) ($item->total_weight ?? 0);
    @endphp
    <div class="d-inline-flex align-items-baseline gap-1">
    <span class="fw-semibold text-dark">{{ number_format($pieces) }}</span>
    <small class="text-muted fs-12">{{ __('pieces') }}</small>
    </div>
    @if($totalWeight > 0)
    <small class="text-muted font-fanum d-block">
    {{ \App\Services\AdminDashboardStats::formatWeight($totalWeight) }} {{ __('g') }}
    </small>
    @endif
    @break
    @case('payment_progress')
    @php
    $isOffline = method_exists($item, 'isOfflineCardPayment') ? $item->isOfflineCardPayment() : false;
    $total = (int) ($item->total_price ?? 0);
    $received = (int) ($item->receipts_amount ?? 0);
    $remaining = max(0, $total - $received);
    $hasReceipt = method_exists($item, 'hasUploadedReceipt') ? $item->hasUploadedReceipt() : $received > 0;
    $isSettled = in_array($item->status, \App\Models\Invoice::successfulStatuses(), true);
    $receiptCount = (int) ($item->payment_receipts_count ?? 0);
    $ratio = $total > 0 ? (int) round(($received / $total) * 100) : 0;
    @endphp
    @if(! $isOffline)
    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-12">
    <i class="ri-bank-card-line me-0.5"></i>{{ __('Online gateway') }}
    </span>
    @elseif($isSettled && $remaining === 0)
    <span class="badge bg-success-subtle text-success border border-success-subtle fs-12 fw-bold" title="{{ __('The receipts cover the invoice total.') }}">
    <i class="ri-checkbox-circle-line me-0.5"></i>{{ __('Settled') }}
    </span>
    @elseif($hasReceipt)
    {{-- Receipt on file but not verified (or short): never call this "Paid". --}}
    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-12 fw-bold"
    title="{{ __('Short by :amount').' '.config('app.currency.symbol') }}">
    <i class="ri-error-warning-line me-0.5"></i>{{ $ratio }}%
    </span>
    <small class="text-muted font-fanum d-block">
    {{ __('Remaining Balance') }}: {{ number_format($remaining) }}
    </small>
    @else
    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-12">
    <i class="ri-time-line me-0.5"></i>{{ __('Unpaid') }}
    </span>
    @endif
    @if($isOffline && $receiptCount > 0)
    <small class="text-muted d-block">
    {{ $receiptCount }} {{ __('receipt(s)') }}
    </small>
    @endif
    @break
    @case('delivery_method')
    @php
    $isPickup = $item->isPickup();
    $courierName = $item->activeDelivery?->courier?->name;
    $requiresCode = $item->requiresDeliveryCode();
    @endphp
    @if($isPickup)
    <span class="badge bg-success-subtle text-success border border-success-subtle fs-12">
    <i class="ri-store-2-line me-0.5"></i>{{ __('Store pickup') }}
    </span>
    @elseif($courierName)
    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-12">
    <i class="ri-motorbike-line me-0.5"></i>{{ __('Courier') }}
    </span>
    <small class="d-block text-dark">{{ $courierName }}</small>
    @elseif($requiresCode)
    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fs-12">
    <i class="ri-truck-line me-0.5"></i>{{ __('Pending courier') }}
    </span>
    @else
    <span class="badge bg-light text-dark border border-secondary-subtle fs-12">
    <i class="ri-mail-box-line me-0.5"></i>{{ $item->transport?->title ?? __('Postal service') }}
    </span>
    @endif
    @break
    @case('category_id')
    @if($item->category != null)
    <a href="{{route('admin.category.edit',$item->category?->slug)}}">
    {{ $item->category?->name??'-' }}
    </a>
    @else
    {{__("Removed")}}
    @endif
    @break
    @case('state_id')
    @if($item->state != null)
    <a href="{{route('admin.state.edit',$item->state?->id)}}">
    {{ $item->state?->name??'-' }}
    </a>
    @else
    {{__("Removed")}}
    @endif
    @break
    @case('product_id')
    @if($item->product != null)
    <a href="{{route('admin.product.edit',$item->product?->slug)}}">
    {{ $item->product?->name??'-' }}
    </a>
    @else
    {{__("Removed")}}
    @endif
    @break
    @case('evaluation_id')
    @if($item->evaluation != null)
    <a href="{{route('admin.evaluation.edit',$item->evaluation_id)}}">
    {{ $item->evaluation?->title??'-' }}
    </a>
    @else
    {{__("Removed")}}
    @endif
    @break
    @case('submitted_at')
    {{ $item->submittedAtLabel() }}
    @break
    @case('expire')
    @case('created_at')
    @case('updated_at')
    {{$item->$col?->ldate("Y-m-d H:i")??'-'}}
    @break
    @case('has_purchase')
    {{ $item->purchaseLabel() }}
    @break
    @case('metal_type')
    <span class="badge @if(($item->metal_type ?? 'gold') == 'silver') bg-secondary text-white @else bg-warning text-dark @endif">
    @if(($item->metal_type ?? 'gold') == 'silver')
    {{ __('Silver') }}
    @else
    {{ __('Gold') }} ({{ $item->getGoldKarat()->label() }})
    @endif
    </span>
    @break
    @case('karat')
    @if(($item->metal_type ?? 'gold') !== 'silver')
    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
    {{ $item->getGoldKarat()->label() }}
    </span>
    @else
    <span class="text-muted">-</span>
    @endif
    @break
    @case('target_group')
    @php
    $tgMap = [
    'women' => __("Women's"),
    'men' => __("Men's"),
    'children' => __("Children's"),
    'unisex' => __("Unisex"),
    ];
    $tgVal = $tgMap[$item->target_group ?? 'unisex'] ?? $item->target_group;
    @endphp
    <span class="badge bg-info-subtle text-info border border-info-subtle">
    {{ $tgVal }}
    </span>
    @break
    @case('weight')
    @php
    $firstAvailable = method_exists($item, 'firstAvailableQuantity') ? $item->firstAvailableQuantity() : null;
    $pieceWeight = $firstAvailable?->weight ?? ($item->weight ?? 0);
    @endphp
    <span>~ {{ number_format((float) $pieceWeight, 3) }} {{__('g')}}</span>
    @break
    @case('total_weight')
    <span class="fw-semibold text-dark">{{ \App\Services\AdminDashboardStats::formatWeight($item->total_weight ?? ($item->weight ?? 0)) }}</span>
    <small class="text-muted fs-12">{{__('g')}}</small>
    @break
    @case('price')
    <span class="fw-bold text-dark">{{ number_format($item->price ?? 0) }}</span>
    <small class="text-muted fs-12">{{ __('Toman') }}</small>
    @break
    @case('total_price')
    <span class="fw-bold text-dark">{{ number_format($item->total_price ?? ($item->price ?? 0)) }}</span>
    <small class="text-muted fs-12">{{ __('Toman') }}</small>
    @break
    @case('sku')
    <code class="fw-bold text-primary font-monospace">{{ $item->sku ?: '-' }}</code>
    @break
    @case('total_ordered')
    @php
    $totalOrd = method_exists($item, 'totalOrderedCount') ? $item->totalOrderedCount() : ($item->total_ordered_count ?? 0);
    @endphp
    <div class="d-inline-flex align-items-center gap-1">
    <span class="badge bg-light text-dark border border-secondary-subtle fs-12 fw-semibold">
    {{ number_format($totalOrd) }} {{ __('pieces') }}
    </span>
    @if(request()->routeIs('admin.stock.*') && $totalOrd > 0)
    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1 fs-11 view-pieces-btn" data-product-id="{{ $item->id }}" data-product-name="{{ $item->name }}" title="{{ __('View piece codes') }}">
    <i class="ri-eye-line"></i>
    </button>
    @endif
    </div>
    @break
    @case('scrapped_pieces')
    @php
    $scrappedCnt = method_exists($item, 'scrappedPiecesCount') ? $item->scrappedPiecesCount() : ($item->scrapped_pieces_count ?? 0);
    @endphp
    @if($scrappedCnt > 0)
    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-12 fw-bold" title="{{ __('Defective or scrapped pieces') }}">
    <i class="ri-fire-line me-0.5"></i>{{ number_format($scrappedCnt) }} {{ __('pieces') }}
    </span>
    @else
    <span class="text-muted fs-12">—</span>
    @endif
    @break
    @case('sold_pieces')
    @php
    $soldCnt = method_exists($item, 'soldPiecesCount') ? $item->soldPiecesCount() : ($item->sold_pieces_count ?? 0);
    @endphp
    @if($soldCnt > 0)
    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-12 fw-semibold">
    <i class="ri-shopping-bag-3-line me-0.5"></i>{{ number_format($soldCnt) }} {{ __('pieces') }}
    </span>
    @else
    <span class="text-muted fs-12">—</span>
    @endif
    @break
    @case('stock_quantity')
    @php
    $isLowStock = method_exists($item, 'isLowStock')
    ? $item->isLowStock()
    : (($item->min_stock_level ?? 0) > 0 && ($item->stock_quantity ?? 0) < ($item->min_stock_level ?? 0));
    @endphp
    <div class="d-inline-flex align-items-center gap-1.5 flex-wrap">
    <span class="@if($isLowStock) text-danger fw-bold @endif">
    {{ number_format($item->stock_quantity ?? 0) }}
    </span>
    <small class="text-muted fs-12">{{ __('pieces') }}</small>
    @if($isLowStock)
    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="{{__('Below minimum stock (:min)', ['min' => $item->min_stock_level])}}">
    <i class="ri-alarm-warning-line me-1"></i>{{__('Low stock')}}
    </span>
    @endif
    @if(method_exists($item, 'isBelowBuyPrice') && $item->isBelowBuyPrice())
    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" title="{{__('Selling price (:price) is below purchase price (:buy_price)', ['price' => number_format($item->price ?? 0), 'buy_price' => number_format($item->buy_price ?? 0)])}}">
    <i class="ri-error-warning-line me-1"></i>{{__('Below purchase price')}}
    </span>
    @endif
    </div>
    @break
    @case('icon')
    <i class="{{$item->$col}}"></i>
    @break
    @default
    @if(substr($col,0,3) == 'is_')
    @if($item->$col == 1)
    <i class="ri-check-line"></i>
    @endif
    @elseif(gettype($item->$col) == 'integer')
    {{number_format($item->$col)}}
    @elseif($col === 'role')
    {{ __((string) $item->$col) }}
    @elseif(strpos($col,'_type'))
    {{ __(str_replace('App\\Models\\', '', (string) $item->$col)) }}
    @else
    {{$item->$col}}
    @endif
    @endswitch
