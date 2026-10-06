@extends('admin.templates.panel-form-template')

@section('title')
    @if(isset($item))
        {{__("Edit campaign")}} [{{$item->name}}]
    @else
        {{__("Add new campaign")}}
    @endif -
@endsection

@php
    $occasions = \App\Enums\Occasion::options();
    $selectedOccasions = old('occasions', $item->occasions ?? []);
    $selectedMetals = old('metal_scope', $item->metal_scope ?? \App\Enums\MetalType::values());
    $clashes = $clashes ?? collect();
    $preview = $preview ?? null;

    $tabs = [
        ['id' => 'content', 'icon' => 'ri-text', 'label' => __('Content')],
        ['id' => 'products', 'icon' => 'ri-shopping-bag-3-line', 'label' => __('Products')],
        ['id' => 'schedule', 'icon' => 'ri-calendar-event-line', 'label' => __('Schedule')],
        ['id' => 'publishing', 'icon' => 'ri-send-plane-line', 'label' => __('Publishing')],
    ];
@endphp

@section('form')
    @include('components.err')

    {{-- A schedule collision silently costs the slot, so it stays above the tabs. --}}
    @if($clashes->isNotEmpty())
        <div class="alert alert-warning border border-warning-subtle shadow-sm d-flex align-items-start gap-2 p-3 mb-4 rounded-3">
            <i class="ri-alarm-warning-line fs-5"></i>
            <div>
                {{__("These campaigns overlap your schedule and have an equal or higher priority, so yours will not be shown during the overlap:")}}
                <ul class="mb-0 mt-1">
                    @foreach($clashes as $clash)
                        <li>
                            <a href="{{route('admin.campaign.edit', $clash->slug)}}" target="_blank" class="fw-bold">{{$clash->name}}</a>
                            — {{$clash->scheduleLabel()}} ({{__("priority")}} {{$clash->priority}})
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="item-list">
        <div class="p-3 pb-0">
            <ul class="nav nav-tabs setting-tabs" role="tablist">
                @foreach($tabs as $i => $tab)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link @if($i === 0) active @endif"
                                data-bs-toggle="tab"
                                data-bs-target="#tab-{{$tab['id']}}"
                                type="button"
                                role="tab">
                            <i class="{{$tab['icon']}}"></i>
                            {{$tab['label']}}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="tab-content p-3">
            <div class="tab-pane fade show active" id="tab-content" role="tabpanel">
                @include('admin.campaigns.sub-pages.campaign-content')
            </div>

            <div class="tab-pane fade" id="tab-products" role="tabpanel">
                @include('admin.campaigns.sub-pages.campaign-products')
            </div>

            <div class="tab-pane fade" id="tab-schedule" role="tabpanel">
                @include('admin.campaigns.sub-pages.campaign-schedule')
            </div>

            <div class="tab-pane fade" id="tab-publishing" role="tabpanel">
                @include('admin.campaigns.sub-pages.campaign-publishing')
            </div>
        </div>

        {{-- One action bar for every tab. --}}
        <div class="p-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="text-muted fs-13">
                <i class="ri-save-line"></i>
                {{__("Changes go live on the home page as soon as you save.")}}
            </span>
            <div class="d-flex gap-2">
                <a href="{{route('admin.campaign.index')}}" class="btn btn-outline-secondary">{{__("Cancel")}}</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="ri-save-3-line me-1"></i>
                    {{__("Save")}}
                </button>
            </div>
        </div>
    </div>
@endsection

@section('js-content')
    <script>
        // After a failed validation, jump to the tab that actually holds the
        // first error instead of leaving the admin on the first tab wondering
        // what went wrong.
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.bootstrap === 'undefined') {
                return;
            }

            var invalid = document.querySelector('.tab-content .is-invalid, .tab-content [aria-invalid="true"]');

            if (!invalid) {
                return;
            }

            var pane = invalid.closest('.tab-pane');

            if (!pane) {
                return;
            }

            var trigger = document.querySelector('[data-bs-target="#' + pane.id + '"]');

            if (trigger) {
                window.bootstrap.Tab.getOrCreateInstance(trigger).show();
            }
        });
    </script>
@endsection