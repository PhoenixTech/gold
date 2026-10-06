<div class="form-group mb-3">
    <label for="name" class="fw-semibold">{{__("Title shown in the tile")}}</label>
    <input name="name" type="text" id="name"
           class="form-control @error('name') is-invalid @enderror"
           placeholder="{{__("e.g. Yalda Offers")}}" value="{{old('name',$item->name??null)}}">
    @error('name') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>

<div class="form-group mb-3">
    <label for="subtitle" class="fw-semibold">{{__("Subtitle")}}</label>
    <input name="subtitle" type="text" id="subtitle"
           class="form-control @error('subtitle') is-invalid @enderror"
           placeholder="{{__("Shown under the title")}}" value="{{old('subtitle',$item->subtitle??null)}}">
    @error('subtitle') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>

<div class="form-group mb-3">
    <label for="badge_text" class="fw-semibold">{{__("Badge")}}</label>
    <input name="badge_text" type="text" id="badge_text" maxlength="60"
           class="form-control @error('badge_text') is-invalid @enderror"
           placeholder="{{__("e.g. Special Offer")}}" value="{{old('badge_text',$item->badge_text??null)}}">
    <small class="text-muted d-block mt-1">{{__("Small ribbon in the corner of the tile. Leave empty for none.")}}</small>
    @error('badge_text') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>

<div class="form-group mb-3">
    <label for="description" class="fw-semibold">{{__("Description")}}</label>
    <textarea name="description" id="description" rows="4"
              class="form-control @error('description') is-invalid @enderror"
              placeholder="{{__("Shown on the campaign product page")}}">{{old('description',$item->description??null)}}</textarea>
    @error('description') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>

<hr class="my-4">

<h6 class="fw-bold text-dark d-flex align-items-center gap-1.5 mb-1">
    <i class="ri-image-line text-primary"></i>
    <span>{{__("Tile artwork")}}</span>
</h6>
<p class="text-muted fs-13 mb-3">
    {{__("Optional. When an image is uploaded it fills the tile instead of the product thumbnails. Recommended 1:1.22 to match the category tiles.")}}
</p>

@if(isset($item) && $item->imgUrl())
    <img src="{{$item->imgUrl()}}" alt="" class="rounded-3 mb-3" style="max-height: 140px;">
@endif

<div class="form-group mb-3">
    <label for="image">{{__("Tile artwork")}}</label>
    <input accept=".jpg,.jpeg,.png,.svg,.webp" name="image" type="file"
           class="form-control @error('image') is-invalid @enderror" id="image">
    @error('image') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>

@if(isset($item) && $item->mobile_image && $item->mobileImgUrl())
    <img src="{{$item->mobileImgUrl()}}" alt="" class="rounded-3 mb-3" style="max-height: 140px;">
@endif

<div class="form-group mb-3">
    <label for="mobile_image">{{__("Tile artwork (mobile)")}}</label>
    <input accept=".jpg,.jpeg,.png,.svg,.webp" name="mobile_image" type="file"
           class="form-control @error('mobile_image') is-invalid @enderror" id="mobile_image">
    <small class="text-muted d-block mt-1">{{__("Optional. Shown on phones instead of the artwork above. Leave empty to reuse it.")}}</small>
    @error('mobile_image') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>