@php
    use App\Models\Setting;

    $stockItems = old('stock_items');
    if ($stockItems === null) {
        $stockItems = isset($item)
            ? $item->quantities()->get(['id', 'weight', 'code', 'count', 'price', 'image'])->toArray()
            : [];
    } elseif (is_string($stockItems)) {
        $stockItems = json_decode($stockItems, true) ?: [];
    }

    $goldSetting = Setting::query()->where('key', 'gold')->first();
    $silverSetting = Setting::query()->where('key', 'silver')->first();
    $minimumPercentSetting = Setting::query()->where('key', 'min')->first();
    $goldMarketPrice = (int) str_replace(',', '', (string) ($goldSetting?->value ?: $goldSetting?->raw ?: 0));
    $silverMarketPrice = (int) str_replace(',', '', (string) ($silverSetting?->value ?: $silverSetting?->raw ?: 0));
    $minimumPercent = (float) str_replace(',', '', (string) ($minimumPercentSetting?->value ?: $minimumPercentSetting?->raw ?: 100));
    if ($minimumPercent <= 0) {
        $minimumPercent = 100;
    }
@endphp

<div class="row g-4">
    <div class="col-md-4">
        <div class="form-group">
            <label for="stock_quantity" class="fw-semibold d-flex align-items-center justify-content-between">
                <span>{{__('Stock quantity')}}</span>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                    <i class="ri-lock-line me-1"></i>{{__('Auto-calculated')}}
                </span>
            </label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0">
                    <i class="ri-archive-line"></i>
                </span>
                <input type="number" id="stock_quantity" name="stock_quantity"
                       value="{{old('stock_quantity',$item->stock_quantity??0)}}"
                       placeholder="{{__('Stock quantity')}}"
                       class="form-control bg-light text-dark fw-bold border-start-0 border-end-0"
                       readonly
                       tabindex="-1"
                       style="cursor: not-allowed;">
                <span class="input-group-text bg-light text-muted border-start-0 fs-12">
                    {{__('pieces')}}
                </span>
            </div>
            <small class="text-muted d-flex align-items-center gap-1 mt-1">
                <i class="ri-information-line text-primary"></i>
                {{__('Auto-calculated from available stock pieces.')}}
            </small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="min_stock_level" class="fw-semibold">{{__('Minimum stock level')}}</label>
            <input type="number" id="min_stock_level" name="min_stock_level"
                   value="{{old('min_stock_level',$item->min_stock_level??0)}}"
                   placeholder="{{__('Minimum stock level')}}"
                   class="form-control">
            <small class="text-muted">{{__('If stock is below this number, we will notify you.')}}</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="stock_status" class="fw-semibold">{{__("Status")}}</label>
            <select class="form-control" name="stock_status" id="stock_status">
                @foreach(\App\Models\Product::$stock_status as $k => $v)
                    <option
                        value="{{ $v }}" {{ old("stock_status", $item->stock_status??null) == $v ? "selected" : "" }}>{{ __($v) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-12">
        <stock-items-input
            xname="stock_items"
            :xvalue='@json($stockItems)'
            :product-sku='@json($item->sku ?? "")'
            :gold-price="{{ $goldMarketPrice }}"
            :silver-price="{{ $silverMarketPrice }}"
            :minimum-percent="{{ $minimumPercent }}"
            title="{{__('Stock pieces')}}"
            subtitle="{{__('Each row is one unique piece with its own weight and price.')}}"
            add-label="{{__('Add piece')}}"
            empty-label="{{__('No stock pieces yet.')}}"
            sku-label="{{__('Piece SKU')}}"
            weight-label="{{__('Weight (grams)')}}"
            price-label="{{__('Price')}}"
            status-label="{{__('Status')}}"
            available-label="{{__('Available')}}"
            sold-label="{{__('Sold')}}"
            remove-label="{{__('Remove')}}"
            live-hint="{{__('Calculated from current weight and pricing settings.')}}"
            breakdown-title="{{__('Price calculation breakdown')}}"
            final-label="{{__('Final price')}}"
            need-weight-hint="{{__('Enter weight to see calculation details.')}}"
            metal-gold-label="{{__('Gold')}}"
            metal-silver-label="{{__('Silver')}}"
            search-placeholder="{{__('Search SKU or weight')}}"
        ></stock-items-input>
    </div>
</div>

