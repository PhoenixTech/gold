{{-- Tab: how the campaign's product set is built.
     @var \App\Models\Campaign|null $item
     @var array{products: \Illuminate\Support\Collection, pinnedIds: array, pinned: int, excluded: int, occasions: int, final: int}|null $preview
     @var \Illuminate\Support\Collection $productOptions
     @var array $occasions
     @var array $selectedOccasions
     @var string $includedValue
     @var string $excludedValue --}}

<div class="alert alert-info border border-info-subtle d-flex align-items-start gap-2 py-2 mb-4">
    <i class="ri-lightbulb-flash-line fs-5"></i>
    <div>
        {{__("The tile is filled by three inputs combined: your included products first, then every published product carrying the selected occasions, minus everything you excluded.")}}
    </div>
</div>

<div class="p-3 bg-white rounded-3 border border-light-subtle mb-4">
    <label class="fw-semibold text-dark d-flex align-items-center gap-1.5 mb-1">
        <i class="ri-gift-line text-danger"></i>
        <span>{{__("Occasions")}}</span>
    </label>
    <p class="text-muted fs-13 mb-3">
        {{__("Every published product carrying any of these occasions is added to the tile automatically. Tag products from the product form.")}}
    </p>

    @error('occasions') <div class="text-danger small mb-2">{{$message}}</div> @enderror

    <div class="row g-2">
        @foreach($occasions as $key => $label)
            <div class="col-6 col-sm-4 col-lg-3">
                <div class="form-check m-0">
                    <input class="form-check-input" type="checkbox" name="occasions[]"
                           id="campaign_occasion_{{$key}}" value="{{$key}}"
                           @checked(in_array($key, (array) $selectedOccasions))>
                    <label class="form-check-label fs-13 text-dark cursor-pointer" for="campaign_occasion_{{$key}}">{{$label}}</label>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="form-group mb-4">
    <label class="fw-semibold text-dark d-flex align-items-center gap-1.5 mb-1">
        <i class="ri-checkbox-circle-line text-success"></i>
        <span>{{__("Include specific products")}}</span>
    </label>
    <p class="text-muted fs-13 mb-2">
        {{__("Pinned to the front of the tile, in the order you pick them. Useful for a hero piece.")}}
    </p>
    <searchable-multi-select
        @error('included_products') :err="true" @enderror
    :items='@json($productOptions)'
        title-field="name"
        value-field="id"
        xlang="{{config('app.locale')}}"
        xid="included_products"
        xname="included_products"
        :xvalue='{{$includedValue}}'
        :close-on-Select="false"></searchable-multi-select>
    @error('included_products') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>

<div class="form-group mb-4">
    <label class="fw-semibold text-dark d-flex align-items-center gap-1.5 mb-1">
        <i class="ri-forbid-circle-line text-danger"></i>
        <span>{{__("Exclude products")}}</span>
    </label>
    <p class="text-muted fs-13 mb-2">
        {{__("Held back from the tile, even when their occasion matches — sold out pieces, or anything you do not want advertised.")}}
    </p>
    <searchable-multi-select
        @error('excluded_products') :err="true" @enderror
    :items='@json($productOptions)'
        title-field="name"
        value-field="id"
        xlang="{{config('app.locale')}}"
        xid="excluded_products"
        xname="excluded_products"
        :xvalue='{{$excludedValue}}'
        :close-on-Select="false"></searchable-multi-select>
    @error('excluded_products') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>

@if($preview)
    <div class="p-3 bg-white rounded-3 border border-success-subtle">
        <label class="fw-semibold text-dark d-flex align-items-center gap-1.5 mb-2">
            <i class="ri-eye-line text-success"></i>
            <span>{{__("What the tile will show (gold tab)")}}</span>
        </label>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">
                {{toPersianDigits($preview['final'])}} {{__("in the tile")}}
            </span>
            <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                {{toPersianDigits($preview['occasions'])}} {{__("from occasions")}}
            </span>
            <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                {{toPersianDigits($preview['pinned'])}} {{__("included")}}
            </span>
            <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">
                {{toPersianDigits($preview['excluded'])}} {{__("excluded")}}
            </span>
        </div>

        @if($preview['products']->isEmpty())
            <div class="alert alert-danger border border-danger-subtle shadow-sm p-3 mb-0 rounded-3">
                <i class="ri-error-warning-line"></i>
                {{__("No product matches, so the tile will be hidden. Tag products with one of the selected occasions, or include some by hand.")}}
            </div>
        @else
            <p class="text-muted fs-13 mb-2">
                {{__("Included products come first, then the occasion matches, minus everything you excluded.")}}
            </p>
            <div class="d-flex flex-wrap gap-2">
                @foreach($preview['products'] as $previewProduct)
                    <span class="badge bg-light text-dark border border-secondary-subtle fw-normal d-inline-flex align-items-center gap-1">
                        <img src="{{$previewProduct->thumbUrl()}}" alt="" class="rounded-1" style="width: 18px; height: 18px; object-fit: cover;">
                        <span class="fs-12">{{$previewProduct->name}}</span>
                        @if(in_array($previewProduct->getKey(), $preview['pinnedIds'], true))
                            <i class="ri-pushpin-fill text-success" title="{{__("Included by hand")}}"></i>
                        @else
                            <i class="ri-sparkling-fill text-primary" title="{{__("Matched by occasion")}}"></i>
                        @endif
                    </span>
                @endforeach
            </div>

            @if(! $item->isLive())
                <p class="text-muted fs-13 mb-0 mt-3">
                    <i class="ri-information-line"></i>
                    {{__("The tile stays hidden until the status is published and the start date is reached.")}}
                </p>
            @endif
        @endif
    </div>
@endif