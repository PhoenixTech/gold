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
                   class="form-control @error('sku') is-invalid @enderror"
                   placeholder="{{__('SKU')}}"
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
            <textarea name="desc" class="form-control ckeditorx seo-analyze @error('description') is-invalid @enderror"
                      placeholder="{{__('Description Text')}}"
                      id="description"
                      rows="8">{{old('description',$item->description??null)}}</textarea>
        </div>
    </div>
    <div class="col-12">
        <div class="form-group">
            <label for="keyword" class="fw-semibold">{{__('Keyword')}} [{{__("SEO")}}]</label>
            <input name="keyword" type="text" id="keyword"
                   class="form-control @error('keyword') is-invalid @enderror"
                   placeholder="{{__('Keyword')}}" value="{{old('keyword',$item->keyword??null)}}"/>
            <div id="seo-hint"></div>
        </div>
    </div>
</div>
