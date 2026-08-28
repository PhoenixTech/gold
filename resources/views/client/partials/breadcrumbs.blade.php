@if(isset($breadcrumb) && count($breadcrumb) > 0)
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb bg-light-subtle p-2 px-3 rounded-pill border d-inline-flex align-items-center mb-0 fs-13 flex-wrap">
            <li class="breadcrumb-item">
                <a href="{{url('/')}}" class="text-decoration-none text-muted hover-primary">
                    <i class="ri-home-2-line me-1"></i> {{__("Home")}}
                </a>
            </li>
            @foreach($breadcrumb as $name => $url)
                @if($loop->last || empty($url))
                    <li class="breadcrumb-item active text-dark fw-bold text-truncate" style="max-width: 280px;" aria-current="page">
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
