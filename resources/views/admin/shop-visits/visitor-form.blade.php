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

    <style ignore--minify>
        .visit-sheet {
            max-width: 720px;
            margin: 0 auto 3rem;
            background: #fff;
            color: #141414;
            border: 1px solid #e6d7b0;
            box-shadow: 0 10px 30px rgba(20, 20, 20, .04);
        }
        .visit-sheet__head {
            padding: 1.25rem 1.5rem 1rem;
            border-bottom: 1px solid #c4a35a;
        }
        .visit-sheet__head h1 {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0 0 .35rem;
        }
        .visit-sheet__head p {
            color: #5c5c5c;
            margin: 0;
            font-size: .9rem;
        }
        .visit-sheet__body {
            padding: 1.25rem 1.5rem 1.75rem;
        }
        .visit-sheet label {
            font-size: .82rem;
            font-weight: 600;
            margin-bottom: .35rem;
        }
        .visit-sheet .form-control,
        .visit-sheet .form-select {
            border-radius: 0;
            border-color: #d9d2c3;
            min-height: 44px;
        }
        .visit-sheet .form-control:focus,
        .visit-sheet .form-select:focus {
            border-color: #c4a35a;
            box-shadow: 0 0 0 .2rem rgba(196, 163, 90, .15);
        }
        .visit-progress {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .5rem;
            list-style: none;
            padding: 0;
            margin: 0 0 1.25rem;
        }
        .visit-progress li {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .65rem .75rem;
            border: 1px solid #e6d7b0;
            background: #fbf8f1;
            color: #5c5c5c;
            min-height: 52px;
        }
        .visit-progress .visit-progress__n {
            flex: 0 0 1.6rem;
            width: 1.6rem;
            height: 1.6rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #c4a35a;
            font-size: .75rem;
            font-weight: 700;
        }
        .visit-progress strong {
            display: block;
            font-size: .78rem;
            color: #141414;
        }
        .visit-progress small {
            display: block;
            font-size: .72rem;
            color: #8a6a1f;
        }
        .visit-progress .is-current {
            background: #141414;
            border-color: #141414;
            color: #f4e7c3;
        }
        .visit-progress .is-current strong,
        .visit-progress .is-current small,
        .visit-progress .is-current .visit-progress__n {
            color: #f4e7c3;
            border-color: #c4a35a;
        }
        .visit-progress .is-done {
            background: #fff;
        }
        .visit-progress .is-done .visit-progress__n {
            background: #c4a35a;
            border-color: #c4a35a;
            color: #141414;
        }
        .visit-choice,
        .visit-check {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem 1.75rem;
        }
        .visit-sheet .visit-choice label,
        .visit-sheet .visit-check label {
            display: inline-flex;
            align-items: center;
            margin: 0;
            padding: .45rem .25rem;
            font-weight: 500;
            cursor: pointer;
        }
        .visit-sheet input[type="checkbox"],
        .visit-sheet input[type="radio"] {
            width: 1.15rem;
            height: 1.15rem;
            margin: 0;
            margin-inline-end: .7rem;
            flex-shrink: 0;
            accent-color: #c4a35a;
        }
        .visit-reason {
            display: none;
            padding: .85rem;
            background: #fbf8f1;
            border: 1px dashed #c4a35a;
            margin-top: .75rem;
        }
        .visit-reason.is-open {
            display: block;
        }
        .visit-recap {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem .9rem;
            padding: .85rem 1rem;
            margin-bottom: 1.25rem;
            background: #fbf8f1;
            border: 1px solid #e6d7b0;
            font-size: .9rem;
        }
        .visit-recap span {
            color: #5c5c5c;
        }
        .visit-recap strong {
            color: #141414;
        }
        .visit-sheet .btn-primary {
            border-radius: 0;
            font-weight: 600;
            padding: .75rem 1.25rem;
            min-height: 48px;
        }
        .visit-actions {
            margin-top: 1.25rem;
        }
        @media (max-width: 767px) {
            .visit-progress {
                grid-template-columns: 1fr;
            }
            .visit-actions {
                position: sticky;
                bottom: 0;
                z-index: 2;
                background: #fff;
                margin: 1rem -1.5rem -1.75rem;
                padding: .85rem 1.5rem calc(.85rem + env(safe-area-inset-bottom));
                border-top: 1px solid #e6d7b0;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            .visit-sheet .btn-primary {
                transition: none;
            }
        }
    </style>

    <section class="visit-sheet">
        <div class="visit-sheet__head">
            <h1>{{ __('Register shop') }}</h1>
            <p>
                @if($isStepOne)
                    {{ __('Fill the seller details while you are still inside.') }}
                @else
                    {{ __('Now leave the shop and save the address.') }}
                @endif
            </p>
        </div>
        <div class="visit-sheet__body">
            @include('components.err')

            <ul class="visit-progress">
                <li class="{{ $isStepOne ? 'is-current' : 'is-done' }}" @if($isStepOne) aria-current="step" @endif>
                    <span class="visit-progress__n">
                        @if($isStepOne)
                        @else
                            <i class="ri-check-line"></i>
                        @endif
                    </span>
                    <span>
                        <strong>{{ __('Part 1') }} &nbsp;</strong>
                        <small> {{ __('Inside the shop') }}</small>
                    </span>
                </li>
                <li class="{{ $isStepOne ? '' : 'is-current' }}" @if(! $isStepOne) aria-current="step" @endif>
                    <span>
                        <strong>{{ __('Part 2') }} &nbsp;</strong>
                        <small>{{ __('After leaving the shop') }}</small>
                    </span>
                </li>
            </ul>

            @if($isStepOne)
                <form method="POST" action="{{ route('admin.shop-visit.step-one') }}" id="visit-step-one">
                    @csrf
                    <div class="mb-3">
                        <label for="mobile">{{ __('Mobile number') }}</label>
                        <input id="mobile" name="mobile" type="tel" inputmode="numeric" dir="ltr"
                               class="form-control @error('mobile') is-invalid @enderror"
                               value="{{ old('mobile', $visit->mobile) }}"
                               placeholder="09xxxxxxxxx" maxlength="11" autocomplete="tel" required autofocus>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="first_name">{{ __('First name') }}</label>
                            <input id="first_name" name="first_name" type="text"
                                   class="form-control @error('first_name') is-invalid @enderror"
                                   value="{{ old('first_name', $visit->first_name) }}"
                                   autocomplete="given-name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="last_name">{{ __('Last name') }}</label>
                            <input id="last_name" name="last_name" type="text"
                                   class="form-control @error('last_name') is-invalid @enderror"
                                   value="{{ old('last_name', $visit->last_name) }}"
                                   autocomplete="family-name" required>
                        </div>
                    </div>
                    <div class="mt-3 mb-2">
                        <label class="d-block">{{ __('Do you have a purchase?') }}</label>
                        <div class="visit-choice">
                            <label>
                                <input type="radio" name="has_purchase" value="1" required
                                       @checked($purchaseValue === '1')>
                                {{ __('Yes') }}
                            </label>
                            <label>
                                <input type="radio" name="has_purchase" value="0" required
                                       @checked($purchaseValue === '0')>
                                {{ __('No') }}
                            </label>
                        </div>
                    </div>
                    <div class="visit-reason @if($purchaseValue === '0') is-open @endif" id="visit-reason">
                        <div class="mb-2">{{ __('Reason for not buying') }}</div>
                        <label class="d-block mb-2">
                            <input type="checkbox" name="has_own_workshop" value="1"
                                   @checked(old('has_own_workshop', $visit->has_own_workshop))>
                            {{ __('Has a personal production workshop') }}
                        </label>
                        <label for="other_reason" class="d-block">{{ __('Explain other reasons') }}</label>
                        <input id="other_reason" name="other_reason" type="text" class="form-control"
                               value="{{ old('other_reason', $visit->other_reason) }}">
                    </div>
                    <div class="visit-actions">
                        <button type="submit" class="btn btn-primary w-100">
                            {{ __('Continue') }}
                        </button>
                    </div>
                </form>
            @else
                <div class="visit-recap">
                    <div>
                        <span>{{ __('Shop contact') }}:</span>
                        <strong>{{ $visit->first_name }} {{ $visit->last_name }}</strong>
                    </div>
                    <div dir="ltr"><strong>{{ $visit->mobile }}</strong></div>
                    <div>
                        <span>{{ __('Do you have a purchase?') }}:</span>
                        <strong>{{ $visit->purchaseLabel() }}</strong>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.shop-visit.step-two') }}" id="visit-step-two">
                    @csrf
                    <div class="mb-3">
                        <label class="d-block">{{ __('Shop categories') }}</label>
                        <div class="visit-check">
                            @foreach(\App\Models\ShopVisit::CATEGORIES as $key => $label)
                                <label>
                                    <input type="checkbox" name="categories[]" value="{{ $key }}"
                                           @checked(in_array($key, old('categories', $visit->categories ?? []), true))>
                                    {{ __($label) }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="d-block">{{ __('Work style') }}</label>
                        <div class="visit-check">
                            @foreach(\App\Models\ShopVisit::WORK_STYLES as $key => $label)
                                <label>
                                    <input type="checkbox" name="work_styles[]" value="{{ $key }}"
                                           @checked(in_array($key, old('work_styles', $visit->work_styles ?? []), true))>
                                    {{ __($label) }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="state_id">{{ __('Province') }}</label>
                            <select id="state_id" name="state_id" class="form-select" required>
                                <option value="">{{ __('Choose') }}</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->id }}" @selected((int) $selectedStateId === (int) $state->id)>
                                        {{ $state->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="city_id">{{ __('City') }}</label>
                            <select id="city_id" name="city_id" class="form-select" required>
                                <option value="">{{ __('Choose') }}</option>
                                @foreach($citiesForState as $city)
                                    <option value="{{ $city->id }}" @selected((int) $selectedCityId === (int) $city->id)>
                                        {{ $city->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="mall">{{ __('Shopping mall') }}</label>
                            <select id="mall" name="mall" class="form-select" required>
                                <option value="">{{ __('Choose') }}</option>
                                @foreach(\App\Models\ShopVisit::malls() as $mall)
                                    <option value="{{ $mall }}" @selected($mallSelect === $mall)>{{ $mall }}</option>
                                @endforeach
                                <option value="__other__" @selected($mallSelect === '__other__')>{{ __('Other') }}</option>
                            </select>
                        </div>
                        <div class="col-12" id="mall-other-wrap" @if($mallSelect !== '__other__') style="display:none" @endif>
                            <label for="mall_other">{{ __('Other shopping mall') }}</label>
                            <input id="mall_other" name="mall_other" type="text" class="form-control" value="{{ $mallOther }}">
                        </div>
                        <div class="col-12">
                            <label for="address">{{ __('Exact address and plaque') }}</label>
                            <input id="address" name="address" type="text"
                                   class="form-control @error('address') is-invalid @enderror"
                                   value="{{ old('address', $visit->address) }}"
                                   autocomplete="street-address" required>
                        </div>
                    </div>
                    <div class="visit-actions">
                        <button type="submit" class="btn btn-primary w-100">
                            {{ __('Register shop') }}
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const purchaseRadios = document.querySelectorAll('input[name="has_purchase"]');
            const reasonBox = document.getElementById('visit-reason');
            purchaseRadios.forEach(function (radio) {
                radio.addEventListener('change', function () {
                    if (!reasonBox) {
                        return;
                    }
                    reasonBox.classList.toggle('is-open', radio.value === '0' && radio.checked);
                });
            });

            const mobile = document.getElementById('mobile');
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

            const states = @json($statesPayload);
            const stateSelect = document.getElementById('state_id');
            const citySelect = document.getElementById('city_id');
            function fillCities(stateId, selectedId) {
                if (!citySelect) {
                    return;
                }
                const state = states.find(function (item) {
                    return String(item.id) === String(stateId);
                });
                citySelect.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = @json(__('Choose'));
                citySelect.appendChild(placeholder);
                if (!state) {
                    return;
                }
                state.cities.forEach(function (city) {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.textContent = city.name;
                    if (selectedId !== null && selectedId !== '' && String(city.id) === String(selectedId)) {
                        option.selected = true;
                    }
                    citySelect.appendChild(option);
                });
            }
            if (stateSelect && citySelect) {
                stateSelect.addEventListener('change', function () {
                    fillCities(stateSelect.value, null);
                });
            }

            const mallSelect = document.getElementById('mall');
            const mallOtherWrap = document.getElementById('mall-other-wrap');
            if (mallSelect && mallOtherWrap) {
                mallSelect.addEventListener('change', function () {
                    mallOtherWrap.style.display = mallSelect.value === '__other__' ? '' : 'none';
                });
            }
        });
    </script>
@endsection
