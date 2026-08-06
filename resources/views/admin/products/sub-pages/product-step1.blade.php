<div class="row">
    <div class="col-md-6 mt-3">
        <div class="form-group">
            <label for="name">
                {{__('Name')}}
            </label>
            <input name="name" type="text"
                   id="name"
                   class="form-control @error('name') is-invalid @enderror"
                   placeholder="{{__('Name')}}"
                   value="{{old('name',$item->name??null)}}"/>
        </div>
    </div>
    <div class="col-md-6 mt-3">
        <div class="form-group">
            <label for="name">
                {{__('Slug')}}
            </label>
            <input name="slug" type="text"
                   id="slug"
                   class="form-control @error('slug') is-invalid @enderror"
                   placeholder="{{__('Slug')}}"
                   value="{{old('slug',$item->slug??null)}}"/>
        </div>
    </div>


    <div class="col-lg-3 mt-3">
        <div class="form-group">
            <label for="price">
                {{__('Base price')}}
            </label>

            <currency-input xname="price" xid="price" @error('price')
            :err="true" @enderror xtitle="{{__('Base price')}}"
                            :xvalue="{{old('price',$item->price??null)}}"></currency-input>
        </div>
    </div>
    <div class="col-lg-3 mt-3">
        <div class="form-group">
            <label for="wage">
                {{__('Wage')}}
            </label>

            <currency-input xname="wage" xid="wage" @error('wage')
            :err="true" @enderror xtitle="{{__('Wage')}}"
                            :xvalue="{{old('wage',$item->wage??15)}}"></currency-input>
        </div>
    </div>
    <div class="col-lg-3 mt-3">
        <div class="form-group">
            <label for="addon">
                {{__('Addon price')}}
            </label>

            <currency-input xname="addon" xid="addon" @error('addon')
            :err="true" @enderror :xvalue="{{old('addon',$item->addon??0)}}"></currency-input>
        </div>
    </div>
    <div class="col-lg-3 mt-3">
        <div class="form-group">
            <label for="buy_price">
                {{__('Purchase price')}}
            </label>

            <currency-input xname="buy_price" xid="buy_price" @error('buy_price')
            :err="true" @enderror :xvalue="{{old('buy_price',$item->buy_price??0)}}"></currency-input>
        </div>
    </div>

    <!-- Gold & Silver Specifications Section -->
    <div class="col-12 mt-4">
        <div class="card border border-warning-subtle shadow-sm rounded-3">
            <div class="card-header bg-warning-subtle fw-bold text-dark d-flex align-items-center gap-2">
                <i class="ri-vip-diamond-line text-warning fs-5"></i>
                <span>{{__("Gold & Silver Specifications")}}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="metal_type" class="fw-semibold">{{__('Metal type')}}</label>
                            <select name="metal_type" id="metal_type" class="form-control @error('metal_type') is-invalid @enderror">
                                <option value="gold" @if(old('metal_type', $item->metal_type ?? 'gold') == 'gold') selected @endif>{{__('Gold')}}</option>
                                <option value="silver" @if(old('metal_type', $item->metal_type ?? 'gold') == 'silver') selected @endif>{{__('Silver')}}</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="weight" class="fw-semibold">{{__('Weight (grams)')}}</label>
                            <input name="weight" type="number" step="0.001" min="0" id="weight"
                                   class="form-control @error('weight') is-invalid @enderror"
                                   placeholder="0.000"
                                   value="{{old('weight', $item->weight ?? 0)}}"/>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="labor_charge_1" class="fw-semibold">{{__('Labor charge 1')}}</label>
                            <currency-input xname="labor_charge_1" xid="labor_charge_1" @error('labor_charge_1') :err="true" @enderror :xvalue="{{old('labor_charge_1', $item->labor_charge_1 ?? $item->wage ?? 0)}}"></currency-input>
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
                            <label for="profit" class="fw-semibold">{{__('Profit (%)')}}</label>
                            <input name="profit" type="number" step="0.01" min="0" max="100" id="profit"
                                   class="form-control @error('profit') is-invalid @enderror"
                                   placeholder="7%"
                                   value="{{old('profit', $item->profit ?? 7)}}"/>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="tax" class="fw-semibold">{{__('Tax (%)')}}</label>
                            <input name="tax" type="number" step="0.01" min="0" max="100" id="tax"
                                   class="form-control @error('tax') is-invalid @enderror"
                                   placeholder="9%"
                                   value="{{old('tax', $item->tax ?? 9)}}"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mt-3">
        <div class="form-group">
            <label for="categoryId">
                {{__('Main product category')}}
            </label>

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
    <div class="col-lg-4 mt-3">
        <div class="form-group">
            <label for="price">
                {{__('SKU')}}
            </label>
            <input name="sku" type="text"
                   id="sku"
                   class="form-control @error('sku') is-invalid @enderror"
                   placeholder="{{__('SKU')}}"
                   value="{{old('sku',$item->sku??null)}}"/>
        </div>
    </div>
    <div class="col-lg-4 mt-3">
        <div class="form-group">
            <label for="status">
                {{__('Status')}}
            </label>
            <select name="status" id="status"
                    class="form-control @error('status') is-invalid @enderror">
                <option value="1"
                        @if (old('status',$item->status??null) == '1' ) selected @endif >{{__("Published")}} </option>
                <option value="0"
                        @if (old('status',$item->status??null) == '0' ) selected @endif >{{__("Draft")}} </option>
            </select>
        </div>
    </div>
    <div class="col-md-12 mt-3">
        <div class="form-group">
            <label for="excerpt">
                {{__('Excerpt')}}
            </label>
            <textarea name="excerpt"
                      class="form-control @error('excerpt') is-invalid @enderror"
                      placeholder="{{__('Excerpt')}}"
                      id="excerpt"
                      rows="4">{{old('excerpt',$item->excerpt??null)}}</textarea>
        </div>
    </div>
    <div class="col-md-12 mt-3">
        <div class="form-group">
            <label for="description">
                {{__('Description Text')}}
            </label>
            <textarea name="desc" class="form-control ckeditorx seo-analyze @error('description') is-invalid @enderror"
                      placeholder="{{__('Description Text')}}"
                      id="description"
                      rows="8">{{old('description',$item->description??null)}}</textarea>
        </div>
        <div class="col-12">
            <div class="form-group mt-3">
                <label for="title">
                    {{__('Keyword')}} [{{__("SEO")}}]
                </label>
                <input name="keyword" type="text" id="keyword"
                       class="form-control @error('keyword') is-invalid @enderror"
                       placeholder="{{__('Keyword')}}" value="{{old('keyword',$item->keyword??null)}}"/>
            </div>
            <div id="seo-hint">
            </div>
        </div>
    </div>
</div>
