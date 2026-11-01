<div class="p-3 bg-white rounded-3 border border-light-subtle mb-4">
    <label class="fw-semibold text-dark d-flex align-items-center gap-1.5 mb-2">
        <i class="ri-gift-line text-danger"></i>
        <span>{{__("Occasions")}}</span>
    </label>

    @error('occasions') <div class="text-danger small mb-2">{{$message}}</div> @enderror

    <div class="row g-2">
        @foreach($occasions as $key => $label)
            <div class="col-6 col-sm-4 col-lg-3">
                <div class="form-check m-0">
                    <input class="form-check-input" type="checkbox" name="occasions[]"
                           id="campaign_occasion_{{$key}}" value="{{$key}}"
                           @checked(in_array($key, (array) $selectedOccasions))>
                    <label class="form-check-label fs-13 text-dark" for="campaign_occasion_{{$key}}">{{$label}}</label>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="form-group mb-4">
    <label class="fw-semibold text-dark d-flex align-items-center gap-1.5 mb-2">
        <i class="ri-checkbox-circle-line text-success"></i>
        <span>{{__("Include specific products")}}</span>
    </label>
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
    <label class="fw-semibold text-dark d-flex align-items-center gap-1.5 mb-2">
        <i class="ri-forbid-circle-line text-danger"></i>
        <span>{{__("Exclude products")}}</span>
    </label>
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
                <i class="ri-error-warning-line me-1"></i>
                {{__("No product matches, so the tile will be hidden. Tag products with one of the selected occasions, or include some by hand.")}}
            </div>
        @else
            <div class="d-flex flex-wrap gap-2">
                @foreach($preview['products'] as $previewProduct)
                    <span class="badge bg-light-subtle text-body border border-secondary-subtle fw-normal d-inline-flex align-items-center gap-1">
                        <img src="{{$previewProduct->thumbUrl()}}" alt="{{$previewProduct->name}}" width="18" height="18" class="rounded-1" style="width: 18px; height: 18px; object-fit: cover;">
                        <span class="fs-12">{{$previewProduct->name}}</span>
                        @if(in_array($previewProduct->getKey(), $preview['pinnedIds'], true))
                            <i class="ri-pushpin-fill text-success" title="{{__("Included by hand")}}"></i>
                        @else
                            <i class="ri-price-tag-3-fill text-primary" title="{{__("Matched by occasion")}}"></i>
                        @endif
                    </span>
                @endforeach
            </div>
        @endif
    </div>
@endif
