<section class='PostsSlider live-setting' data-live="{{$data->area_name.'_'.$data->part}}">
    <div class="{{gfx()['container']}}">
        <h3>
            {{getSetting($data->area_name.'_'.$data->part.'_title')}}
        </h3>

    </div>

    <div id="posts-slider-container">

        <div id="posts-slider">


            @foreach( getPostsQueryBySetting($data->area_name.'_'.$data->part.'_query',10) as $post )
                <div class="item slider-content">
                    <div class="post-slider">

                        <a href="{{$post->webUrl()}}">
                            <img src="{{$post->orgUrl()}}" alt=" {{$post->title}}" loading="lazy">
                            <h5>
                                {{$post->title}}
                            </h5>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
    <div id="pst-nxt" class="sld-btn">
        <i class="ri-arrow-right-line"></i>
    </div>
    <div id="pst-prv" class="sld-btn">
        <i class="ri-arrow-left-line"></i>
    </div>
</section>
