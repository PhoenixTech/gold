<div class="row g-3">
    <div class="col-lg-4">
        <div class="form-group">
            <label for="price" class="fw-semibold">{{__('Base price')}}</label>
            <currency-input xname="price" xid="price" @error('price')
            :err="true" @enderror xtitle="{{__('Base price')}}"
                            :xvalue="{{old('price',$item->price??null)}}"></currency-input>
            <small class="text-muted">{{__('Overwritten by the lowest available stock piece price.')}}</small>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-group">
            <label for="buy_price" class="fw-semibold">{{__('Purchase price')}}</label>
            <currency-input xname="buy_price" xid="buy_price" @error('buy_price')
            :err="true" @enderror :xvalue="{{old('buy_price',$item->buy_price??0)}}"></currency-input>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-group">
            <label for="addon" class="fw-semibold">{{__('Addon price')}}</label>
            <currency-input xname="addon" xid="addon" @error('addon')
            :err="true" @enderror :xvalue="{{old('addon',$item->addon??0)}}"></currency-input>
        </div>
    </div>

    <div class="col-12">
        <div class="border rounded-3 p-3 bg-light-subtle">
            <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="ri-vip-diamond-line text-warning"></i>
                {{__("Gold & Silver Specifications")}}
            </h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="weight" class="fw-semibold">{{__('Reference weight (grams)')}}</label>
                        <input name="weight" type="number" step="0.001" min="0" id="weight"
                               class="form-control @error('weight') is-invalid @enderror"
                               placeholder="0.000"
                               value="{{old('weight', $item->weight ?? 0)}}"/>
                        <small class="text-muted">{{__('Reference weight hint')}}</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="labor_charge_1" class="fw-semibold">{{__('Wage percent')}}</label>
                        <currency-input xname="labor_charge_1" xid="labor_charge_1" @error('labor_charge_1') :err="true" @enderror :xvalue="{{old('labor_charge_1', $item->labor_charge_1 ?? $item->wage ?? 15)}}"></currency-input>
                        <small class="text-muted">{{__('Used as wage/fee percent in price formula.')}}</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="profit" class="fw-semibold">{{__('Profit (%)')}}</label>
                        <input name="profit" type="number" step="0.01" min="0" max="100" id="profit"
                               class="form-control @error('profit') is-invalid @enderror"
                               placeholder="7"
                               value="{{old('profit', $item->profit ?? 7)}}"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="tax" class="fw-semibold">{{__('Tax (%)')}}</label>
                        <input name="tax" type="number" step="0.01" min="0" max="100" id="tax"
                               class="form-control @error('tax') is-invalid @enderror"
                               placeholder="9"
                               value="{{old('tax', $item->tax ?? 9)}}"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="labor_charge_2" class="fw-semibold">{{__('Labor charge 2')}}</label>
                        <currency-input xname="labor_charge_2" xid="labor_charge_2" @error('labor_charge_2') :err="true" @enderror :xvalue="{{old('labor_charge_2', $item->labor_charge_2 ?? 0)}}"></currency-input>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="labor_charge_3" class="fw-semibold">{{__('Labor charge 3')}}</label>
                        <currency-input xname="labor_charge_3" xid="labor_charge_3" @error('labor_charge_3') :err="true" @enderror :xvalue="{{old('labor_charge_3', $item->labor_charge_3 ?? 0)}}"></currency-input>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
