<section class="CategoryDescription live-setting" data-live="{{$data->area_name.'_'.$data->part}}" >
        <div class="{{gfx()['container']}} py-3">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3>
                        {{$category->name}}
                    </h3>
                    {{$category->description}}
                </div>
                <div class="col-md-6">
                    <img src=" {{$category->imgUrl()}}" alt="{{$category->name}}" class="img-fluid">
                </div>
            </div>
        </div>

</section>
