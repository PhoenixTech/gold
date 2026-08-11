@extends('admin.templates.panel-form-template')
@section('title')
    @if(isset($item))
        {{__("Edit bank account")}} [{{$item->bank_name}}]
    @else
        {{__("Add new bank account")}}
    @endif -
@endsection
@section('form')

    <div class="row">
        <div class="col-lg-3">

            @include('components.err')
            <div class="item-list mb-3">
                <h3 class="p-3">
                    <i class="ri-message-3-line"></i>
                    {{__("Tips")}}
                </h3>
                <ul>
                    <li>
                        {{__("Only one bank account can be active at a time. The active account is shown to customers for card-to-card payment.")}}
                    </li>
                    <li>
                        {{__("Provide at least one of card number, account number, or IBAN.")}}
                    </li>
                </ul>
            </div>

        </div>
        <div class="col-lg-9 ps-xl-1 ps-xxl-1">
            <div class="general-form ">

                <h1>
                    @if(isset($item))
                        {{__("Edit bank account")}} [{{$item->bank_name}}]
                    @else
                        {{__("Add new bank account")}}
                    @endif
                </h1>

                <div class="row">
                    <div class="col-md-6 mt-3">
                        <div class="form-group">
                            <label for="bank_name">
                                {{__('Bank name')}}
                            </label>
                            <input name="bank_name" id="bank_name" type="text"
                                   class="form-control @error('bank_name') is-invalid @enderror"
                                   placeholder="{{__('Bank name')}}"
                                   value="{{old('bank_name',$item->bank_name??null)}}"/>
                        </div>
                    </div>
                    <div class="col-md-6 mt-3">
                        <div class="form-group">
                            <label for="account_holder_name">
                                {{__('Account holder name')}}
                            </label>
                            <input name="account_holder_name" id="account_holder_name" type="text"
                                   class="form-control @error('account_holder_name') is-invalid @enderror"
                                   placeholder="{{__('Account holder name')}}"
                                   value="{{old('account_holder_name',$item->account_holder_name??null)}}"/>
                        </div>
                    </div>
                    <div class="col-md-6 mt-3">
                        <div class="form-group">
                            <label for="card_number">
                                {{__('Card number')}}
                            </label>
                            <input name="card_number" id="card_number" type="text" dir="ltr"
                                   class="form-control @error('card_number') is-invalid @enderror"
                                   placeholder="{{__('Card number')}}"
                                   value="{{old('card_number',$item->card_number??null)}}"/>
                        </div>
                    </div>
                    <div class="col-md-6 mt-3">
                        <div class="form-group">
                            <label for="account_number">
                                {{__('Account number')}}
                            </label>
                            <input name="account_number" id="account_number" type="text" dir="ltr"
                                   class="form-control @error('account_number') is-invalid @enderror"
                                   placeholder="{{__('Account number')}}"
                                   value="{{old('account_number',$item->account_number??null)}}"/>
                        </div>
                    </div>
                    <div class="col-md-12 mt-3">
                        <div class="form-group">
                            <label for="iban">
                                {{__('IBAN')}}
                            </label>
                            <input name="iban" id="iban" type="text" dir="ltr"
                                   class="form-control @error('iban') is-invalid @enderror"
                                   placeholder="{{__('IBAN')}}"
                                   value="{{old('iban',$item->iban??null)}}"/>
                        </div>
                    </div>
                    <div class="col-md-12 mt-3">
                        <div class="form-group">
                            <div class="form-check form-switch">
                                <input value="1"
                                       class="form-check-input @error('is_active') is-invalid @enderror"
                                       name="is_active"
                                       @if(old('is_active', isset($item) && $item->is_active)) checked @endif
                                       type="checkbox" id="is_active">
                                <label class="form-check-label" for="is_active">{{__('Active account')}}</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <label> &nbsp; </label>
                        <input name="" type="submit" class="btn btn-primary mt-2" value="{{__('Save')}}"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
