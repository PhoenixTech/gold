<nav id="panel-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
            <a href="{{url('/')}}" target="_blank">
                <i class="ri-home-3-line"></i>
                {{config('app.name')}}
            </a>
        </li>
        <li class="breadcrumb-item">
            <a href="{{route('admin.home')}}">
                <i class="ri-dashboard-3-line"></i>
                {{__("Dashboard")}}
            </a>
        </li>
        {{lastCrump()}}
    </ol>
</nav>
