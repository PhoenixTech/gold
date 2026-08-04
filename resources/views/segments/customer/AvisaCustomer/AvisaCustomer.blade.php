<section id='AvisaCustomer' class=' live-setting' data-live="{{$data->area_name.'_'.$data->part}}">
<div class="{{gfx()['container']}}">
        <button class="avisa-menu-btn d-lg-none" id="avisa-menu-btn" type="button" aria-label="Menu">
            <i class="ri-menu-3-line"></i>
            {{__("User menu")}}
        </button>
        <div class="avisa-backdrop d-lg-none" id="avisa-backdrop"></div>
        <div class="row">
            <div class="col-lg-3">
                <div class="avisa-sidebar" id="avisa-sidebar">
                    <div class="avisa-user">
                        <img src="{{auth('customer')->user()->avatar()}}"  alt="[avatar]" class="avisa-avatar" onclick="document.querySelector('#avatar').click();">
                        <div class="avisa-user-meta">
                            <small>
                                {{__("Welcome back")}}
                            </small>
                            <strong>
                                {{auth('customer')->user()->name}}
                            </strong>
                        </div>
                        <button class="avisa-close-btn d-lg-none" id="avisa-close-btn" type="button" aria-label="Close">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                <ul class="tab-control" id="avisa-tabs">
                    <li>
                        <a href="#summary" class="active">
                            <i class="ri-home-2-line"></i>
                            {{__("Summary")}}
                        </a>
                    </li>
                    <li>
                        <a href="#invoices">
                            <i class="ri-file-list-3-line"></i>
                            {{__("Invoices")}}
                        </a>
                    </li>
                    <li>
                        <a href="#profile">
                            <i class="ri-user-3-line"></i>
                            {{__("Profile")}}
                        </a>
                    </li>
                    <li>
                        <a href="#addresses">
                            <i class="ri-map-pin-user-line"></i>
                            {{__("Addresses")}}
                        </a>
                    </li>
                    <li>
                        <a href="#credit">
                            <i class="ri-bank-card-2-line"></i>
                            {{__("Credit")}}
                        </a>
                    </li>
                    <li>
                        <a href="#tickets">
                            <i class="ri-customer-service-fill"></i>
                            {{__("Tickets")}}
                        </a>
                    </li>
                    <li>
                        <a href="#submit-ticket">
                            <i class="ri-mail-add-line"></i>
                            {{__("Submit new ticket")}}
                        </a>
                    </li>
                    <li>
                        <a href="#comments">
                            <i class="ri-message-2-line"></i>
                            {{__("Comments")}}
                        </a>
                    </li>
                    <li>
                        <a href="#favs">
                            <i class="ri-hearts-line"></i>
                            {{__("Favorites")}}
                        </a>
                    </li>
                    <li>
                        <a href="{{route('client.sign-out')}}">
                            <i class="ri-logout-box-line"></i>
                            {{__("Sign-out")}}
                        </a>
                    </li>
                </ul>
                </div>
            </div>
            <div class="col-lg-9" id="tabs-content">

                @include('components.err')
                @if(cardCount() > 0)
                    <div class="alert alert-info mt-4">
                        <a href="{{ route('client.card') }}" class="btn btn-primary float-end">
                            {{__("Continue")}}
                        </a>
                        <h5 class="alert-heading">
                            {{__("System notification")}}
                        </h5>
                        {{__("You have some products in your shopping card.")}}
                        <br>
                    </div>
                @endif
                @if( auth('customer')->user()->name == null || trim(auth('customer')->user()->name) == '')
                    <div class="alert alert-danger mt-4">
                        <h5 class="alert-heading">
                            {{__("System notification")}}
                        </h5>
                        {{__("Your information is insufficient, Please complete your information")}}
                    </div>
                @endif
                @if(  auth('customer')->user()->addresses()->count() == 0)
                    <div class="alert alert-danger mt-4">
                        <h5 class="alert-heading">
                            {{__("System notification")}}
                        </h5>
                        {{__("You need at least one address to order, Please add address")}}
                    </div>
                @endif
                <div class="tab active" id="summary">
                    <div class="row">
                        <div class="avisa-grid col-lg-3 col-md-6">
                            <div class="grid-item">
                                <i class="ri-list-check-3"></i>
                                <h2>
                                    {{number_format(auth('customer')->user()->invoices()->count())}}
                                </h2>
                                <h3>
                                    {{__("Invoices")}}
                                </h3>
                            </div>
                        </div>
                        <div class="avisa-grid col-lg-3 col-md-6">
                            <div class="grid-item">
                                <i class="ri-bank-card-2-line"></i>
                                <h3>
                                    {{__("Credits")}}
                                </h3>
                                <h2>
                                    {{number_format(auth('customer')->user()->credit)}}
                                    {{config('app.currency.symbol')}}
                                </h2>
                            </div>
                        </div>
                        <div class="avisa-grid col-lg-3 col-md-6">
                            <div class="grid-item">
                                <i class="ri-customer-service-2-line"></i>
                                <h2>
                                    {{number_format(auth('customer')->user()->tickets()->count())}}
                                </h2>
                                <h3>
                                    {{__("Tickets")}}
                                </h3>
                            </div>
                        </div>
                        <div class="avisa-grid col-lg-3 col-md-6">
                            <div class="grid-item">
                                <i class="ri-map-pin-line"></i>
                                <h2>
                                    {{number_format(auth('customer')->user()->addresses()->count())}}
                                </h2>
                                <h3>
                                    {{__("Addresses")}}
                                </h3>
                            </div>
                        </div>
                        <div class="avisa-grid col-md-6">
                            <div class="grid-item">
                                <i class="ri-message-3-line"></i>
                                <h2>
                                    {{number_format(auth('customer')->user()->comments()->count())}}
                                </h2>
                                <h3>
                                    {{__("Comments")}}
                                </h3>
                            </div>
                        </div>
                        <div class="avisa-grid col-md-6">
                            <div class="grid-item">
                                <i class="ri-hearts-line"></i>
                                <h2>
                                    {{number_format(auth('customer')->user()->favorites()->count())}}
                                </h2>
                                <h3>
                                    {{__("Favorites")}}
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab" id="invoices">
                    <div class="avisa-table-card">
                        <div class="avisa-table-head">
                            <h4>
                                <i class="ri-file-list-3-line"></i>
                                {{__("Invoices")}}
                            </h4>
                            <span class="avisa-count-badge">{{number_format(auth('customer')->user()->invoices()->count())}}</span>
                        </div>
                        <div class="avisa-table-wrap">
                            <table class="avisa-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{__("Datetime")}}</th>
                                        <th>{{__("Orders count")}}</th>
                                        <th>{{__("Total price")}}</th>
                                        <th>{{__("Status")}}</th>
                                        <th class="text-end">{{__("Actions")}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(auth('customer')->user()->invoices()->orderByDesc('id')->get() as $inv)
                                        <tr>
                                            <td data-label="#"> {{$inv->hash}} </td>
                                            <td data-label="{{__('Datetime')}}">{{$inv->created_at->ldate('Y-m-d H:i')}}</td>
                                            <td data-label="{{__('Orders count')}}">{{number_format($inv->count)}}</td>
                                            <td data-label="{{__('Total price')}}">
                                                <b>{{number_format($inv->total_price)}} {{config('app.currency.symbol')}}</b>
                                            </td>
                                            <td data-label="{{__('Status')}}">
                                                <span class="inv-badge inv-{{$inv->status}}">{{__($inv->status)}}</span>
                                            </td>
                                            <td data-label="{{__('Actions')}}" class="avisa-row-actions">
                                                <a href="{{ route('client.invoice',$inv->hash) }}"
                                                   class="avisa-icon-btn" title="{{__('View')}}">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                                @if( in_array($inv->status, ['PENDING', 'CANCELED', 'FAILED'] ) && $inv->created_at->timestamp >  (time() - 3600) )
                                                    <a href="{{route('client.pay',$inv->hash)}}"
                                                       class="avisa-pay-btn">
                                                        <i class="ri-secure-payment-line"></i>
                                                        {{__("Pay now")}}
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab" id="profile">
                    <div class="avisa-profile-head">
                        <img src="{{auth('customer')->user()->avatar()}}" alt="avatar"
                             class="avisa-profile-avatar"
                             onclick="document.querySelector('#avatar')?.click();">
                        <div class="avisa-profile-info">
                            <h4>{{auth('customer')->user()->name}}</h4>
                            <span>{{auth('customer')->user()->mobile}}</span>
                        </div>
                        <label class="avisa-upload-btn" for="avatar">
                            <i class="ri-image-add-line"></i>
                            {{__("Change avatar")}}
                        </label>
                    </div>
                    <div class="avisa-hint">
                        <i class="ri-information-line"></i>
                        {{__("If you want to change the password, choose both the same. Otherwise, leave the password field blank.")}}
                    </div>
                    <div class="avisa-panel">
                    <form action="{{route('client.profile.save')}}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 mt-3">
                                <div class="form-group">
                                    <label for="name">
                                        {{__('Name')}}
                                    </label>
                                    <input name="name" type="text"
                                           class="form-control @error('name') is-invalid @enderror"
                                           placeholder="{{__('Name')}}"
                                           value="{{old('name',auth('customer')->user()->name??null)}}"/>
                                </div>
                            </div>
                            <div class="col-md-4 mt-3">
                                <div class="form-group">
                                    <label for="email">
                                        {{__('Email')}}
                                    </label>
                                    <input name="email" type="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           placeholder="{{__('Email')}}"
                                           value="{{old('email',auth('customer')->user()->email??null)}}"/>
                                </div>
                            </div>
                            <div class="col-md-4 mt-3">
                                <div class="form-group">
                                    <label for="mobile">
                                        {{__('Mobile')}}
                                    </label>
                                    <input name="mobile" type="text" @if(config('app.sms.sign'))  readonly
                                           @endif  class="form-control @error('mobile') is-invalid @enderror"
                                           placeholder="{{__('Mobile')}}"
                                           value="{{old('mobile',auth('customer')->user()->mobile??null)}}"
                                           min-length="10"/>
                                </div>
                            </div>
                            <div class="col-md-3 mt-3">
                                <div class="form-group">
                                    <label for="dp">
                                        {{__('Date of born')}}
                                    </label>
                                    <vue-datetime-picker-input
                                        :xmax="{{strtotime('yesterday')}}"
                                        xid="dp" xname="dob" xtitle="{{__("Date of born")}}"
                                        @if(app()->getLocale() != 'fa')  def-tab="1" xshow="date"  @else xshow="pdate"  @endif
                                        :xvalue="{{strtotime(auth('customer')->user()->dob)}}"
                                        :timepicker="false"
                                    ></vue-datetime-picker-input>
                                </div>
                            </div>
                            <div class="col-md-3 mt-3">
                                <label for="height">
                                    {{__('Height')}}
                                </label>
                                <input name="height" type="text"
                                       class="form-control @error('height') is-invalid @enderror"
                                       placeholder="{{__('Height')}}"
                                       value="{{old('height',auth('customer')->user()->height??null)}}"
                                       minlength="2"/>
                            </div>
                            <div class="col-md-3 mt-3">
                                <label for="weight">
                                    {{__('Weight')}}
                                </label>
                                <input name="weight" type="text"
                                       class="form-control @error('weight') is-invalid @enderror"
                                       placeholder="{{__('Weight')}}"
                                       value="{{old('weight',auth('customer')->user()->weight??null)}}"
                                       minlength="2"/>
                            </div>
                            <div class="col-md-3 mt-3">
                                <label for="sex">
                                    {{__('Sex')}}
                                </label>
                                <select name="sex" id="sex" class="form-control">
                                    <option value="MALE"> {{__("Male")}} </option>
                                    <option value="FEMALE"
                                            @if(auth('customer')->user()->sex == 'FEMALE') selected @endif> {{__("Female")}} </option>
                                </select>
                            </div>
                            <div class="col-md-4 mt-3">
                                <div class="form-group">
                                    <label for="password">
                                        {{__('Password')}}
                                    </label>
                                    <input name="password" type="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           placeholder="{{__('Password')}}" value="{{old('password',''??null)}}"/>
                                </div>
                            </div>
                            <div class="col-md-4 mt-3">
                                <div class="form-group">
                                    <label for="password_confirmation">
                                        {{__('password repeat')}}
                                    </label>
                                    <input name="password_confirmation" type="password"
                                           class="form-control @error('password_confirmation') is-invalid @enderror"
                                           placeholder="{{__('password repeat')}}"
                                           value="{{old('password_confirmation',$item->password_confirmation??null)}}"/>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <input type="file" name="avatar" class="d-none" id="avatar" accept="image/jpeg">
                                <button type="submit" class="avisa-pay-btn w-100 justify-content-center">
                                    <i class="ri-save-3-line"></i>
                                    {{__('Save')}}
                                </button>
                            </div>
                        </div>
                    </form>
                    </div>
                </div>
                <div class="tab" id="credit">
                    <div class="avisa-grid">
                        <div class="grid-item">
                            <i class="ri-bank-card-2-line"></i>
                            <h3>
                                {{__("Credits")}}
                            </h3>
                            <h2>
                                {{number_format(auth('customer')->user()->credit)}}
                                {{config('app.currency.symbol')}}
                            </h2>

                        </div>
                    </div>
                    <h5 class="my-3">
                        {{__("Credit history")}}
                    </h5>
                    @foreach(auth('customer')->user()->credits as $cr)
                        <div class="avisa-credit-item">
                            <div class="avisa-credit-top">
                                <span class="avisa-credit-date">
                                    <i class="ri-time-line"></i>
                                    {{$cr->created_at->ldate('Y-m-d H:i')}}
                                </span>
                                @if($cr->invoice_id != null)
                                    <a href="{{ route('client.invoice',$cr->invoice()->hash) }}"
                                       class="avisa-icon-btn" title="{{__('View')}}">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                @endif
                            </div>
                            <div class="avisa-credit-amount">
                                <i class="ri-bank-card-2-line"></i>
                                {{number_format($cr->amount)}} {{config('app.currency.symbol')}}
                            </div>
                            @php($data = json_decode($cr->data))
                            @if(isset($data->message))
                                <div class="avisa-credit-note">
                                    <i class="ri-chat-3-line"></i>
                                    {{$data->message}}
                                </div>
                            @endif
                        </div>
                    @endforeach
                    {{-- WIP add credit manual--}}

                </div>
                <div class="tab" id="tickets">
                    <div class="avisa-table-card">
                        <div class="avisa-table-head">
                            <h4>
                                <i class="ri-customer-service-2-line"></i>
                                {{__("Tickets")}}
                            </h4>
                        </div>
                        <div class="avisa-table-wrap">
                            <table class="avisa-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{__("Title")}}</th>
                                        <th>{{__("Status")}}</th>
                                        <th class="text-end">{{__("Actions")}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(auth('customer')->user()->main_tickets()->orderByDesc('id')->get() as $i =>  $ticket)
                                        <tr>
                                            <td data-label="#"> {{$i+1}} </td>
                                            <td data-label="{{__('Title')}}">{{$ticket->title}}</td>
                                            <td data-label="{{__('Status')}}">
                                                <span class="inv-badge inv-{{$ticket->status}}">{{__($ticket->status)}}</span>
                                            </td>
                                            <td data-label="{{__('Actions')}}" class="avisa-row-actions">
                                                <a href="{{ route('client.ticket.show',$ticket->id) }}"
                                                   class="avisa-pay-btn">
                                                    <i class="ri-eye-line"></i>
                                                    {{__("View")}}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab" id="comments">

                    @if(auth('customer')->user()->comments()->count() == 0)
                        <div class="alert alert-info">
                            {{__("You don't have any comments, We are so pleased to hear your look-out")}}
                        </div>
                    @else
                        @foreach(auth('customer')->user()->comments as $comment)
                            <div class="avisa-comment">
                                <h3>
                                    {{$comment->commentable->title}}
                                    {{$comment->commentable->name}}
                                </h3>
                                <span class="comment-date float-end">
                                    {{$comment->created_at->ldate('Y-m-d')}}
                                </span>
                                <p>
                                    {{$comment->body}}
                                </p>
                            </div>
                        @endforeach
                    @endif
                </div>
                <div class="tab" id="submit-ticket">
                    <div class="avisa-panel">
                    <form action="{{ route('client.ticket.submit') }}" method="post">
                        @csrf
                        <div class="form-group">
                            <label for="title">
                                {{__("Title")}}
                            </label>
                            <input type="text" id="title" name="title" value="{{old('title')}}"
                                   placeholder="{{__("Title")}}"
                                   class="form-control">
                        </div>
                        <div class="form-group mt-3">
                            <label for="body">
                                {{__("Description Text")}}
                            </label>
                            <textarea rows="7" name="body" class="form-control"
                                      placeholder="{{__("Your message ...")}}">{{old('body')}}</textarea>
                        </div>
                        <div class="mt-3">
                            <button class="avisa-pay-btn w-100 justify-content-center">
                                <i class="ri-send-plane-2-line"></i>
                                {{__("Send ticket")}}
                            </button>
                        </div>
                    </form>
                    </div>
                </div>
                <div class="tab" id="addresses">
                    <div class="avisa-panel">
                        <div class="avisa-panel-head">
                            <h4>
                                <i class="ri-map-pin-user-line"></i>
                                {{__("Addresses")}}
                            </h4>
                        </div>
                        <address-input
                            list-link="{{route('client.addresses')}}"
                            add-link="{{route('client.address.store')}}"
                            update-link="{{route('client.address.update','')}}"
                            rem-link="{{route('client.address.destroy','')}}"
                            state-link="{{route('v1.state.index')}}"
                            cities-link="{{route('v1.state.show','')}}"
                            :dark-mode="false"
                            :translate='{{vueTranslate([
            'addr-editor' => __('Address editor'),
            'state' => __('State'),
            'city' => __('City'),
            'address' => __('Address'),
            'post-code' => __('Post code'),
            'add-address' => __('Add address'),
            'save' => __('Save'),
            ])}}'
                        ></address-input>
                    </div>
                </div>
                <div class="tab" id="favs">
                    @foreach(auth('customer')->user()->favorites as $fav)

                        <div class="product-item">
                            <div class="row">
                                <div class="col-md-2">
                                    <img src="{{$fav->imgUrl()}}" class="img-fluid" alt="{{$fav->name}}" loading="lazy">
                                </div>
                                <div class="col-md-10">
                                    <h4>
                                        {{$fav->name}}
                                    </h4>
                                    <p class="text-muted">
                                        {{$fav->excerpt}}
                                    </p>
                                    <a class="fav-btn float-end mx-2" data-slug="{{$fav->slug}}"
                                       data-is-fav="{{$fav->isFav()}}"
                                       data-bs-custom-class="custom-tooltip"
                                       data-bs-toggle="tooltip" data-bs-placement="top"
                                       title="{{__("Add to / Remove from favorites")}}">
                                        <i class="ri-heart-line"></i>
                                        <i class="ri-heart-fill"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
