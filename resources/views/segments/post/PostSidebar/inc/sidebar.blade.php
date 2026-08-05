<aside class="post-sidebar">
    <div class="post-sidebar-widget">
        <h4 class="widget-title">
            <i class="ri-search-2-line"></i> {{__("Search")}}
        </h4>
        <form action="{{route('client.search')}}" class="side-data mb-0">
            <div class="input-group">
                <input type="search" name="q" class="form-control rounded-pill-start border-end-0" placeholder="{{__('Search')}}...">
                <button class="btn btn-primary rounded-pill-end px-3" type="submit">
                    <i class="ri-search-2-line"></i>
                </button>
            </div>
        </form>
    </div>

    <div class="post-sidebar-widget">
        <h4 class="widget-title">
            <i class="ri-article-line"></i> {{__("Recent posts")}}
        </h4>
        <ul class="recent-posts-list list-unstyled mb-0">
            @foreach(\App\Models\Post::where('status',1)->orderByDesc('id')->limit(5)->get() as $pst)
                <li class="mb-3 pb-2 border-bottom last-0">
                    <a href="{{$pst->webUrl()}}" class="d-flex align-items-center gap-2 text-decoration-none text-dark group-hover">
                        <img src="{{$pst->imgUrl()}}" alt="{{$pst->title}}" class="rounded-3 flex-shrink-0" style="width: 52px; height: 52px; object-fit: cover;">
                        <div class="overflow-hidden">
                            <h6 class="fs-14 fw-bold mb-1 text-truncate" style="max-width: 180px;">{{$pst->title}}</h6>
                            <small class="text-muted d-block text-truncate fs-12" style="max-width: 180px;">{{$pst->subtitle}}</small>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="post-sidebar-widget">
        <h4 class="widget-title">
            <i class="ri-folder-line"></i> {{__("Groups")}}
        </h4>
        <div class="list-group list-group-flush rounded-3 border-0">
            @foreach(\App\Models\Group::whereNull('parent_id')->get() as $grp)
                <a href="{{$grp->webUrl()}}" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between px-2 py-2 border-0 rounded-2 mb-1">
                    <span class="fs-14 fw-semibold"><i class="ri-folder-2-line me-1 text-primary"></i> {{$grp->name}}</span>
                    <i class="ri-arrow-left-s-line fs-14 text-muted"></i>
                </a>
            @endforeach
        </div>
    </div>
</aside>
