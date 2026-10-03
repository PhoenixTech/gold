<ol class="list-unstyled d-flex align-items-start gap-2 item-list p-3 mb-3">
    @foreach($steps as $step)
        @php
            $badge = match ($step['state']) {
                'done' => 'bg-success text-white',
                'current' => 'bg-primary text-white',
                'waiting' => 'bg-warning text-dark',
                default => 'bg-secondary-subtle text-secondary',
            };
            $hint = null;
            if ($step['state'] === 'waiting') {
                $hint = $isOutForDelivery ? __('Waiting for courier') : __('Waiting on customer');
            } elseif ($step['state'] === 'current') {
                $hint = __('Your action');
            }
        @endphp
        <li class="flex-fill text-center" @if(in_array($step['state'], ['current', 'waiting'], true)) aria-current="step" @endif>
            <span class="badge rounded-circle {{ $badge }} d-inline-flex align-items-center justify-content-center fs-6" style="width: 2.25rem; height: 2.25rem;">
                @if($step['state'] === 'done')
                    <i class="ri-check-line"></i>
                @else
                    {{ $step['number'] }}
                @endif
            </span>
            <div class="fw-semibold mt-1 {{ $step['state'] === 'todo' ? 'text-muted' : 'text-dark' }}">{{ $step['label'] }}</div>
            <div class="fs-12 text-muted" style="min-height: 1.2rem;">{{ $hint }}</div>
        </li>
    @endforeach
</ol>
