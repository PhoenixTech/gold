<aside class="post-sidebar">
    <div class="card border rounded-3 p-3 mb-4 bg-white shadow-xs">
        <h2 class="fw-bold fs-15 text-main mb-3">
            {{__("Search")}}
        </h2>
        <form action="{{route('client.search')}}" method="GET" class="mb-0" role="search">
            <label for="post-search-input" class="visually-hidden">{{__("Search")}}</label>
            <div class="input-group">
                <input type="search" id="post-search-input" name="q" class="form-control rounded-2 border" placeholder="{{__('Search')}}..." autocomplete="off">
                <button class="btn btn-primary rounded-2 px-3 d-inline-flex align-items-center justify-content-center" type="submit" aria-label="{{__('Search')}}" style="min-width: 44px; min-height: 44px;">
                    <i class="ri-search-2-line" aria-hidden="true"></i>
                </button>
            </div>
        </form>
    </div>

    <div class="card border rounded-3 p-3 mb-4 bg-white shadow-xs">
        <h2 class="fw-bold fs-15 text-main mb-3">
            {{__("Recent posts")}}
        </h2>
        <ul class="list-unstyled mb-0 d-flex flex-column gap-2.5">
            @foreach(\App\Models\Post::where('status', 1)->orderByDesc('id')->limit(5)->get() as $pst)
                <li class="pb-2.5 border-bottom border-light-subtle last-0">
                    <a href="{{$pst->webUrl()}}" class="d-flex align-items-center gap-2.5 text-decoration-none text-main hover-primary group-hover">
                        <img src="{{$pst->imgUrl()}}" alt="{{$pst->title}}" class="rounded-2 flex-shrink-0 object-fit-cover" width="56" height="56" loading="lazy">
                        <div class="flex-grow-1 min-w-0">
                            <h3 class="fs-13 fw-semibold mb-1 text-truncate">{{$pst->title}}</h3>
                            <time datetime="{{$pst->created_at->toIso8601String()}}" class="text-muted d-block fs-11 fa-num">
                                {{$pst->created_at->ldate('Y/m/d')}}
                            </time>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="card border rounded-3 p-3 mb-4 bg-white shadow-xs">
        <h2 class="fw-bold fs-15 text-main mb-3">
            {{__("Categories")}}
        </h2>
        <div class="d-flex flex-column gap-1">
            @foreach(\App\Models\Group::whereNull('parent_id')->get() as $grp)
                <a href="{{$grp->webUrl()}}" class="text-decoration-none text-body hover-primary py-1.5 px-2 rounded-2 d-flex align-items-center justify-content-between fs-13 fw-medium transition-all">
                    <span>{{$grp->name}}</span>
                </a>
            @endforeach
        </div>
    </div>
</aside>
