@extends('layouts.app')

@section('title')
    {{ __('Register shop') }} -
@endsection

@section('content')
    @php
        $statesPayload = $states->map(function ($state) {
            return [
                'id' => $state->id,
                'name' => $state->name,
                'cities' => $state->cities->map(fn ($city) => [
                    'id' => $city->id,
                    'name' => $city->name,
                ])->values(),
            ];
        })->values();
        $selectedMall = old('mall', $visit->mall);
        $isOtherMall = $selectedMall && ! in_array($selectedMall, \App\Models\ShopVisit::malls(), true);
        $mallSelect = old('mall') === '__other__' || $isOtherMall ? '__other__' : $selectedMall;
        $mallOther = old('mall_other', $isOtherMall ? $selectedMall : '');
        $purchaseValue = (string) old('has_purchase', $visit->has_purchase === null ? '' : (int) $visit->has_purchase);
        $isStepOne = $visit->isCollecting();
        $citiesForState = $states->firstWhere('id', (int) $selectedStateId)?->cities ?? collect();
    @endphp

    <div class="shop-visit visit-sheet" v-cloak>
        <div class="shop-visit__head">
            <h1>
                <i class="ri-store-2-line"></i>
                {{ __('Register shop') }}
            </h1>
            <p>
                @if($isStepOne)
                    {{ __('Fill the seller details while you are still inside.') }}
                @else
                    {{ __('Now leave the shop and save the address.') }}
                @endif
            </p>
        </div>

        <div class="shop-visit__card">
            <ol class="shop-visit__progress" aria-label="{{ __('Progress') }}">
                <li class="{{ $isStepOne ? 'is-current' : 'is-done' }}" @if($isStepOne) aria-current="step" @endif>
                    <span class="shop-visit__progress-num">
                        @if($isStepOne) 1 @else <i class="ri-check-line"></i> @endif
                    </span>
                    <span class="shop-visit__progress-label">
                        <strong>{{ __('Part 1') }}</strong>
                        <small>{{ __('Inside the shop') }}</small>
                    </span>
                </li>
                <li class="{{ $isStepOne ? '' : 'is-current' }}" @if(! $isStepOne) aria-current="step" @endif>
                    <span class="shop-visit__progress-num">2</span>
                    <span class="shop-visit__progress-label">
                        <strong>{{ __('Part 2') }}</strong>
                        <small>{{ __('After leaving the shop') }}</small>
                    </span>
                </li>
            </ol>

            <div class="shop-visit__body">
                @include('components.err')

                @if($isStepOne)
                    <form method="POST" action="{{ route('admin.shop-visit.step-one') }}" id="visit-step-one" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label for="mobile">{{ __('Mobile number') }} <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-smartphone-line"></i></span>
                                <input id="mobile" name="mobile" type="tel" inputmode="numeric" dir="ltr"
                                       class="form-control border-start-0 ps-2 @error('mobile') is-invalid @enderror"
                                       value="{{ old('mobile', $visit->mobile) }}"
                                       placeholder="09xxxxxxxxx" maxlength="11" autocomplete="tel" required>
                            </div>
                            @error('mobile')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name">{{ __('First name') }} <span class="text-danger">*</span></label>
                                <input id="first_name" name="first_name" type="text"
                                       class="form-control @error('first_name') is-invalid @enderror"
                                       value="{{ old('first_name', $visit->first_name) }}"
                                       autocomplete="given-name" required>
                                @error('first_name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="last_name">{{ __('Last name') }} <span class="text-danger">*</span></label>
                                <input id="last_name" name="last_name" type="text"
                                       class="form-control @error('last_name') is-invalid @enderror"
                                       value="{{ old('last_name', $visit->last_name) }}"
                                       autocomplete="family-name" required>
                                @error('last_name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="d-block">{{ __('Do you have a purchase?') }} <span class="text-danger">*</span></label>
                            <div class="shop-visit__choice" role="radiogroup">
                                <label class="@if($purchaseValue === '1') is-checked @endif">
                                    <input type="radio" name="has_purchase" value="1" required @checked($purchaseValue === '1')>
                                    {{ __('Yes') }}
                                </label>
                                <label class="@if($purchaseValue === '0') is-checked @endif">
                                    <input type="radio" name="has_purchase" value="0" required @checked($purchaseValue === '0')>
                                    {{ __('No') }}
                                </label>
                            </div>
                            @error('has_purchase')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="shop-visit__reason @if($purchaseValue === '0') is-open @endif" id="visit-reason" aria-live="polite">
                            <div class="shop-visit__reason-title">
                                <i class="ri-information-line"></i>
                                {{ __('Reason for not buying') }}
                            </div>
                            <label class="d-flex align-items-center gap-2 mb-3 fw-normal">
                                <input type="checkbox" name="has_own_workshop" value="1"
                                       @checked(old('has_own_workshop', $visit->has_own_workshop))>
                                <span>{{ __('Has a personal production workshop') }}</span>
                            </label>
                            <label for="other_reason" class="d-block">{{ __('Explain other reasons') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></label>
                            <input id="other_reason" name="other_reason" type="text" class="form-control"
                                   value="{{ old('other_reason', $visit->other_reason) }}"
                                   placeholder="{{ __('Explain other reasons') }}">
                            @error('other_reason')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="shop-visit__actions">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Continue') }}
                                <i class="ri-arrow-left-line ms-1"></i>
                            </button>
                        </div>
                    </form>
                @else
                    <div class="shop-visit__recap">
                        <div>
                            <span>{{ __('Shop contact') }}:</span>
                            <strong>{{ $visit->first_name }} {{ $visit->last_name }}</strong>
                        </div>
                        <span class="recap-divider">·</span>
                        <div dir="ltr"><strong>{{ $visit->mobile }}</strong></div>
                        <span class="recap-divider">·</span>
                        <div>
                            <span>{{ __('Do you have a purchase?') }}:</span>
                            <strong>{{ $visit->purchaseLabel() }}</strong>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.shop-visit.step-two') }}" id="visit-step-two" novalidate>
                        @csrf
                        <div class="mb-3">
                            <label class="d-block">{{ __('Shop categories') }}</label>
                            <div class="shop-visit__check">
                                @foreach(\App\Models\ShopVisit::CATEGORIES as $key => $label)
                                    <label class="@if(in_array($key, old('categories', $visit->categories ?? []), true)) is-checked @endif">
                                        <input type="checkbox" name="categories[]" value="{{ $key }}"
                                               @checked(in_array($key, old('categories', $visit->categories ?? []), true))>
                                        {{ __($label) }}
                                    </label>
                                @endforeach
                            </div>
                            @error('categories')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="d-block">{{ __('Work style') }}</label>
                            <div class="shop-visit__check">
                                @foreach(\App\Models\ShopVisit::WORK_STYLES as $key => $label)
                                    <label class="@if(in_array($key, old('work_styles', $visit->work_styles ?? []), true)) is-checked @endif">
                                        <input type="checkbox" name="work_styles[]" value="{{ $key }}"
                                               @checked(in_array($key, old('work_styles', $visit->work_styles ?? []), true))>
                                        {{ __($label) }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="state_id">{{ __('Province') }} <span class="text-danger">*</span></label>
                                <select id="state_id" name="state_id" class="form-select @error('state_id') is-invalid @enderror" required>
                                    <option value="">{{ __('Choose') }}</option>
                                    @foreach($states as $state)
                                        <option value="{{ $state->id }}" @selected((int) $selectedStateId === (int) $state->id)>
                                            {{ $state->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('state_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="city_id">{{ __('City') }} <span class="text-danger">*</span></label>
                                <select id="city_id" name="city_id" class="form-select @error('city_id') is-invalid @enderror" required>
                                    <option value="">{{ __('Choose') }}</option>
                                    @foreach($citiesForState as $city)
                                        <option value="{{ $city->id }}" @selected((int) $selectedCityId === (int) $city->id)>
                                            {{ $city->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('city_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label for="mall">{{ __('Shopping mall') }} <span class="text-danger">*</span></label>
                                <select id="mall" name="mall" class="form-select @error('mall') is-invalid @enderror" required>
                                    <option value="">{{ __('Choose') }}</option>
                                    @foreach(\App\Models\ShopVisit::malls() as $mall)
                                        <option value="{{ $mall }}" @selected($mallSelect === $mall)>{{ $mall }}</option>
                                    @endforeach
                                    <option value="__other__" @selected($mallSelect === '__other__')>{{ __('Other') }}</option>
                                </select>
                                @error('mall')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12" id="mall-other-wrap" @if($mallSelect !== '__other__') hidden @endif>
                                <label for="mall_other">{{ __('Other shopping mall') }}</label>
                                <input id="mall_other" name="mall_other" type="text" class="form-control @error('mall_other') is-invalid @enderror" value="{{ $mallOther }}" placeholder="{{ __('Other shopping mall') }}">
                                @error('mall_other')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label for="address">{{ __('Exact address and plaque') }} <span class="text-danger">*</span></label>
                                <input id="address" name="address" type="text"
                                       class="form-control @error('address') is-invalid @enderror"
                                       value="{{ old('address', $visit->address) }}"
                                       autocomplete="street-address" required
                                       placeholder="{{ __('Exact address and plaque') }}">
                                @error('address')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="shop-visit__actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-check-line me-1"></i>
                                {{ __('Register shop') }}
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <script>
        (function() {
            // Run immediately if DOM already ready to avoid flicker waiting for DOMContentLoaded
            function initVisitForm() {
                var purchaseRadios = document.querySelectorAll('input[name="has_purchase"]');
                var reasonBox = document.getElementById('visit-reason');
                var choiceLabels = document.querySelectorAll('.shop-visit__choice label');
                var checkLabels = document.querySelectorAll('.shop-visit__check label');

                function syncChoiceChecked() {
                    choiceLabels.forEach(function(label){
                        var input = label.querySelector('input[type="radio"]');
                        label.classList.toggle('is-checked', !!(input && input.checked));
                    });
                    checkLabels.forEach(function(label){
                        var input = label.querySelector('input[type="checkbox"]');
                        label.classList.toggle('is-checked', !!(input && input.checked));
                    });
                }

                purchaseRadios.forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        if (!reasonBox) return;
                        var show = radio.value === '0' && radio.checked;
                        reasonBox.classList.toggle('is-open', show);
                        syncChoiceChecked();
                    });
                });

                // fallback for browsers without :has() - keep visual sync
                document.querySelectorAll('.shop-visit__choice input, .shop-visit__check input').forEach(function(input){
                    input.addEventListener('change', syncChoiceChecked);
                });
                syncChoiceChecked();

                var mobile = document.getElementById('mobile');
                if (mobile) {
                    mobile.addEventListener('input', function () {
                        mobile.value = mobile.value
                            .replace(/[۰-۹]/g, function (digit) {
                                return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit));
                            })
                            .replace(/[٠-٩]/g, function (digit) {
                                return String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit));
                            })
                            .replace(/[^0-9]/g, '')
                            .slice(0, 11);
                    });
                }

                var states = @json($statesPayload);
                var stateSelect = document.getElementById('state_id');
                var citySelect = document.getElementById('city_id');
                function fillCities(stateId, selectedId) {
                    if (!citySelect) return;
                    var state = states.find(function (item) {
                        return String(item.id) === String(stateId);
                    });
                    // Preserve placeholder and only rebuild if needed to avoid flicker
                    var currentVal = selectedId !== null && selectedId !== '' ? String(selectedId) : (citySelect.value ? String(citySelect.value) : '');
                    citySelect.innerHTML = '';
                    var placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = @json(__('Choose'));
                    citySelect.appendChild(placeholder);
                    if (!state) return;
                    state.cities.forEach(function (city) {
                        var option = document.createElement('option');
                        option.value = city.id;
                        option.textContent = city.name;
                        if (currentVal !== '' && String(city.id) === String(currentVal)) {
                            option.selected = true;
                        }
                        citySelect.appendChild(option);
                    });
                }
                if (stateSelect && citySelect) {
                    stateSelect.addEventListener('change', function () {
                        fillCities(stateSelect.value, '');
                    });
                }

                var mallSelect = document.getElementById('mall');
                var mallOtherWrap = document.getElementById('mall-other-wrap');
                if (mallSelect && mallOtherWrap) {
                    mallSelect.addEventListener('change', function () {
                        if (mallSelect.value === '__other__') {
                            mallOtherWrap.hidden = false;
                        } else {
                            mallOtherWrap.hidden = true;
                        }
                    });
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initVisitForm);
            } else {
                initVisitForm();
            }
        })();
    </script>
@endsection
