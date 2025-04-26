<section class="Natalia2Categories live-setting" data-live="{{$data->area_name.'_'.$data->part}}"
        >
    <div class="{{gfx()['container']}}">
        <div class="row">

            <div class="col-7 col-md-8">
                <div class="main-dir">
{{--                    <h1>--}}
{{--                        {{getSetting($data->area_name.'_'.$data->part.'_title')}}--}}
{{--                    </h1>--}}
{{--                    <h2>--}}
{{--                        {{getSetting($data->area_name.'_'.$data->part.'_subtitle')}}--}}
{{--                    </h2>--}}
{{--                    @foreach(getCategoriesSet($data->area_name.'_'.$data->part.'_categories') as $mainCategory)--}}
{{--                        <a href="{{$mainCategory->webUrl()}}" class="btn btn-outline-dark">--}}
{{--                            {{$mainCategory->name}}--}}
{{--                        </a>--}}
{{--                        <br>--}}
{{--                    @endforeach--}}
                    {!! getSetting($data->area_name.'_'.$data->part.'_text') !!}
                </div>

            </div>
            <div class="col-5 col-md-4 bg"  style="background-image: url('{{asset('upload/images/'.$part->area_name . '.' . $part->part.'.webp')}}')">

            </div>
        </div>

    </div>
</section>
