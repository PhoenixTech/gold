@php
    $aboutText = $about ?? getSetting('about');
@endphp

<div class="brand-intro-card contact-intro-card p-4 p-md-5 shadow-sm rounded-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill px-3 py-1 fs-12 fw-bold">
                    {{config('app.name')}}
                </span>
                <span class="text-muted fs-13">{{__("Authentic Gold & Timeless Jewelry")}}</span>
            </div>
            <h2 class="fw-bold text-dark mb-3 fs-24">
                {{__("Craftsmanship, Quality & Transparent Gold Trading")}}
            </h2>
            <p class="text-muted fs-15 leading-relaxed mb-0">
                @if(!empty($aboutText))
                    {!! $aboutText !!}
                @else
                    {{__("Welcome to our atelier and online gallery. Every piece in our collection is crafted with authentic 18-karat gold, certified weights, and meticulous precision. Whether you are looking for ready-to-wear fine jewelry, seeking custom design consultation, or purchasing gold gifts, our specialist team is delighted to assist you.")}}
                @endif
            </p>
        </div>
        <div class="col-lg-4 text-center">
            <div class="d-inline-flex flex-column align-items-center justify-content-center p-4 bg-white rounded-4 shadow-sm border border-light-subtle w-100" style="max-width: 320px;">
                <i class="ri-medal-fill text-warning fs-36 mb-2"></i>
                <h6 class="fw-bold text-dark mb-1">{{__("Official Guarantee & Invoice")}}</h6>
                <p class="text-muted fs-13 mb-0">{{__("100% Certified Standard 18K Gold")}}</p>
            </div>
        </div>
    </div>

    <!-- Trust Badges Grid -->
    <div class="row g-3 g-md-4 mt-3 pt-3 border-top border-light-subtle">
        <div class="col-6 col-lg-3">
            <div class="trust-badge-card h-100 text-center">
                <div class="badge-icon-box mx-auto">
                    <i class="ri-vip-diamond-line"></i>
                </div>
                <h6 class="fw-bold text-dark fs-14 mb-1">{{__("18K Certified Gold")}}</h6>
                <p class="text-muted fs-12 mb-0">{{__("Verified weight and standard assay stamp")}}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="trust-badge-card h-100 text-center">
                <div class="badge-icon-box mx-auto">
                    <i class="ri-brush-3-line"></i>
                </div>
                <h6 class="fw-bold text-dark fs-14 mb-1">{{__("Custom Jewelry Design")}}</h6>
                <p class="text-muted fs-12 mb-0">{{__("From initial sketch to 3D crafting")}}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="trust-badge-card h-100 text-center">
                <div class="badge-icon-box mx-auto">
                    <i class="ri-shield-check-line"></i>
                </div>
                <h6 class="fw-bold text-dark fs-14 mb-1">{{__("Insured Delivery")}}</h6>
                <p class="text-muted fs-12 mb-0">{{__("Secure tracked delivery with full insurance")}}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="trust-badge-card h-100 text-center">
                <div class="badge-icon-box mx-auto">
                    <i class="ri-exchange-dollar-line"></i>
                </div>
                <h6 class="fw-bold text-dark fs-14 mb-1">{{__("Live Pricing Transparency")}}</h6>
                <p class="text-muted fs-12 mb-0">{{__("Fair workshop rates and clear breakdown")}}</p>
            </div>
        </div>
    </div>
</div>
