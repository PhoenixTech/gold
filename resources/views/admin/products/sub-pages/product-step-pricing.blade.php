<div class="row g-3">
    <div class="col-lg-6">
        <div class="form-group">
            <label for="buy_price" class="fw-semibold">{{__('Purchase price')}}</label>
            <currency-input xname="buy_price" xid="buy_price" @error('buy_price')
            :err="true" @enderror :xvalue="{{old('buy_price',$item->buy_price??0)}}"></currency-input>
            <small class="text-muted">{{__('Minimum price floor. If dynamic price is below this, product cannot be sold.')}}</small>
        </div>
    </div>
    <div class="col-lg-6">
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
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="labor_charge_1" class="fw-semibold">{{__('Labor charge 1')}}</label>
                        <currency-input xname="labor_charge_1" xid="labor_charge_1" @error('labor_charge_1') :err="true" @enderror :xvalue="{{old('labor_charge_1', $item->labor_charge_1 ?? $item->wage ?? 15)}}"></currency-input>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="labor_charge_2" class="fw-semibold">{{__('Labor charge 2')}}</label>
                        <currency-input xname="labor_charge_2" xid="labor_charge_2" @error('labor_charge_2') :err="true" @enderror :xvalue="{{old('labor_charge_2', $item->labor_charge_2 ?? 0)}}"></currency-input>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="labor_charge_3" class="fw-semibold">{{__('Labor charge 3')}}</label>
                        <currency-input xname="labor_charge_3" xid="labor_charge_3" @error('labor_charge_3') :err="true" @enderror :xvalue="{{old('labor_charge_3', $item->labor_charge_3 ?? 0)}}"></currency-input>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="fw-semibold d-flex align-items-center justify-content-between">
                            <span>{{__('Total wage')}}</span>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                                <i class="ri-lock-line me-1"></i>{{__('Auto-calculated')}}
                            </span>
                        </label>
                        <div class="input-group">
                            <input type="text" id="total_labor_charge"
                                   class="form-control bg-light text-dark fw-bold border-start-0 border-end-0"
                                   readonly
                                   tabindex="-1"
                                   style="cursor: not-allowed;"
                                   value="0">
                            <span class="input-group-text bg-light text-muted border-start-0 fs-12">%</span>
                        </div>
                        <small class="text-muted d-flex align-items-center gap-1 mt-1">
                            <i class="ri-information-line text-primary"></i>
                            {{__('Used as wage/fee percent in price formula.')}}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function parseCharge(selector) {
        const el = document.querySelector(selector);
        if (!el) return 0;
        const raw = String(el.value || '').split(',').join('').trim();
        const n = parseFloat(raw);
        return isNaN(n) ? 0 : n;
    }

    function updateTotalLaborCharge() {
        const sum = parseCharge('input[name="labor_charge_1"]')
            + parseCharge('input[name="labor_charge_2"]')
            + parseCharge('input[name="labor_charge_3"]');
        const displayEl = document.getElementById('total_labor_charge');
        if (displayEl) {
            displayEl.value = sum.toLocaleString('fa-IR', { maximumFractionDigits: 2 });
        }
    }

    const form = document.querySelector('.product-form');
    if (form) {
        form.addEventListener('input', updateTotalLaborCharge);
        form.addEventListener('change', updateTotalLaborCharge);
        form.addEventListener('keyup', updateTotalLaborCharge);
    }
    updateTotalLaborCharge();
});
</script>
