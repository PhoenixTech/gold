{{--
    The 12th cell of the home page category grid.

    It occupies exactly the slot the 11 category tiles leave empty, so the grid
    stays a clean 4x3 while a campaign is live. When no campaign owns the slot
    (nothing scheduled, nothing published, or every tagged product was taken
    down) this partial renders nothing at all and the grid falls back to 11
    cells.

    Inside the cell, either the campaign artwork or the product thumbnails are
    shown — an uploaded image wins, so an event can be art-directed.

    @var \App\Models\Campaign|null $campaign
    @var \Illuminate\Support\Collection|null $products
    @var string $metal
--}}
@if($campaign && $products && $products->isNotEmpty())
    @php
        $thumbs = $products->take(4)->values();
        $extraCount = max(0, $products->count() - $thumbs->count());
        $occasion = $campaign->primaryOccasion();
    @endphp

    <div class="col-3 text-center mb-3 campaign-slot-col">
        <a href="{{ $campaign->url($metal) }}"
           class="d-block text-decoration-none text-dark cat-item-link campaign-slot">

            <div class="cat-img-box campaign-slot__box">
                @if($campaign->hasImage())
                    <picture>
                        <source media="(max-width: 575.98px)" srcset="{{ $campaign->mobileImgUrl() }}">
                        <img src="{{ $campaign->imgUrl() }}"
                             onerror="this.onerror=null;this.src='{{ $campaign->imgOriginalUrl() }}';"
                             alt="{{ $campaign->name }}"
                             class="campaign-slot__art"
                             loading="lazy">
                    </picture>
                @else
                    <div class="campaign-slot__thumbs">
                        @foreach($thumbs as $product)
                            <img src="{{ $product->thumbUrl() }}"
                                 onerror="this.onerror=null;this.src='{{ $product->originalImageUrl() }}';"
                                 alt="{{ $product->name }}"
                                 class="campaign-slot__thumb"
                                 loading="lazy">
                        @endforeach
                    </div>
                @endif

                @if($extraCount > 0)
                    <span class="campaign-slot__more">+{{ toPersianDigits($extraCount) }}</span>
                @endif

                <div class="campaign-slot__scrim"></div>

                @if($campaign->badge_text)
                    <span class="campaign-slot__badge {{ $occasion?->isSeasonal() ? 'campaign-slot__badge--hot' : '' }}">
                        <i class="ri-flashlight-fill"></i>
                        {{ $campaign->badge_text }}
                    </span>
                @endif

                <span class="campaign-slot__cta">
                    <i class="ri-arrow-left-line"></i>
                </span>
            </div>

            <h5 class="cat-item-title">{{ $campaign->name }}</h5>

            @if($campaign->subtitle)
                <p class="campaign-slot__subtitle">{{ $campaign->subtitle }}</p>
            @endif
        </a>
    </div>
@endif