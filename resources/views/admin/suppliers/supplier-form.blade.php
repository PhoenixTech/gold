@extends('admin.templates.panel-form-template')

@section('title')
    @if(isset($item))
        {{ __('Edit supplier') }} [{{ $item->first_name }} {{ $item->last_name }}]
    @else
        {{ __('Add new supplier') }}
    @endif -
@endsection

@section('form')
    <div class="row">
        <div class="col-lg-3">
            @include('components.err')
            <div class="item-list mb-3">
                <h5 class="p-3">
                    <i class="ri-information-line"></i>
                    {{ __('Tips') }}
                </h5>
                <p class="px-3 pb-3 mb-0">
                    {{ __('Manage shop payment card numbers under Shop definitions → Bank accounts.') }}
                </p>
            </div>
        </div>

        <div class="col-lg-9 ps-xl-1 ps-xxl-1">
            <div class="general-form">
                <h3>
                    @if(isset($item))
                        {{ __('Edit supplier') }} [{{ $item->first_name }} {{ $item->last_name }}]
                    @else
                        {{ __('Add new supplier') }}
                    @endif
                </h3>

                <div class="row">
                    <div class="col-md-6 mt-3">
                        <div class="form-group">
                            <label for="first_name">{{ __('First name') }}</label>
                            <input name="first_name" id="first_name" type="text"
                                   class="form-control @error('first_name') is-invalid @enderror"
                                   value="{{ old('first_name', $item->first_name ?? null) }}" required>
                        </div>
                    </div>

                    <div class="col-md-6 mt-3">
                        <div class="form-group">
                            <label for="last_name">{{ __('Last name') }}</label>
                            <input name="last_name" id="last_name" type="text"
                                   class="form-control @error('last_name') is-invalid @enderror"
                                   value="{{ old('last_name', $item->last_name ?? null) }}" required>
                        </div>
                    </div>

                    <div class="col-md-12 mt-3">
                        <div class="form-group">
                            <label for="company_name">{{ __('Company name') }}</label>
                            <input name="company_name" id="company_name" type="text"
                                   class="form-control @error('company_name') is-invalid @enderror"
                                   value="{{ old('company_name', $item->company_name ?? null) }}">
                        </div>
                    </div>

                    <div class="col-md-6 mt-3">
                        <div class="form-group">
                            <label for="account_number">{{ __('Account number') }}</label>
                            <input name="account_number" id="account_number" type="text" dir="ltr"
                                   class="form-control @error('account_number') is-invalid @enderror"
                                   value="{{ old('account_number', $item->account_number ?? null) }}">
                        </div>
                    </div>

                    <div class="col-md-6 mt-3">
                        <div class="form-group">
                            <label for="iban">{{ __('IBAN') }}</label>
                            <input name="iban" id="iban" type="text" dir="ltr"
                                   class="form-control @error('iban') is-invalid @enderror"
                                   value="{{ old('iban', $item->iban ?? null) }}">
                        </div>
                    </div>

                    <div class="col-md-12 mt-3">
                        <input type="submit" class="btn btn-primary" value="{{ __('Save') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
