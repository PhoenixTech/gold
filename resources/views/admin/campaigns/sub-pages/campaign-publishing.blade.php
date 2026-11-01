<div class="form-group mb-4">
    <label for="status" class="fw-semibold">{{__("Status")}}</label>
    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
        @foreach(\App\Enums\CampaignStatus::cases() as $case)
            <option value="{{$case->value}}" @selected(old('status', $item->status->value ?? 'draft') === $case->value)>{{$case->label()}}</option>
        @endforeach
    </select>
    @if(isset($item) && $item->isLive())
        <div class="mt-2">
            <a href="{{$item->url()}}" target="_blank" class="text-decoration-none fs-13">
                <i class="ri-external-link-line me-1"></i>
                {{__("Open the campaign page")}}
            </a>
        </div>
    @endif
    @error('status') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>

<div class="form-group mb-3">
    <label for="slug" class="fw-semibold">{{__("Slug")}}</label>
    <input name="slug" type="text" id="slug" dir="ltr"
           class="form-control @error('slug') is-invalid @enderror"
           value="{{old('slug', $item->slug ?? null)}}">
    @error('slug') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>

<div class="form-group mb-3">
    <label for="canonical" class="fw-semibold">{{__("Canonical URL")}}</label>
    <input name="canonical" type="text" id="canonical" dir="ltr"
           class="form-control @error('canonical') is-invalid @enderror"
           value="{{old('canonical', $item->canonical ?? null)}}">
    @error('canonical') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>
