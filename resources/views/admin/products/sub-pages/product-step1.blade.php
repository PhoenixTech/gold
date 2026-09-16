<div class="row g-3">
    <div class="col-md-6">
        <div class="form-group">
            <label for="name" class="fw-semibold">{{__('Name')}}</label>
            <input name="name" type="text"
                   id="name"
                   class="form-control @error('name') is-invalid @enderror"
                   placeholder="{{__('Name')}}"
                   value="{{old('name',$item->name??null)}}"/>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="slug" class="fw-semibold">{{__('Slug')}}</label>
            <input name="slug" type="text"
                   id="slug"
                   class="form-control @error('slug') is-invalid @enderror"
                   placeholder="{{__('Slug')}}"
                   value="{{old('slug',$item->slug??null)}}"/>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="form-group">
            <label for="categoryId" class="fw-semibold">{{__('Main product category')}}</label>
            <searchable-select
                vuex-dispatch="updateCategory"
                @error('category_id') :err="true" @enderror
                :items='@json($cats)'
                title-field="name"
                value-field="id"
                xlang="{{config('app.locale')}}"
                xid="categoryId"
                xname="category_id"
                @error('category_id') :err="true" @enderror
                xvalue='{{old('category_id',$item->category_id??null)}}'
                :close-on-Select="true"></searchable-select>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-group">
            <label for="sku" class="fw-semibold">{{__('SKU')}}</label>
            <input name="sku" type="text"
                   id="sku"
                   class="form-control bg-light @error('sku') is-invalid @enderror"
                   placeholder="{{__('Auto-generated')}}"
                   readonly
                   value="{{old('sku',$item->sku??null)}}"/>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="form-group">
            <label for="status" class="fw-semibold">{{__('Status')}}</label>
            <select name="status" id="status"
                    class="form-control @error('status') is-invalid @enderror">
                <option value="1"
                        @if (old('status',$item->status??null) == '1' ) selected @endif >{{__("Published")}}</option>
                <option value="0"
                        @if (old('status',$item->status??null) == '0' ) selected @endif >{{__("Draft")}}</option>
            </select>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label for="metal_type" class="fw-semibold">{{__('Metal type')}}</label>
            <select name="metal_type" id="metal_type" class="form-control @error('metal_type') is-invalid @enderror">
                <option value="gold" @if(old('metal_type', $item->metal_type ?? 'gold') == 'gold') selected @endif>{{__('Gold')}}</option>
                <option value="silver" @if(old('metal_type', $item->metal_type ?? 'gold') == 'silver') selected @endif>{{__('Silver')}}</option>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="target_group" class="fw-semibold">{{__('Target group')}}</label>
            <select name="target_group" id="target_group" class="form-control @error('target_group') is-invalid @enderror">
                <option value="women" @if(old('target_group', $item->target_group ?? 'women') == 'women') selected @endif>{{__("Women's")}}</option>
                <option value="men" @if(old('target_group', $item->target_group ?? 'women') == 'men') selected @endif>{{__("Men's")}}</option>
                <option value="children" @if(old('target_group', $item->target_group ?? 'women') == 'children') selected @endif>{{__("Children's")}}</option>
                <option value="unisex" @if(old('target_group', $item->target_group ?? 'women') == 'unisex') selected @endif>{{__("Unisex")}}</option>
            </select>
        </div>
    </div>

    <div class="col-12">
        <div class="card border border-light-subtle rounded-3 bg-light-subtle p-3 mb-2">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-sparkling-2-fill text-warning fs-4"></i>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">{{__('Product attributes')}}</h6>
                        <span class="text-muted fs-12">{{__('Without impact on final price')}}</span>
                    </div>
                </div>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-11">
                    <i class="ri-information-line me-1"></i>{{__('Group A')}}
                </span>
            </div>

            <div class="row g-3">
                {{-- Plating Color --}}
                <div class="col-md-6">
                    <div class="p-3 bg-white rounded-3 border border-light-subtle h-100 shadow-xs">
                        <label class="fw-semibold text-dark d-flex align-items-center gap-1.5 mb-2.5">
                            <i class="ri-paint-brush-line text-warning"></i>
                            <span>{{__('Plating Color')}}</span>
                        </label>
                        @php $selectedPlatings = old('plating_colors', $item->plating_colors ?? []); @endphp
                        <div class="d-flex flex-column gap-2">
                            @foreach(\App\Models\Product::platingColorOptions() as $key => $label)
                                <div class="form-check m-0">
                                    <input class="form-check-input" type="checkbox" name="plating_colors[]" id="plating_{{$key}}" value="{{$key}}"
                                           @if(in_array($key, $selectedPlatings)) checked @endif>
                                    <label class="form-check-label fs-13 text-dark cursor-pointer" for="plating_{{$key}}">
                                        {{$label}}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Stone --}}
                <div class="col-md-6">
                    <div class="p-3 bg-white rounded-3 border border-light-subtle h-100 shadow-xs">
                        <label class="fw-semibold text-dark d-flex align-items-center gap-1.5 mb-2.5">
                            <i class="ri-vip-diamond-line text-primary"></i>
                            <span>{{__('Stone')}}</span>
                        </label>
                        @php $selectedStones = old('stones', $item->stones ?? []); @endphp
                        <div class="row g-2">
                            @foreach(\App\Models\Product::stoneOptions() as $key => $label)
                                <div class="col-6 col-sm-4">
                                    <div class="form-check m-0">
                                        <input class="form-check-input stone-checkbox" type="checkbox" name="stones[]" id="stone_{{$key}}" value="{{$key}}"
                                               @if(in_array($key, $selectedStones)) checked @endif>
                                        <label class="form-check-label fs-13 text-dark cursor-pointer" for="stone_{{$key}}">
                                            {{$label}}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Accessory --}}
                <div class="col-md-6">
                    <div class="p-3 bg-white rounded-3 border border-light-subtle h-100 shadow-xs">
                        <label class="fw-semibold text-dark d-flex align-items-center gap-1.5 mb-2.5">
                            <i class="ri-handbag-line text-success"></i>
                            <span>{{__('Accessory')}}</span>
                        </label>
                        @php $selectedAccessories = old('accessories', $item->accessories ?? []); @endphp
                        <div class="d-flex flex-column gap-2">
                            @foreach(\App\Models\Product::accessoryOptions() as $key => $label)
                                <div class="form-check m-0">
                                    <input class="form-check-input accessory-checkbox" type="checkbox" name="accessories[]" id="accessory_{{$key}}" value="{{$key}}"
                                           @if(in_array($key, $selectedAccessories)) checked @endif>
                                    <label class="form-check-label fs-13 text-dark cursor-pointer" for="accessory_{{$key}}">
                                        {{$label}}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Occasions --}}
                <div class="col-md-6">
                    <div class="p-3 bg-white rounded-3 border border-light-subtle h-100 shadow-xs">
                        <label class="fw-semibold text-dark d-flex align-items-center gap-1.5 mb-2.5">
                            <i class="ri-gift-line text-danger"></i>
                            <span>{{__('Occasions')}}</span>
                        </label>
                        @php $selectedOccasions = old('occasions', $item->occasions ?? []); @endphp
                        <div class="row g-2">
                            @foreach(\App\Models\Product::occasionOptions() as $key => $label)
                                <div class="col-6 col-sm-4">
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="checkbox" name="occasions[]" id="occasion_{{$key}}" value="{{$key}}"
                                               @if(in_array($key, $selectedOccasions)) checked @endif>
                                        <label class="form-check-label fs-13 text-dark cursor-pointer" for="occasion_{{$key}}">
                                            {{$label}}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="form-group">
            <label for="excerpt" class="fw-semibold">{{__('Excerpt')}}</label>
            <textarea name="excerpt"
                      class="form-control @error('excerpt') is-invalid @enderror"
                      placeholder="{{__('Excerpt')}}"
                      id="excerpt"
                      rows="3">{{old('excerpt',$item->excerpt??null)}}</textarea>
        </div>
    </div>
    <div class="col-12">
        <div class="form-group">
            <label for="description" class="fw-semibold">{{__('Description Text')}}</label>
            <textarea name="desc" class="form-control quill-editor @error('description') is-invalid @enderror"
                      placeholder="{{__('Description Text')}}"
                      id="description"
                      rows="8">{{old('description',$item->description??null)}}</textarea>
        </div>
    </div>
    <div class="col-12">
        <div class="form-group">
            <label for="keyword" class="fw-semibold">{{__('Keyword')}}</label>
            <input name="keyword" type="text" id="keyword"
                   class="form-control @error('keyword') is-invalid @enderror"
                   placeholder="{{__('Keyword')}}" value="{{old('keyword',$item->keyword??null)}}"/>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const targetMap = { 'women': 'F', 'men': 'M', 'children': 'C', 'unisex': 'U' };
    const metalMap = { 'gold': '1', 'silver': '2', '1': '1', '2': '2' };
    const catCodeMap = @json(\App\Models\Category::all()->mapWithKeys(fn($cat) => [$cat->id => $cat->sku_code]));
    const skuInput = document.getElementById('sku');
    const targetSelect = document.getElementById('target_group');
    const metalSelect = document.getElementById('metal_type');

    function syncSkuToBreadcrumb(sku) {
        if (!sku) return;
        const breadcrumbSku = document.getElementById('breadcrumb-product-sku');
        const breadcrumbSkuItem = document.getElementById('breadcrumb-product-sku-item');
        if (breadcrumbSku && breadcrumbSkuItem) {
            breadcrumbSku.textContent = sku;
            breadcrumbSkuItem.classList.remove('d-none');
        }
    }

    function updateDynamicSku() {
        if (!skuInput) return;
        const targetVal = targetSelect ? targetSelect.value : 'women';
        const metalVal = metalSelect ? metalSelect.value : 'gold';
        const catHidden = document.querySelector('input[name="category_id"]');
        const catSelect = document.getElementById('categoryId');
        const catVal = parseInt((catHidden && catHidden.value) || (catSelect && catSelect.value) || '0', 10);

        const t = targetMap[targetVal] || 'U';
        const m = metalMap[metalVal] || '1';
        const c = catCodeMap[catVal] || (catVal ? String(catVal).padStart(2, '0') : '00');

        let seq = '0001';
        const curr = (skuInput.value || '').trim();
        if (/^\d{4}$/.test(curr.slice(-4))) {
            seq = curr.slice(-4);
        }

        const newSku = `${t}${m}${c}${seq}`;
        skuInput.value = newSku;
        syncSkuToBreadcrumb(newSku);
    }

    if (targetSelect) targetSelect.addEventListener('change', updateDynamicSku);
    if (metalSelect) metalSelect.addEventListener('change', updateDynamicSku);

    const catSelect = document.getElementById('categoryId');
    if (catSelect) catSelect.addEventListener('change', updateDynamicSku);

    const catHidden = document.querySelector('input[name="category_id"]');
    if (catHidden) {
        const observer = new MutationObserver(updateDynamicSku);
        observer.observe(catHidden, { attributes: true, attributeFilter: ['value'] });
    }

    if (!skuInput.value) {
        updateDynamicSku();
    } else {
        syncSkuToBreadcrumb(skuInput.value);
    }
    skuInput.addEventListener('input', function () {
        syncSkuToBreadcrumb(skuInput.value);
    });

    // Stone "None" mutual exclusion
    const stoneNone = document.getElementById('stone_none');
    const stoneCheckboxes = document.querySelectorAll('.stone-checkbox:not(#stone_none)');
    if (stoneNone) {
        stoneNone.addEventListener('change', function () {
            if (this.checked) {
                stoneCheckboxes.forEach(cb => cb.checked = false);
            }
        });
        stoneCheckboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                if (this.checked && stoneNone) {
                    stoneNone.checked = false;
                }
            });
        });
    }

    // Accessory "None" mutual exclusion
    const accessoryNone = document.getElementById('accessory_none');
    const accessoryCheckboxes = document.querySelectorAll('.accessory-checkbox:not(#accessory_none)');
    if (accessoryNone) {
        accessoryNone.addEventListener('change', function () {
            if (this.checked) {
                accessoryCheckboxes.forEach(cb => cb.checked = false);
            }
        });
        accessoryCheckboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                if (this.checked && accessoryNone) {
                    accessoryNone.checked = false;
                }
            });
        });
    }
});
</script>
