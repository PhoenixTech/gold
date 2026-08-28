@extends('website.inc.website-layout')

@section('title')
    {{__("Compare Products")}} - {{config('app.name')}}
@endsection

@section('content')
<div class="compare-page-view">
    @include('client.partials.parallax-header', ['title' => __('Compare Products'), 'subtitle' => __('Compare specs and prices side-by-side')])
    <div class="{{gfx()['container']}} py-5">
        @if(isset($products) && count($products) > 0)
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white overflow-auto">
                <table class="table table-bordered align-middle text-center mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 150px;">{{__('Product')}}</th>
                            @foreach($products as $prod)
                                <th style="min-width: 200px;">
                                    <img src="{{$prod->imgUrl()}}" alt="{{$prod->name}}" class="rounded-3 mb-2" style="width: 100px; height: 100px; object-fit: cover;">
                                    <h6 class="fw-bold mb-1">{{$prod->name}}</h6>
                                    <span class="text-primary fw-bold">{{$prod->getPrice()}}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-semibold text-start ps-3">{{__('Category')}}</td>
                            @foreach($products as $prod)
                                <td>{{$prod->category->name ?? '-'}}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="fw-semibold text-start ps-3">{{__('Stock status')}}</td>
                            @foreach($products as $prod)
                                <td>
                                    <span class="badge {{$prod->stock_status == 'IN_STOCK' ? 'bg-success' : 'bg-danger'}}">
                                        {{$prod->stock_status == 'IN_STOCK' ? __('In Stock') : __('Out of Stock')}}
                                    </span>
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        @else
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <i class="ri-scales-3-line fs-1 text-muted mb-2"></i>
                <h5 class="fw-bold text-dark mb-1">{{__('No products selected for comparison')}}</h5>
                <p class="text-muted fs-14 mb-4">{{__('Add products to the comparison list to see their differences.')}}</p>
                <div>
                    <a href="{{route('client.products')}}" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold">
                        {{__('Explore Products')}}
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
