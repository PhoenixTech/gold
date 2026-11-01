<div class="form-group mb-3">
    <label for="starts_at" class="fw-semibold">{{__("Start date")}}</label>
    <vue-datetime-picker-input
        :xmin="{{strtotime('-1 year')}}"
        xid="c-start" xname="starts_at" xtitle="{{__('Start date')}}"
        @if(app()->getLocale() != 'fa') def-tab="1" xshow="datetime" @else xshow="pdatetime" @endif
        @if(isset($item) && $item->starts_at) :xvalue="{{$item->starts_at->getTimestamp()}}" @elseif(old('starts_at')) :xvalue="{{strtotime(old('starts_at')) ?: old('starts_at')}}" @endif
        :timepicker="true"
    ></vue-datetime-picker-input>
    @error('starts_at') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>

<div class="form-group mb-3">
    <label for="ends_at" class="fw-semibold">{{__("End date")}}</label>
    <vue-datetime-picker-input
        :xmin="{{strtotime('-1 year')}}"
        xid="c-end" xname="ends_at" xtitle="{{__('End date')}}"
        @if(app()->getLocale() != 'fa') def-tab="1" xshow="datetime" @else xshow="pdatetime" @endif
        @if(isset($item) && $item->ends_at) :xvalue="{{$item->ends_at->getTimestamp()}}" @elseif(old('ends_at')) :xvalue="{{strtotime(old('ends_at')) ?: old('ends_at')}}" @endif
        :timepicker="true"
    ></vue-datetime-picker-input>
    @error('ends_at') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>

<div class="form-group mb-3">
    <label for="priority" class="fw-semibold">{{__("Priority")}}</label>
    <input name="priority" type="number" id="priority" min="0" max="999" step="1"
           class="form-control @error('priority') is-invalid @enderror"
           value="{{old('priority', $item->priority ?? 10)}}">
    @error('priority') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>

<div class="form-group mb-4">
    <label for="limit" class="fw-semibold">{{__("Products in the tile")}}</label>
    <input name="limit" type="number" id="limit" min="1" max="12" step="1"
           class="form-control @error('limit') is-invalid @enderror"
           value="{{old('limit', $item->limit ?? 6)}}">
    @error('limit') <div class="text-danger small mt-1">{{$message}}</div> @enderror
</div>

<hr class="my-4">

<label class="fw-bold text-dark d-flex align-items-center gap-1.5 mb-3">
    <i class="ri-layout-row-line text-primary"></i>
    <span>{{__("Tabs")}}</span>
</label>

@foreach(\App\Enums\MetalType::cases() as $metal)
    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" name="metal_scope[]"
               id="metal_{{$metal->value}}" value="{{$metal->value}}"
               @checked(in_array($metal->value, (array) $selectedMetals))>
        <label class="form-check-label" for="metal_{{$metal->value}}">{{$metal->label()}}</label>
    </div>
@endforeach
@error('metal_scope') <div class="text-danger small mt-1">{{$message}}</div> @enderror
