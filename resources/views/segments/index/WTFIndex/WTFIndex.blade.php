<section class="WTFIndex live-setting" data-live="{{$data->area_name.'_'.$data->part}}">
    <div style="padding-top: 5rem"></div>
    <div id="wtf-main-btns" class="row">
        @foreach(getCategoriesSet($data->area_name.'_'.$data->part.'_categories') as $mainCategory)
            <div class="col" style="background: {{$mainCategory->bg_color}}; color: {{$mainCategory->color}}"
                 data-id="#wtf-{{$mainCategory->id}}">
                {{explode(' ',$mainCategory->name)[0]}}
            </div>
        @endforeach
    </div>
    <div class="py-4">
        @foreach(getCategoriesSet($data->area_name.'_'.$data->part.'_categories') as $k => $mainCategory)
            @php($x = explode(' ',$mainCategory->name))
            <div class="{{gfx()['container']}} wtf-section" id="wtf-{{$mainCategory->id}}"  @if($k == 0) style="display: block" @endif>
                <div class="row">
                    @foreach($mainCategory->children as $childCategory)
                        <div class="col-3">
                            <img src="{{$childCategory->imgUrl()}}" alt="{{$childCategory->name}}" >
                            <h5>
                                {{implode(' ',array_diff(explode(' ',$childCategory->name),$x))}}
                            </h5>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>
