@extends('website.inc.website-layout')

@section('title')
    {{config('app.name')}} - {{getSetting('subtitle') ?: __('Online Gold & Jewelry Store')}}
@endsection

@section('content')
<div class="homepage-wrapper">

    <!-- WTF Category Tabs (Top two buttons, unpinned / non-sticky) -->
    @if(isset($mainCategories) && $mainCategories->isNotEmpty())
        <div id="wtf-main-btns" class="wtf-tabs">
            @foreach($mainCategories as $k => $mainCategory)
                @php
                    $tabName = explode(' ', $mainCategory->name)[0];
                    $defaultBg = ($k == 0) ? '#caa867' : '#cccccc';
                    $bgColor = $mainCategory->bg_color ?: $defaultBg;
                    $textColor = $mainCategory->color ?: '#111111';
                @endphp
                <button type="button" 
                        class="wtf-tab-btn @if($k == 0) active @endif" 
                        style="background-color: {{$bgColor}}; color: {{$textColor}};"
                        data-id="#wtf-{{$mainCategory->id}}">
                    {{$tabName}}
                </button>
            @endforeach
        </div>
    @endif

    <!-- WTFIndex (Category Grid: 4 columns x 3 rows with clean square thumbs & titles from /old) -->
    @if(isset($mainCategories) && $mainCategories->isNotEmpty())
        <section class="WTFIndex live-setting pt-3 pb-2" data-nav="#wtf-main-btns">
            @foreach($mainCategories as $k => $mainCategory)
                @php
                    $words = explode(' ', $mainCategory->name);
                    $metalParam = $mainCategory->metal ?? ($k == 1 ? 'silver' : 'gold');
                    $childCats = is_iterable($mainCategory->children) ? $mainCategory->children : ($mainCategory->relationLoaded('children') ? $mainCategory->children->where('hide', 0) : $mainCategory->children()->where('hide', 0)->get());
                @endphp
                <div class="wtf-section container px-2 px-sm-3" id="wtf-{{$mainCategory->id}}" @if($k == 0) style="display: block" @else style="display: none" @endif>
                    <div class="row g-2 g-sm-3" dir="rtl">
                        @foreach($childCats as $childCategory)
                            <div class="col-3 text-center mb-3">
                                <a href="{{ route('client.category', ['category' => $childCategory->slug, 'metal' => $metalParam]) }}" class="d-block text-decoration-none text-dark cat-item-link">
                                    <div class="cat-img-box d-flex align-items-center justify-content-center">
                                        @php
                                            $hasMetalImg = ($metalParam === 'silver' ? !empty($childCategory->silver_image) : !empty($childCategory->image));
                                        @endphp
                                        @if($hasMetalImg)
                                            <img src="{{$childCategory->imgForMetal($metalParam)}}" 
                                                 onerror="this.onerror=null;this.src='{{$childCategory->imgOriginalForMetal($metalParam)}}';" 
                                                 alt="{{$childCategory->name}}" 
                                                 class="w-100 cat-thumb-img" 
                                                 loading="lazy">
                                        @elseif(!empty($childCategory->image))
                                            <img src="{{$childCategory->imgUrl()}}" 
                                                 onerror="this.onerror=null;this.src='{{$childCategory->imgOriginalUrl()}}';" 
                                                 alt="{{$childCategory->name}}" 
                                                 class="w-100 cat-thumb-img" 
                                                 loading="lazy">
                                        @elseif(!empty($childCategory->icon))
                                            <div class="d-flex flex-column align-items-center justify-content-center w-100 h-100 p-2 text-center" style="background: linear-gradient(135deg, #fafafa 0%, #f1f3f5 100%);">
                                                <i class="{{$childCategory->icon}} fs-1 {{ $metalParam === 'silver' ? 'text-secondary' : 'text-warning' }} opacity-85"></i>
                                            </div>
                                        @else
                                            <img src="{{$childCategory->imgUrl()}}" 
                                                 onerror="this.onerror=null;this.src='{{$childCategory->imgOriginalUrl()}}';" 
                                                 alt="{{$childCategory->name}}" 
                                                 class="w-100 cat-thumb-img" 
                                                 loading="lazy">
                                        @endif
                                    </div>
                                    <h5 class="cat-item-title">
                                        {{implode(' ', array_diff(explode(' ', $childCategory->name), $words)) ?: $childCategory->name}}
                                    </h5>
                                </a>
                            </div>
                        @endforeach

                        {{-- 12th cell: the live campaign for this metal tab. Renders nothing when no campaign is live. --}}
                        @include('client.partials.home.campaign-slot', [
                            'campaign' => $campaigns[$metalParam] ?? null,
                            'products' => $products[$metalParam] ?? null,
                            'metal' => $metalParam,
                        ])
                    </div>
                </div>
            @endforeach
        </section>
    @endif

    <!-- Trust Badges Section -->
    <section class="home-brand-intro py-3 py-md-4 reveal-on-scroll">
        <div class="{{gfx()['container']}}">
            @include('client.partials.brand-intro')
        </div>
    </section>

    @php
        $tagGroups = $featuredTagGroups ?? collect();
    @endphp

    @if($tagGroups->isNotEmpty() && $tagGroups->contains(fn($g) => $g['products']->isNotEmpty()))
        <section class="featured-products py-5 bg-light-subtle border-top border-bottom reveal-on-scroll">
            <div class="{{gfx()['container']}}">
                <div class="mb-4 pb-2 border-bottom">
                    <h4 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="ri-sparkling-fill text-primary"></i>
                        <span>{{__("Our Featured Products")}}</span>
                    </h4>
                    <p class="text-muted fs-14 mb-0">{{__("A curated selection of our finest gold & jewelry pieces")}}</p>
                </div>

                @if($tagGroups->count() > 1 || ($tagGroups->first()['tag'] ?? null) !== null)
                    <div class="featured-tag-boxes row g-3 mb-4" role="tablist">
                        @foreach($tagGroups as $index => $tagGroup)
                            <div class="col-12 col-md-4">
                                <button type="button"
                                    class="featured-tag-box w-100 p-3 p-md-3.5 rounded-3 text-start d-flex align-items-center justify-content-between border transition-all @if($loop->first) active @endif"
                                    id="featured-tab-{{ $index }}"
                                    data-bs-toggle="pill"
                                    data-bs-target="#featured-pane-{{ $index }}"
                                    role="tab"
                                    aria-controls="featured-pane-{{ $index }}"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="featured-tag-icon-wrap rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                                            <i class="{{ $tagGroup['icon'] }} fs-20"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold fs-15 text-dark tag-box-title">{{ $tagGroup['title'] }}</div>
                                            <div class="text-muted fs-12 mt-0.5">{{ count($tagGroup['products']) }} {{ __('Products') }}</div>
                                        </div>
                                    </div>
                                    <i class="ri-arrow-left-s-line fs-20 text-muted tag-box-arrow"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="tab-content" id="featured-tags-content">
                    @foreach($tagGroups as $index => $tagGroup)
                        <div class="tab-pane fade @if($loop->first) show active @endif"
                            id="featured-pane-{{ $index }}"
                            role="tabpanel"
                            aria-labelledby="featured-tab-{{ $index }}">
                            <div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 bg-white">
                                @if($tagGroup['products']->isNotEmpty())
                                    <div class="row g-3 g-md-4">
                                        @foreach($tagGroup['products'] as $item)
                                            <div class="col-6 col-md-4 col-lg-3">
                                                @include('client.partials.product-card', ['product' => $item])
                                            </div>
                                        @endforeach
                                    </div>

                                    @if(!empty($tagGroup['slug']))
                                        <div class="text-center mt-4 pt-3 border-top">
                                            <a href="{{ route('client.tag', $tagGroup['slug']) }}" class="btn btn-outline-primary rounded-pill btn-sm px-4 py-2 fw-semibold d-inline-flex align-items-center gap-1.5">
                                                <span>{{ __('View all') }} {{ $tagGroup['title'] }}</span>
                                                <i class="ri-arrow-left-line"></i>
                                            </a>
                                        </div>
                                    @endif
                                @else
                                    <div class="text-center py-5 text-muted">
                                        <i class="ri-inbox-line fs-1 d-block mb-2 text-secondary"></i>
                                        <span>{{ __('No products found') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @php unset($item, $tagGroup); @endphp
    @endif

    <!-- Latest News / Articles Section -->
    @if(isset($latestPosts) && $latestPosts->isNotEmpty())
        <section class="NeginNews py-5 bg-light-subtle reveal-on-scroll">
            <div class="{{gfx()['container']}}">
                <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                    <div>
                        <h4 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                            <i class="ri-newspaper-line text-primary"></i>
                            <span>{{__("Latest Articles & Market News")}}</span>
                        </h4>
                        <p class="text-muted fs-14 mb-0">{{__("Read educational guides and gold market updates")}}</p>
                    </div>
                    <a href="{{route('client.posts')}}" class="btn btn-outline-primary rounded-pill btn-sm px-3.5 py-1.5 fw-semibold d-inline-flex align-items-center gap-1">
                        <span>{{__("View all")}}</span>
                        <i class="ri-arrow-left-line"></i>
                    </a>
                </div>

                <div class="row g-4">
                    @foreach($latestPosts as $post)
                        <div class="col-12 col-sm-6 col-lg-3">
                            @include('client.partials.post-card', ['post' => $post])
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- Homepage FAQ Section -->
    <section class="home-faq-section py-5 bg-white border-top reveal-on-scroll">
        <div class="{{gfx()['container']}}">
            @include('client.partials.faq')
        </div>
    </section>

</div>
@endsection
