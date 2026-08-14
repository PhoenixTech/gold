<section class='PostModernPosts live-setting' data-live="{{$data->area_name.'_'.$data->part}}" >
    <div class="{{gfx()['container']}}">
        <h3>
            {{getSetting($data->area_name.'_'.$data->part.'_title')}}
        </h3>

        <div class="row">

            @foreach( getGroupPostsBySetting($data->area_name.'_'.$data->part.'_group',4) as $post )
                <div class="col-lg-3 col-md-6">
                    <a href="{{$post->webUrl()}}">
                        <div class="post-modern-post" style="background-image: url('{{$post->imgUrl()}}')">
                            <img src="{{asset('upload/images/'.$data->area_name.'.'.$data->part.'.svg')}}"
                                 class="img-fluid" alt="">
                        </div>
                        <h4>
                            {{$post->title}}
                        </h4>
                    </a>
                </div>

            @endforeach
        </div>
    </div>
</section>
