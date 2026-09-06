{{-- Guidelines Info Box --}}
<div class="alert alert-primary border border-primary-subtle bg-primary-subtle bg-opacity-10 shadow-sm rounded-3 p-3 mb-4 d-flex align-items-start gap-3">
    <div class="fs-4 text-primary lh-1 mt-0.5">
        <i class="ri-information-line"></i>
    </div>
    <div class="fs-13 text-dark flex-grow-1">
        <strong class="d-block text-dark fw-bold mb-1.5">{{ __("Product image guidelines") }}</strong>
        <ul class="list-unstyled mb-0 d-flex flex-column gap-1 text-muted">
            <li class="d-flex align-items-center gap-1.5">
                <i class="ri-checkbox-circle-fill text-success fs-14"></i>
                <span>{{ __("Double click on an image to set it as the main image.") }}</span>
            </li>
            <li class="d-flex align-items-center gap-1.5">
                <i class="ri-checkbox-circle-fill text-success fs-14"></i>
                <span>{{ __("You can select and upload multiple images at once.") }}</span>
            </li>
            <li class="d-flex align-items-center gap-1.5">
                <i class="ri-checkbox-circle-fill text-success fs-14"></i>
                <span>{{ __("Supported formats: JPG, PNG, GIF, WebP.") }}</span>
            </li>
        </ul>
    </div>
</div>

{{-- Hidden File Input for Uploader --}}
<div class="uploader-images d-none">
    <input type="file" multiple accept=".jpg,.jpeg,.png,.gif,.webp" id="upload-image-select"/>
</div>

{{-- Modern Drag & Drop Zone --}}
<div id="upload-drag-drop" class="card border border-2 border-dashed rounded-4 p-4 p-md-5 text-center mb-4 bg-light-subtle shadow-xs" style="cursor: pointer;">
    <div class="d-flex flex-column align-items-center justify-content-center py-2">
        <div class="rounded-circle bg-primary-subtle text-primary p-3 mb-3 d-inline-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
            <i class="ri-upload-cloud-2-line fs-1"></i>
        </div>
        <h5 class="fw-bold text-dark mb-1">{{ __("Click to upload or drag and drop images here") }}</h5>
        <p class="text-muted fs-13 mb-3">{{ __("Upload high quality product photos to showcase your jewelry pieces") }}</p>
        <button type="button" class="btn btn-sm btn-primary px-3 rounded-pill">
            <i class="ri-add-line me-1"></i>{{ __("Select images") }}
        </button>
    </div>
</div>

{{-- Uploaded Images Gallery Grid --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-1.5">
        <i class="ri-gallery-line text-primary"></i>
        <span>{{ __("Product gallery") }}</span>
    </h6>
    @if(isset($item) && count($item->getMedia()) > 0)
        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-12">
            {{ count($item->getMedia()) }} {{ __("Images") }}
        </span>
    @endif
</div>

<div id="uploading-images" class="row g-3">
    @if (isset($item))
        @foreach($item->getMedia() as $k => $media)
            <div data-id="-1" data-key="{{$k}}"
                 class="image-index col-xl-3 col-md-4 col-sm-6 mb-3 @if($k == $item->image_index) indexed @endif">
                <div class="card h-100 shadow-sm border rounded-3 overflow-hidden position-relative product-media-card">
                    {{-- Main Image Indicator Badge --}}
                    <span class="badge bg-primary position-absolute top-0 start-0 m-2 shadow-sm index-badge" style="z-index: 2;">
                        <i class="ri-star-fill me-1"></i>{{ __("Main image") }}
                    </span>

                    {{-- Delete Button --}}
                    <button type="button" class="btn btn-danger upload-remove-image position-absolute top-0 end-0 m-2 shadow-sm rounded-circle d-flex align-items-center justify-content-center p-0" style="width: 30px; height: 30px; z-index: 2;" title="{{ __('Remove image') }}">
                        <i class="ri-delete-bin-line fs-14"></i>
                    </button>

                    <div class="ratio ratio-1x1 bg-light">
                        <img class="img-list w-100 h-100 object-fit-cover" src="{{$media->getUrl('product-image')}}" alt="{{$k}}">
                    </div>

                    <div class="card-footer bg-white border-top py-2 px-2.5 text-center">
                        <small class="text-muted fs-11 d-flex align-items-center justify-content-center gap-1">
                            <i class="ri-cursor-line text-primary"></i>
                            {{ __("Double click to set as main") }}
                        </small>
                    </div>
                    <input type="hidden" name="medias[]" value="{{$media->id}}"/>
                </div>
            </div>
        @endforeach
        <input type="hidden" name="index_image" id="index-image" value="{{$item?->image_index}}">
    @endif
</div>

