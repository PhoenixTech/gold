<section class="WTFIndex live-setting" data-live="{{$data->area_name.'_'.$data->part}}">
    <div id="wtf-main-btns" class="row">
        @foreach(getCategoriesSet($data->area_name.'_'.$data->part.'_categories') as $mainCategory)
            <div class="col main-dir" style="background: {{$mainCategory->bg_color}}; color: {{$mainCategory->color}}"
                 data-id="#wtf-{{$mainCategory->id}}">
                {{explode(' ',$mainCategory->name)[0]}}
            </div>
        @endforeach
    </div>
    <div class="py-2">
        @foreach(getCategoriesSet($data->area_name.'_'.$data->part.'_categories') as $k => $mainCategory)
            @php($x = explode(' ',$mainCategory->name))
            <div class="{{gfx()['container']}} wtf-section" id="wtf-{{$mainCategory->id}}"  @if($k == 0) style="display: block" @endif>
                <div class="row">
                    @foreach($mainCategory->children()->where('hide',0)->orderBy('sort')->get() as $childCategory)
                        <a class="col-3" href="{{$childCategory->webUrl()}}">
                            <img src="{{$childCategory->imgUrl()}}" alt="{{$childCategory->name}}" >
                            <h5>
                                {{implode(' ',array_diff(explode(' ',$childCategory->name),$x))}}
                            </h5>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>
