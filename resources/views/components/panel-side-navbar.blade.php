<nav id="panel-navbar">
    <ul>
        <li>
            <a href="{{route('client.welcome')}}" target="_blank" class="dsb-item">
                <i class="ri-external-link-line"></i>
                <span class="nav-label">{{__("View Website")}}</span>
            </a>
        </li>

        @if(auth()->user()->hasAnyAccesses(['product', 'category', 'invoice', 'bank-account', 'transport', 'customer', 'discount', 'prop', 'rate', 'evaluation']))
            <li>
                <a href="#shop" class="dsb-item">
                    <i class="ri-store-2-line"></i>
                    <span class="nav-label">{{__("Shop")}}</span>
                    <i class="ri-arrow-down-s-line nav-chevron"></i>
                </a>
                <ul id="shop">
                    @if(auth()->user()->hasAnyAccess('product'))
                        <li>
                            <a href="{{route('admin.product.index')}}">
                                <i class="ri-vip-diamond-fill"></i>
                                {{__('Products')}}
                            </a>
                        </li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('category'))
                        <li>
                            <a href="{{route('admin.category.index')}}">
                                <i class="ri-box-3-fill"></i>
                                {{__('Categories')}}
                            </a>
                        </li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('invoice'))
                        <li>
                            <a href="{{ route('admin.invoice.index') }}">
                                <i class="ri-file-list-3-fill"></i>
                                {{__('Invoices')}}
                            </a>
                        </li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('bank-account'))
                        <li>
                            <a href="{{ route('admin.bank-account.index') }}">
                                <i class="ri-bank-card-line"></i>
                                {{__('Bank accounts')}}
                            </a>
                        </li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('transport'))
                        <li>
                            <a href="{{ route('admin.transport.index') }}">
                                <i class="ri-truck-fill"></i>
                                {{__('Transports')}}
                            </a>
                        </li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('customer'))
                        <li>
                            <a href="{{route('admin.customer.index')}}">
                                <i class="ri-team-fill"></i>
                                {{__('Customers')}}
                            </a>
                        </li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('discount'))
                        <li>
                            <a href="{{route('admin.discount.index')}}">
                                <i class="ri-percent-fill"></i>
                                {{__('Discounts')}}
                            </a>
                        </li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('prop'))
                        <li>
                            <a href="{{route('admin.prop.index')}}">
                                <i class="ri-price-tag-3-fill"></i>
                                {{__('Product attributes')}}
                            </a>
                        </li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('rate'))
                        <li>
                            <a href="{{ route('admin.rate.index') }}">
                                <i class="ri-star-half-line"></i>
                                {{__('Reviews')}}
                            </a>
                        </li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('evaluation'))
                        <li>
                            <a href="{{ route('admin.evaluation.index') }}">
                                <i class="ri-list-check-3"></i>
                                {{__('Rating criteria')}}
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        @if(auth()->user()->hasAnyAccesses(['post', 'group', 'adv', 'gallery', 'clip', 'attachment', 'tags']))
            <li>
                <a href="#website" class="dsb-item">
                    <i class="ri-article-line"></i>
                    <span class="nav-label">{{__("Website content")}}</span>
                    <i class="ri-arrow-down-s-line nav-chevron"></i>
                </a>
                <ul id="website">
                    @if(auth()->user()->hasAnyAccess('post'))
                        <li><a href="{{route('admin.post.index')}}"><i class="ri-megaphone-fill"></i>{{__('Posts')}}</a></li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('group'))
                        <li><a href="{{route('admin.group.index')}}"><i class="ri-book-3-fill"></i>{{__('Post topics')}}</a></li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('gallery'))
                        <li><a href="{{route('admin.gallery.index')}}"><i class="ri-gallery-fill"></i>{{__("Galleries")}}</a></li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('clip'))
                        <li><a href="{{route('admin.clip.index')}}"><i class="ri-video-fill"></i>{{__("Videos")}}</a></li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('adv'))
                        <li><a href="{{route('admin.adv.index')}}"><i class="ri-advertisement-line"></i>{{__("Ads")}}</a></li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('tags'))
                        <li><a href="{{route('admin.tag.index')}}"><i class="ri-price-tag-3-line"></i>{{__("Tags")}}</a></li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('attachment'))
                        <li><a href="{{route('admin.attachment.index')}}"><i class="ri-attachment-2"></i>{{__("Files")}}</a></li>
                    @endif
                </ul>
            </li>
        @endif

        @if(auth()->user()->hasAnyAccesses(['menu', 'slider']) || auth()->user()->hasRole('developer'))
            <li>
                <a href="#appearance" class="dsb-item">
                    <i class="ri-palette-line"></i>
                    <span class="nav-label">{{__("Appearance")}}</span>
                    <i class="ri-arrow-down-s-line nav-chevron"></i>
                </a>
                <ul id="appearance">
                    @if(auth()->user()->hasAnyAccess('menu'))
                        <li><a href="{{route('admin.menu.index')}}"><i class="ri-list-check"></i>{{__("Menus")}}</a></li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('slider'))
                        <li><a href="{{route('admin.slider.index')}}"><i class="ri-image-fill"></i>{{__("Slider")}}</a></li>
                    @endif
                    @if(auth()->user()->hasRole('developer'))
                        <li><a href="{{route('admin.gfx.index')}}"><i class="ri-color-filter-line"></i>{{__("Colors")}}</a></li>
                        <li><a href="{{route('admin.area.index')}}"><i class="ri-paint-brush-line"></i>{{__("Page layout")}}</a></li>
                    @endif
                </ul>
            </li>
        @endif

        @if(auth()->user()->hasAnyAccesses(['question', 'ticket', 'comment', 'contact']))
            <li>
                <a href="#support" class="dsb-item">
                    <i class="ri-customer-service-2-line"></i>
                    <span class="nav-label">{{__("Customer support")}}</span>
                    <i class="ri-arrow-down-s-line nav-chevron"></i>
                </a>
                <ul id="support">
                    @if(auth()->user()->hasAnyAccess('ticket'))
                        <li><a href="{{route('admin.ticket.index')}}"><i class="ri-mail-fill"></i>{{__('Tickets')}}</a></li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('comment'))
                        <li><a href="{{route('admin.comment.index')}}"><i class="ri-chat-1-fill"></i>{{__('Comments')}}</a></li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('contact'))
                        <li><a href="{{route('admin.contact.index')}}"><i class="ri-mail-unread-fill"></i>{{__("Contact messages")}}</a></li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('question'))
                        <li><a href="{{route('admin.question.index')}}"><i class="ri-question-mark"></i>{{__('FAQ')}}</a></li>
                    @endif
                </ul>
            </li>
        @endif

        @if(auth()->user()->hasAnyAccesses(['user', 'state', 'city', 'adminlog', 'guestlog']))
            <li>
                <a href="#staff" class="dsb-item">
                    <i class="ri-shield-user-line"></i>
                    <span class="nav-label">{{__("Staff and logs")}}</span>
                    <i class="ri-arrow-down-s-line nav-chevron"></i>
                </a>
                <ul id="staff">
                    @if(auth()->user()->hasAnyAccess('user'))
                        <li><a href="{{route('admin.user.index')}}"><i class="ri-user-line"></i>{{__("Staff")}}</a></li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('state'))
                        <li><a href="{{route('admin.state.index')}}"><i class="ri-map-line"></i>{{__("Provinces")}}</a></li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('city'))
                        <li><a href="{{route('admin.city.index')}}"><i class="ri-map-2-line"></i>{{__("Cities")}}</a></li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('adminlog'))
                        <li><a href="{{route('admin.adminlog.index')}}"><i class="ri-list-check-3"></i>{{__('Admin logs')}}</a></li>
                    @endif
                    @if(auth()->user()->hasAnyAccess('guestlog'))
                        <li><a href="{{route('admin.guestlog.index')}}"><i class="ri-eye-line"></i>{{__('Visitor logs')}}</a></li>
                    @endif
                    @if(auth()->user()->hasRole('developer') && config('app.xlang.active'))
                        <li><a href="{{ route('admin.lang.index') }}"><i class="ri-global-fill"></i>{{__("Languages")}}</a></li>
                    @endif
                </ul>
            </li>
        @endif

        @if(auth()->user()->hasAnyAccess('setting'))
            <li>
                <a href="{{route('admin.setting.index')}}" class="dsb-item">
                    <i class="ri-settings-4-line"></i>
                    <span class="nav-label">{{__("Settings")}}</span>
                </a>
            </li>
        @endif
    </ul>
</nav>
