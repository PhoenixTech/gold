<div class="row g-4">
    <div class="col-md-6">
        <label for="tags" class="fw-semibold">{{__("Tags")}}</label>
        <tag-input xname="tags" splitter=",," xid="tags"
                   xtitle="{{__("Tags, Press enter")}}"
                   @if(isset($item))
                       xvalue="{{old('title',implode(',,',$item->tags->pluck('name')->toArray()??''))}}"
                   @endif
                   auto-complete="{{route('v1.tag.search','')}}/"
        ></tag-input>

        <div class="form-group mt-3">
            <label for="canonical" class="fw-semibold">{{__('Canonical')}}</label>
            <input type="text" id="canonical" name="canonical"
                   value="{{old('canonical',$item->canonical??null)}}"
                   placeholder="{{__('canonical')}}"
                   class="form-control">
        </div>
    </div>
    <div class="col-md-6">
        <h6 class="fw-bold mb-2">{{__("Categories")}}</h6>
        <ul class="group-control">
            {!!showCatNestedControl($cats,old('cat',isset($item)?$item->categories()->pluck('id')->toArray():[]))!!}
        </ul>
    </div>

    <div class="col-12">
        <div class="form-group">
            <label for="table" class="fw-semibold">{{__('Description Table')}}</label>
            <textarea name="table" class="quill-editor @error('table') is-invalid @enderror"
                      placeholder="{{__('Description Table')}}"
                      id="table"
                      rows="6">{{old('table',$item->table??null)}}</textarea>
        </div>
    </div>
</div>

<hr class="my-4">

<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0">{{__("Discounts")}}</h5>
    <a href="{{route('admin.discount.create')}}?product_id={{$item->id??null}}" class="btn btn-outline-primary btn-sm">
        <i class="ri-add-line"></i>
        {{__("Add new discount")}}
    </a>
</div>
<table class="table table-sm align-middle" id="discounts">
    <tr>
        <th>{{__("Title")}}</th>
        <th>{{__("Type")}}</th>
        <th>{{__("Amount")}}</th>
        <th>{{__("Discount code")}}</th>
        <th>{{__("Expire date")}}</th>
        <th>-</th>
    </tr>
    @if(isset($item))
        @foreach($item->discounts as $dis)
            <tr>
                <td>{{$dis->title}}</td>
                <td>{{$dis->type}}</td>
                <td>
                    {{number_format($dis->amount)}}
                    @if($dis->type == "PERCENT") % @endif
                </td>
                <td>{{$dis->code}}</td>
                <td>{{$dis->expire?->ldate('Y-m-d H:i:s')??'-'}}</td>
                <td class="text-nowrap">
                    <a href="{{ route('admin.discount.destroy',$dis->id) }}" class="btn btn-danger btn-sm" data-id="{{$dis->id}}">
                        <span class="ri-close-line"></span>
                    </a>
                    <a href="{{route('admin.discount.edit',$dis->id)}}" class="btn btn-primary btn-sm ms-1">
                        <i class="ri-edit-line"></i>
                    </a>
                </td>
            </tr>
        @endforeach
    @endif
</table>

<hr class="my-4">

<div class="mt-2 position-relative">
    <h5 class="mb-3">{{__("Attachments")}}</h5>
    @if(isset($item))
        <fast-attaching
            :attachments='@json($item->attachs)'
            xlang="{{config('app.locale')}}"
            upload-url="{{route('admin.attachment.attaching')}}"
            detach-url="{{route('admin.attachment.detach','')}}/"
            model="{{get_class($item)}}"
            id="{{$item->id}}"
        ></fast-attaching>
    @endif
</div>

<hr class="my-4">

<div>
    <h5 class="mb-3">{{__("Additional data")}}</h5>
    <meta-input
        props-api-link="{{route('v1.category.prop','')}}/"
        @if(isset($item))
            :metaz='@json($item->getAllMeta())'
            :quantitiez='@json($item->quantities)'
            product-id="{{$item->id}}"
            :imgz='@json($item->getMedia()->toArray())'
        @endif
    ></meta-input>
</div>
