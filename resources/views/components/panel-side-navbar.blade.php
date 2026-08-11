<nav id="panel-navbar">
    <ul>
        <li>
            <a href="{{route('client.welcome')}}" target="_blank" class="dsb-item">
                <i class="ri-home-smile-fill"></i>
                <span class="nav-label">{{__("xShop")}}</span>
            </a>
        </li>

        @if(  auth()->user()->hasAnyAccesses(['customer','invoice','discount','rate','bank-account']) )
            <li>
                <a href="#card" class="dsb-item">
                    <i class="ri-shopping-cart-line"></i>
                    <span class="nav-label">{{__("Shopping card")}}</span>
                    <i class="ri-arrow-down-s-line nav-chevron"></i>
                </a>
                <ul id="card">
                    @if(  auth()->user()->hasAnyAccess( 'invoice' ))
                        <li>
                            <a href="{{ route('admin.invoice.index') }}">
                                <i class="ri-file-list-3-fill"></i>
                                {{__('Invoices')}}
                            </a>
                        </li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'bank-account' ))
                        <li>
                            <a href="{{ route('admin.bank-account.index') }}">
                                <i class="ri-bank-card-line"></i>
                                {{__('Bank accounts')}}
                            </a>
                        </li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'customer' ))
                        <li>
                            <a href="{{route('admin.customer.index')}}">
                                <i class="ri-team-fill"></i>
                                {{__('Customers')}}
                            </a>
                        </li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'discount' ))
                        <li>
                            <a href="{{route('admin.discount.index')}}">
                                <i class="ri-percent-fill"></i>
                                {{__('Discounts')}}
                            </a>
                        </li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'rate' ))
                        <li>
                            <a href="{{ route('admin.rate.index') }}">
                                <i class="ri-star-half-line"></i>
                                {{__('Rate')}}
                            </a>
                        </li>
                    @endif
                </ul>
            </li>
        @endif

        @if(  auth()->user()->hasAnyAccesses(['product','category','prop','transport','evaluation']) )
            <li>
                <a href="#catalog" class="dsb-item">
                    <i class="ri-store-line"></i>
                    <span class="nav-label">{{__("Catalog")}}</span>
                    <i class="ri-arrow-down-s-line nav-chevron"></i>
                </a>
                <ul id="catalog">
                    @if(  auth()->user()->hasAnyAccess( 'product' ))
                        <li><a href="{{route('admin.product.index')}}"><i class="ri-vip-diamond-fill"></i>{{__('Products')}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'category' ))
                        <li><a href="{{route('admin.category.index')}}"><i class="ri-box-3-fill"></i>{{__('Categories')}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'prop' ))
                        <li><a href="{{route('admin.prop.index')}}"><i class="ri-file-list-3-fill"></i>{{__("Properties meta")}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'transport' ))
                        <li><a href="{{ route('admin.transport.index') }}"><i class="ri-truck-fill"></i>{{__('Transports')}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'evaluation' ))
                        <li><a href="{{ route('admin.evaluation.index') }}"><i class="ri-star-half-line"></i>{{__('Evaluations')}}</a></li>
                    @endif
                </ul>
            </li>
        @endif

        @if(  auth()->user()->hasAnyAccesses(['post','group','adv','gallery','clip','attachment']) )
            <li>
                <a href="#contents" class="dsb-item">
                    <i class="ri-pages-line"></i>
                    <span class="nav-label">{{__("Contents")}}</span>
                    <i class="ri-arrow-down-s-line nav-chevron"></i>
                </a>
                <ul id="contents">
                    @if(  auth()->user()->hasAnyAccess( 'post' ))
                        <li><a href="{{route('admin.post.index')}}"><i class="ri-megaphone-fill"></i>{{__('Post')}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'group' ))
                        <li><a href="{{route('admin.group.index')}}"><i class="ri-book-3-fill"></i>{{__('Groups')}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'adv' ))
                        <li><a href="{{route('admin.adv.index')}}"><i class="ri-threads-line"></i>{{__("Advertise")}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'gallery' ))
                        <li><a href="{{route('admin.gallery.index')}}"><i class="ri-gallery-fill"></i>{{__("Galleries")}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'clip' ))
                        <li><a href="{{route('admin.clip.index')}}"><i class="ri-video-fill"></i>{{__("Video clips")}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'attachment' ))
                        <li><a href="{{route('admin.attachment.index')}}"><i class="ri-attachment-2"></i>{{__("Attachments")}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'tags' ))
                        <li><a href="{{route('admin.tag.index')}}"><i class="ri-price-tag-3-line"></i>{{__("Tags")}}</a></li>
                    @endif
                </ul>
            </li>
        @endif

        @if(  auth()->user()->hasAnyAccesses(['menu','slider','gfx','area']) )
            <li>
                <a href="#themes" class="dsb-item">
                    <i class="ri-palette-line"></i>
                    <span class="nav-label">{{__("Theme")}}</span>
                    <i class="ri-arrow-down-s-line nav-chevron"></i>
                </a>
                <ul id="themes">
                    @if(  auth()->user()->hasAnyAccess( 'menu' ))
                        <li><a href="{{route('admin.menu.index')}}"><i class="ri-list-check"></i>{{__("Menus")}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'slider' ))
                        <li><a href="{{route('admin.slider.index')}}"><i class="ri-image-fill"></i>{{__("Slider")}}</a></li>
                    @endif
                    @if(  auth()->user()->hasRole('developer') )
                        <li><a href="{{route('admin.gfx.index')}}"><i class="ri-color-filter-line"></i>{{__("Graphic")}}</a></li>
                    @endif
                    @if(  auth()->user()->hasRole('developer'))
                        <li><a href="{{route('admin.area.index')}}"><i class="ri-paint-brush-line"></i>{{__("Area design")}}</a></li>
                    @endif
                </ul>
            </li>
        @endif

        @if(  auth()->user()->hasAnyAccesses(['question','ticket','comment','contact']) )
            <li>
                <a href="#interaction" class="dsb-item">
                    <i class="ri-chat-1-line"></i>
                    <span class="nav-label">{{__("Interaction")}}</span>
                    <i class="ri-arrow-down-s-line nav-chevron"></i>
                </a>
                <ul id="interaction">
                    @if(  auth()->user()->hasAnyAccess( 'question' ))
                        <li><a href="{{route('admin.question.index')}}"><i class="ri-question-mark"></i>{{__('Questions')}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'ticket' ))
                        <li><a href="{{route('admin.ticket.index')}}"><i class="ri-mail-fill"></i>{{__('Tickets')}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'comment' ))
                        <li><a href="{{route('admin.comment.index')}}"><i class="ri-chat-1-fill"></i>{{__('Comments')}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'contact' ))
                        <li><a href="{{route('admin.contact.index')}}"><i class="ri-mail-unread-fill"></i>{{__("Contact us")}}</a></li>
                    @endif
                </ul>
            </li>
        @endif

        @if(  auth()->user()->hasAnyAccesses(['user','state','city','report','adminlog','guestlog']) )
            <li>
                <a href="#manage" class="dsb-item">
                    <i class="ri-pie-chart-line"></i>
                    <span class="nav-label">{{__("Managing")}}</span>
                    <i class="ri-arrow-down-s-line nav-chevron"></i>
                </a>
                <ul id="manage">
                    @if(  auth()->user()->hasAnyAccess( 'user' ))
                        <li><a href="{{route('admin.user.index')}}"><i class="ri-user-line"></i>{{__("Users")}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'state' ))
                        <li><a href="{{route('admin.state.index')}}"><i class="ri-map-line"></i>{{__("States")}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'city' ))
                        <li><a href="{{route('admin.city.index')}}"><i class="ri-map-2-line"></i>{{__("City")}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'report' ))
                        <li><a href=""><i class="ri-bar-chart-2-line"></i>{{__("Reports")}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'adminlog' ))
                        <li><a href="{{route('admin.adminlog.index')}}"><i class="ri-list-check-3"></i>{{__('Logs of admins')}}</a></li>
                    @endif
                    @if(  auth()->user()->hasAnyAccess( 'guestlog' ))
                        <li><a href="{{route('admin.guestlog.index')}}"><i class="ri-list-check-3"></i>{{__('Logs of guests')}}</a></li>
                    @endif
                    @if(auth()->user()->hasRole('developer') && config('app.xlang.active'))
                        <li><a href="{{ route('admin.lang.index') }}"><i class="ri-global-fill"></i>{{__("Languages")}}</a></li>
                    @endif
                </ul>
            </li>
        @endif

        @if(  auth()->user()->hasAnyAccess( 'setting' ))
            <li>
                <a href="{{route('admin.setting.index')}}" class="dsb-item">
                    <i class="ri-settings-4-line"></i>
                    <span class="nav-label">{{__("Setting")}}</span>
                </a>
            </li>
        @endif
    </ul>
</nav>
