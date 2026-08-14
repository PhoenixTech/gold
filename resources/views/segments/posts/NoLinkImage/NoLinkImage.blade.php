<section class='NoLinkImage @if(getSetting($data->area_name.'_'.$data->part.'_dark')) dark-mode @endif live-setting' data-live="{{$data->area_name.'_'.$data->part}}">
    <div class="{{gfx()['container']}}">
        <div class="row">

            @foreach( getGroupPostsBySetting($data->area_name.'_'.$data->part.'_group',5) as $post )
                <div class="col-lg-3 col-md-6">
                    <div class=" no-link-item mb-4">

                        <img src="{{$post->orgUrl()}}" class="float-start me-2" alt=" {{$post->title}}" loading="lazy">
                        <h5>
                            {{$post->title}}
                        </h5>
                        <p>
                            {{$post->subtitle}}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
