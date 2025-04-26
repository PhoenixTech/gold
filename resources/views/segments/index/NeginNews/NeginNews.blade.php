<section class="NeginNews live-setting" data-live="{{$data->area_name.'_'.$data->part}}">
    <div class="{{gfx()['container']}}">

        <div class="row">

            <div class="col-7 col-md-8 pt-4 main-dir">
{{--                <h1>--}}
{{--                    {{getSetting($data->area_name.'_'.$data->part.'_title')}}--}}
{{--                </h1>--}}
{{--                @foreach(getGroupsSet($data->area_name.'_'.$data->part.'_groups') as $mainGroup)--}}
{{--                    <a href="{{$mainGroup->webUrl()}}" class="link my-1 d-inline-block">--}}
{{--                        {{$mainGroup->name}}--}}
{{--                    </a>--}}
{{--                    <br>--}}
{{--                @endforeach--}}
                {!! getSetting($data->area_name.'_'.$data->part.'_title') !!}
                <div class="py-2">
                    <br>
                </div>

            </div>
            <div class="col-5 col-md-4">
                <img src="{{asset('upload/images/'.$part->area_name . '.' . $part->part.'.webp')}}" alt="image"
                     class="img-fluid">
            </div>
            <div class="col-12 btm">
{{--                <div class="d-flex justify-content-evenly align-items-center">--}}
{{--                    @foreach(getGroupsSet($data->area_name.'_'.$data->part.'_group2') as $group)--}}
{{--                        <a href="{{$group->webUrl()}}" class="btn btn-dark">--}}
{{--                            {{$group->name}}--}}
{{--                        </a>--}}
{{--                    @endforeach--}}
{{--                </div>--}}
                {!! getSetting($data->area_name.'_'.$data->part.'_text') !!}
            </div>
        </div>
    </div>
</section>
