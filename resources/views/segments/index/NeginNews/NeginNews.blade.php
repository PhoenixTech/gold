<section class="NeginNews live-setting py-5 bg-white border-bottom" data-live="{{$data->area_name.'_'.$data->part}}">
    <div class="{{gfx()['container']}}">
        <div class="row align-items-center g-4">
            <div class="col-12 col-md-7 col-lg-8 main-dir">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-light-subtle">
                    {!! getSetting($data->area_name.'_'.$data->part.'_title') !!}
                </div>
            </div>
            <div class="col-12 col-md-5 col-lg-4 text-center">
                <div class="position-relative overflow-hidden rounded-4 shadow-sm bg-light p-2">
                    <img src="{{asset('upload/images/'.$part->area_name . '.' . $part->part.'.webp')}}" alt="Zhonella Gold News" class="img-fluid rounded-4 object-fit-cover w-100" style="max-height: 280px;">
                </div>
            </div>
            <div class="col-12 btm mt-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    {!! getSetting($data->area_name.'_'.$data->part.'_text') !!}
                </div>
            </div>
        </div>
    </div>
</section>
