@if(isset($breadcrumb) && count($breadcrumb) > 0)
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb fs-13 mb-0">
            <li class="breadcrumb-item">
                <a href="{{url('/')}}" class="text-decoration-none text-muted hover-primary">
                    {{__("Home")}}
                </a>
            </li>
            @foreach($breadcrumb as $name => $url)
                @if($loop->last || empty($url))
                    <li class="breadcrumb-item active text-dark fw-semibold text-truncate" style="max-width: 280px;" aria-current="page">
                        {{$name}}
                    </li>
                @else
                    <li class="breadcrumb-item">
                        <a href="{{$url}}" class="text-decoration-none text-muted hover-primary">
                            {{$name}}
                        </a>
                    </li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif
