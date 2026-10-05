{{-- Tab: whether the campaign is live, and where it lives on the storefront.
     @var \App\Models\Campaign|null $item --}}

<div class="alert alert-info border border-info-subtle d-flex align-items-start gap-2 py-2 mb-4">
    <i class="ri-send-plane-line fs-5"></i>
    <div>
        {{__("A campaign is visible on the home page only while its status is published and the current time is inside its schedule. The tile always opens the campaign product page.")}}
    </div>
</div>

<div class="form-group mb-4">
    <label for="status" class="fw-semibold">{{__("Status")}}</label>
    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
        @foreach(\App\Enums\CampaignStatus::cases() as $case)
            <option value="{{$case->value}}" @selected(old('status', $item->status->value ?? 'draft') === $case->value)>{{$case->label()}}</option>
        @endforeach
    </select>
    @if(isset($item))
        <small class="text-muted d-block mt-1">
            {{__("Current state")}}: <span class="{{$item->statusBadgeClass()}}">{{$item->statusLabel()}}</span>
        </small>
        @if($item->isLive())
            <small class="d-block mt-2">
                <a href="{{$item->url()}}" target="_blank" class="text-decoration-none">
                    <i class="ri-external-link-line"></i>
                    {{__("Open the campaign page")}}
                </a>
            </small>
        @endif
    @endif
    @error('status') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>

<div class="form-group mb-3">
    <label for="slug" class="fw-semibold">{{__("Slug")}}</label>
    <input name="slug" type="text" id="slug" dir="ltr"
           class="form-control @error('slug') is-invalid @enderror"
           placeholder="{{__("Generated from the title when left empty")}}"
           value="{{old('slug', $item->slug ?? null)}}">
    <small class="text-muted d-block mt-1" dir="ltr">
        {{__("Campaign page address")}}: {{ route('client.products') }}?campaign=<b>{{ old('slug', $item->slug ?? '…') }}</b>
    </small>
    @error('slug') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>

<div class="form-group mb-3">
    <label for="canonical" class="fw-semibold">{{__("Canonical URL")}}</label>
    <input name="canonical" type="text" id="canonical" dir="ltr"
           class="form-control @error('canonical') is-invalid @enderror"
           value="{{old('canonical', $item->canonical ?? null)}}">
    @error('canonical') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>