@php
    $totalSteps = count($steps);
@endphp

<div class="item-list p-3 mb-4 shadow-sm">
    <ol class="list-unstyled d-flex align-items-center justify-content-between mb-0 p-0" aria-label="{{ __('Progress') }}">
        @foreach($steps as $index => $step)
            @php
                $state = $step['state'];
                $hint = null;
                if ($state === 'waiting') {
                    $hint = $isOutForDelivery ? __('Waiting for courier') : __('Waiting on customer');
                } elseif ($state === 'current') {
                    $hint = __('Your action');
                }
            @endphp
            <li class="d-flex align-items-center gap-2 {{ $index < $totalSteps - 1 ? 'flex-grow-1' : '' }}" @if(in_array($state, ['current', 'waiting'], true)) aria-current="step" @endif>
                @if($state === 'done')
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-circle bg-success text-white shadow-sm d-inline-flex align-items-center justify-content-center" style="width: 2rem; height: 2rem;">
                            <i class="ri-check-line fs-14"></i>
                        </span>
                        <span class="fw-semibold text-dark fs-13 d-none d-sm-inline">{{ $step['label'] }}</span>
                    </div>
                @elseif($state === 'current')
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-circle bg-primary text-white shadow-sm d-inline-flex align-items-center justify-content-center" style="width: 2rem; height: 2rem;">
                            {{ $step['number'] }}
                        </span>
                        <div>
                            <span class="fw-bold text-dark fs-13">{{ $step['label'] }}</span>
                            @if($hint)
                                <small class="text-primary d-block fs-11 fw-normal">{{ $hint }}</small>
                            @endif
                        </div>
                    </div>
                @elseif($state === 'waiting')
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-circle bg-warning text-dark shadow-sm d-inline-flex align-items-center justify-content-center" style="width: 2rem; height: 2rem;">
                            <i class="ri-time-line fs-14"></i>
                        </span>
                        <div>
                            <span class="fw-bold text-dark fs-13">{{ $step['label'] }}</span>
                            @if($hint)
                                <small class="text-warning-emphasis d-block fs-11 fw-normal">{{ $hint }}</small>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-circle bg-light text-muted border d-inline-flex align-items-center justify-content-center" style="width: 2rem; height: 2rem;">
                            {{ $step['number'] }}
                        </span>
                        <span class="text-muted fs-13 d-none d-sm-inline">{{ $step['label'] }}</span>
                    </div>
                @endif

                @if($index < $totalSteps - 1)
                    <div class="flex-grow-1 mx-2 text-center text-muted opacity-50 d-none d-md-block">
                        <i class="ri-arrow-left-s-line fs-18"></i>
                    </div>
                @endif
            </li>
        @endforeach
    </ol>
</div>
