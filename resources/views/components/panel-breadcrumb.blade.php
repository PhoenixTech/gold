@if(! auth()->user()?->isVisitor() && ! auth()->user()?->isCourier())
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
        <li class="breadcrumb-item active d-none" id="breadcrumb-product-sku-item">
            <code class="fw-bold text-primary font-monospace bg-primary-subtle px-2 py-0.5 rounded border border-primary-subtle fs-12" id="breadcrumb-product-sku"></code>
        </li>
    </ol>
</nav>
@endif
