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
    $goldPrice = (int) str_replace(',', '', (string) ($goldSetting?->value ?: $goldSetting?->raw ?: 0));
    $silverPrice = (int) str_replace(',', '', (string) ($silverSetting?->value ?: $silverSetting?->raw ?: 0));
@endphp

<div class="row g-4">
    <div class="col-12">
        <stock-items-input
            xname="stock_items"
            :xvalue='@json($stockItems)'
            :gold-price="{{ $goldPrice }}"
            :silver-price="{{ $silverPrice }}"
            title="{{__('Stock pieces')}}"
            subtitle="{{__('Each row is one unique piece with its own weight and price.')}}"
            add-label="{{__('Add piece')}}"
            empty-label="{{__('No stock pieces yet.')}}"
            weight-label="{{__('Weight (grams)')}}"
            code-label="{{__('Code')}}"
            code-placeholder="{{__('Optional')}}"
            price-label="{{__('Price')}}"
            status-label="{{__('Status')}}"
            available-label="{{__('Available')}}"
            sold-label="{{__('Sold')}}"
            remove-label="{{__('Remove')}}"
            live-price-label="{{__('Live calculated price')}}"
            live-hint="{{__('Calculated from current weight and pricing settings.')}}"
            breakdown-title="{{__('Price calculation breakdown')}}"
            final-label="{{__('Final price')}}"
            need-weight-hint="{{__('Enter weight to see calculation details.')}}"
            metal-gold-label="{{__('Gold')}}"
            metal-silver-label="{{__('Silver')}}"
        ></stock-items-input>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label for="stock_quantity" class="fw-semibold">{{__('Stock quantity')}}</label>
            <input type="number" id="stock_quantity" name="stock_quantity"
                   value="{{old('stock_quantity',$item->stock_quantity??0)}}"
                   placeholder="{{__('Stock quantity')}}"
                   class="form-control"
                   readonly>
            <small class="text-muted">{{__('Auto-calculated from available stock pieces.')}}</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="min_stock_level" class="fw-semibold">{{__('Minimum stock level')}}</label>
            <input type="number" id="min_stock_level" name="min_stock_level"
                   value="{{old('min_stock_level',$item->min_stock_level??0)}}"
                   placeholder="{{__('Minimum stock level')}}"
                   class="form-control">
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
</div>
