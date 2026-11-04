@if($item->rejectedReceipts->isNotEmpty())
    <details class="border rounded-3 mb-3">
        <summary class="p-2.5 fs-13 fw-semibold text-muted">
            <i class="ri-history-line me-1"></i>{{ __('Rejected receipts') }}
            <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle font-fanum ms-1">{{ $item->rejectedReceipts->count() }}</span>
        </summary>
        <ul class="list-group list-group-flush fs-13">
            @foreach($item->rejectedReceipts as $rejected)
                <li class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span class="font-fanum fw-semibold text-dark">{{ number_format((int) $rejected->amount) }} {{ __('Toman') }}</span>
                    <span class="text-muted">{{ $rejected->rejected_reason }}</span>
                    <span class="text-muted font-fanum fs-12">{{ \App\Models\Invoice::formatPersianDateTime($rejected->rejected_at) }}</span>
                    <a href="{{ $rejected->url() }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary py-0 px-2 fs-12">
                        <i class="ri-external-link-line"></i> {{ __('View') }}
                    </a>
                </li>
            @endforeach
        </ul>
    </details>
@endif
