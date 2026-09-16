@extends('admin.templates.panel-list-template')

@section('title')
    {{ __('Stock audit') }} -
@endsection

@section('list-title')
    <i class="ri-archive-stack-fill text-primary"></i>
    {{ __('Stock audit') }}
@endsection

@section('top-content')
    <div class="row g-3 mb-4">
        <!-- Total Stock Count Box -->
        <div class="col-xl-3 col-md-6">
            <div class="card border border-primary-subtle shadow-sm h-100 rounded-3">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <span class="text-muted fs-12 fw-semibold d-block mb-1">
                            <i class="ri-stack-line text-primary me-1"></i>{{ __('Net stock') }}
                        </span>
                        <h4 class="fw-bold text-dark mb-1">
                            {{ number_format($stockStats['total_count'] ?? 0) }}
                            <small class="text-muted fs-13 fw-normal">{{ __('pieces') }}</small>
                        </h4>
                        <div class="text-muted fs-11 mt-1 d-flex align-items-center gap-2">
                            <span><i class="ri-coins-line text-warning me-0.5"></i>{{ __('Gold') }}: <b>{{ number_format($stockStats['gold_count'] ?? 0) }}</b></span>
                            <span>·</span>
                            <span><i class="ri-vip-diamond-line text-secondary me-0.5"></i>{{ __('Silver') }}: <b>{{ number_format($stockStats['silver_count'] ?? 0) }}</b></span>
                        </div>
                    </div>
                    <div class="bg-primary-subtle text-primary rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="ri-stack-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Stock Weight Box -->
        <div class="col-xl-3 col-md-6">
            <div class="card border border-warning-subtle shadow-sm h-100 rounded-3">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <span class="text-muted fs-12 fw-semibold d-block mb-1">
                            <i class="ri-scales-3-line text-warning me-1"></i>{{ __('Total stock weight') }}
                        </span>
                        <h4 class="fw-bold text-dark mb-1">
                            {{ \App\Services\AdminDashboardStats::formatWeight($stockStats['total_weight'] ?? 0) }}
                            <small class="text-muted fs-13 fw-normal">{{ __('g') }}</small>
                        </h4>
                        <div class="text-muted fs-11 mt-1 d-flex align-items-center gap-2">
                            <span><i class="ri-coins-line text-warning me-0.5"></i>{{ __('Gold') }}: <b>{{ \App\Services\AdminDashboardStats::formatWeight($stockStats['gold_weight'] ?? 0) }}</b> {{ __('g') }}</span>
                            <span>·</span>
                            <span><i class="ri-vip-diamond-line text-secondary me-0.5"></i>{{ __('Silver') }}: <b>{{ \App\Services\AdminDashboardStats::formatWeight($stockStats['silver_weight'] ?? 0) }}</b> {{ __('g') }}</span>
                        </div>
                    </div>
                    <div class="bg-warning-subtle text-warning rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="ri-scales-3-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sold and Scrapped Overview Box -->
        <div class="col-xl-3 col-md-6">
            <div class="card border border-danger-subtle shadow-sm h-100 rounded-3">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <span class="text-muted fs-12 fw-semibold d-block mb-1">
                            <i class="ri-file-chart-line text-danger me-1"></i>{{ __('Sold & Scrapped pieces') }}
                        </span>
                        <div class="d-flex align-items-center gap-3 my-1">
                            <div>
                                <span class="fs-11 text-muted d-block">{{ __('Sold') }}</span>
                                <span class="fw-bold text-dark fs-16">{{ number_format($stockStats['total_sold_count'] ?? 0) }}</span>
                            </div>
                            <div class="border-start ps-3">
                                <span class="fs-11 text-muted d-block">{{ __('Scrapped') }}</span>
                                <span class="fw-bold text-danger fs-16">{{ number_format($stockStats['total_scrapped_count'] ?? 0) }}</span>
                            </div>
                        </div>
                        <div class="text-muted fs-11 mt-1">
                            <span>{{ __('Excluded from net sellable stock') }}</span>
                        </div>
                    </div>
                    <div class="bg-danger-subtle text-danger rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="ri-delete-bin-7-line fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Estimated Inventory Value -->
        <div class="col-xl-3 col-md-6">
            <div class="card border border-success-subtle shadow-sm h-100 rounded-3">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <span class="text-muted fs-12 fw-semibold d-block mb-1">
                            <i class="ri-money-dollar-circle-line text-success me-1"></i>{{ __('Total inventory value') }}
                        </span>
                        <h4 class="fw-bold text-dark mb-1">
                            {{ number_format($stockStats['total_value'] ?? 0) }}
                            <small class="text-muted fs-13 fw-normal">{{ __('Toman') }}</small>
                        </h4>
                        <div class="text-muted fs-11 mt-1">
                            <span>{{ __('Live calculated retail value') }}</span>
                        </div>
                    </div>
                    <div class="bg-success-subtle text-success rounded-3 p-2.5 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="ri-bank-card-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('filter')
    <select name="filter[category_id]" class="form-select form-select-sm w-auto">
        <option value="">{{__("All categories")}}</option>
        @foreach(\App\Models\Category::all(['id','name']) as $cat)
            <option value="{{$cat->id}}" @if(request()->input('filter.category_id') == $cat->id) selected @endif>
                {{$cat->name}}
            </option>
        @endforeach
    </select>

    <select name="filter[stock_condition]" class="form-select form-select-sm w-auto">
        <option value="in_stock" @if(request()->input('filter.stock_condition', 'in_stock') === 'in_stock') selected @endif>
            {{__("In-stock only")}} ({{ number_format($quickCounts['in_stock'] ?? 0) }})
        </option>
        <option value="all" @if(request()->input('filter.stock_condition') === 'all') selected @endif>
            {{__("All inventory items")}} ({{ number_format($quickCounts['all'] ?? 0) }})
        </option>
        <option value="has_scrapped" @if(request()->input('filter.stock_condition') === 'has_scrapped') selected @endif>
            {{__("With scrapped pieces")}} ({{ number_format($quickCounts['has_scrapped'] ?? 0) }})
        </option>
        <option value="has_sold" @if(request()->input('filter.stock_condition') === 'has_sold') selected @endif>
            {{__("With sold pieces")}} ({{ number_format($quickCounts['has_sold'] ?? 0) }})
        </option>
        <option value="out_of_stock" @if(request()->input('filter.stock_condition') === 'out_of_stock') selected @endif>
            {{__("Out of stock")}}
        </option>
    </select>

    <select name="sort" class="form-select form-select-sm w-auto">
        <option value="">{{__("Default sorting")}}</option>
        <option value="most_sold" @if(request()->input('sort') === 'most_sold') selected @endif>
            {{__("Most sold")}}
        </option>
        <option value="most_scrapped" @if(request()->input('sort') === 'most_scrapped') selected @endif>
            {{__("Most scrapped / defective")}}
        </option>
        <option value="total_ordered" @if(request()->input('sort') === 'total_ordered') selected @endif>
            {{__("Most entered pieces")}}
        </option>
        <option value="stock_quantity" @if(request()->input('sort') === 'stock_quantity') selected @endif>
            {{__("Highest net stock")}}
        </option>
        <option value="total_weight" @if(request()->input('sort') === 'total_weight') selected @endif>
            {{__("Highest weight")}}
        </option>
    </select>
@endsection

@section('list-foot')
    <!-- Piece Codes Inspection & Scrap Modal -->
    <div class="modal fade" id="stockPiecesModal" tabindex="-1" aria-labelledby="stockPiecesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-3 shadow">
                <div class="modal-header bg-light py-2.5 px-3">
                    <div>
                        <h6 class="modal-title fw-bold text-dark fs-14 mb-0" id="stockPiecesModalLabel">
                            <i class="ri-barcode-line text-primary me-1"></i>
                            {{ __('Product piece codes') }}
                        </h6>
                        <small class="text-muted" id="modalProductSubtitle">—</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div id="modalLoadingSpinner" class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                        <span class="ms-2 fs-13 text-muted">{{ __('Loading pieces...') }}</span>
                    </div>
                    <div id="modalPiecesTableWrapper" class="d-none">
                        <div class="alert alert-light border d-flex align-items-center justify-content-between p-2 mb-3 rounded-2 fs-12">
                            <div>
                                <span class="fw-semibold">{{ __('Net sellable stock:') }}</span>
                                <strong id="modalNetStockCount" class="text-success fs-14">0</strong> {{ __('pieces') }}
                            </div>
                            <div class="text-muted">
                                <i class="ri-information-line text-primary"></i>
                                {{ __('Scrapped or sold pieces are automatically excluded from net stock.') }}
                            </div>
                        </div>
                        <div class="table-responsive" style="max-height: 55vh; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle mb-0 fs-13">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('Piece SKU') }}</th>
                                        <th>{{ __('Weight') }}</th>
                                        <th>{{ __('Price') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th class="text-end">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="modalPiecesTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light d-flex justify-content-between">
                    <span class="text-muted fs-12">
                        <i class="ri-shield-check-line text-success"></i>
                        {{ __('Scrap action instantly updates net inventory.') }}
                    </span>
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('stockPiecesModal');
            if (!modalEl) return;

            const spinner = document.getElementById('modalLoadingSpinner');
            const wrapper = document.getElementById('modalPiecesTableWrapper');
            const tbody = document.getElementById('modalPiecesTableBody');
            const subtitle = document.getElementById('modalProductSubtitle');
            const netStockEl = document.getElementById('modalNetStockCount');

            const piecesUrlTemplate = @json(route('admin.stock.pieces', ['product' => ':id']));
            const toggleScrapUrlTemplate = @json(route('admin.stock.piece.toggle-scrap', ['quantity' => ':id']));

            function getModalInstance() {
                if (window.bootstrap && window.bootstrap.Modal) {
                    return window.bootstrap.Modal.getOrCreateInstance(modalEl);
                }
                return null;
            }

            // Event delegation for opening the pieces modal
            document.addEventListener('click', function (e) {
                const button = e.target.closest('.view-pieces-btn');
                if (!button) return;

                e.preventDefault();
                const productId = button.getAttribute('data-product-id');
                const productName = button.getAttribute('data-product-name') || '';

                subtitle.textContent = productName;
                tbody.innerHTML = '';
                spinner.classList.remove('d-none');
                wrapper.classList.add('d-none');

                const modal = getModalInstance();
                if (modal) {
                    modal.show();
                }

                const fetchUrl = piecesUrlTemplate.replace(':id', encodeURIComponent(productId));

                fetch(fetchUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => {
                    if (!res.ok) throw new Error('HTTP error ' + res.status);
                    return res.json();
                })
                .then(data => {
                    subtitle.textContent = `${data.product.name} (${data.product.sku})`;
                    netStockEl.textContent = Number(data.product.stock_quantity).toLocaleString('fa-IR');

                    tbody.innerHTML = '';
                    if (!data.pieces || data.pieces.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">${@json(__('No stock pieces yet.'))}</td></tr>`;
                    } else {
                        data.pieces.forEach((piece, index) => {
                            const tr = document.createElement('tr');
                            tr.id = `piece-row-${piece.id}`;
                            if (piece.is_scrapped) tr.classList.add('table-danger');

                            const toggleBtn = piece.is_sold
                                ? `<span class="text-muted fs-12">—</span>`
                                : `<button type="button" class="btn btn-xs ${piece.is_scrapped ? 'btn-danger' : 'btn-outline-danger'} toggle-scrap-btn py-0.5 px-2 fs-11" data-piece-id="${piece.id}">
                                        <i class="${piece.is_scrapped ? 'ri-restart-line' : 'ri-fire-line'} me-1"></i>
                                        ${piece.is_scrapped ? @json(__('Restore piece')) : @json(__('Scrap product'))}
                                   </button>`;

                            tr.innerHTML = `
                                <td class="text-muted fs-11">${index + 1}</td>
                                <td><code class="fw-bold text-primary font-monospace">${piece.code}</code></td>
                                <td>${Number(piece.weight).toLocaleString('fa-IR')} <small class="text-muted">${@json(__('g'))}</small></td>
                                <td>${Number(piece.price).toLocaleString('fa-IR')} <small class="text-muted">${@json(__('Toman'))}</small></td>
                                <td><span id="badge-${piece.id}" class="${piece.badge_class}">${piece.status_label}</span></td>
                                <td class="text-end" id="action-${piece.id}">${toggleBtn}</td>
                            `;
                            tbody.appendChild(tr);
                        });
                    }

                    spinner.classList.add('d-none');
                    wrapper.classList.remove('d-none');
                })
                .catch(err => {
                    console.error(err);
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-3">${@json(__('Failed to load piece details.'))}</td></tr>`;
                    spinner.classList.add('d-none');
                    wrapper.classList.remove('d-none');
                });
            });

            // Event delegation for live scrap toggle
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.toggle-scrap-btn');
                if (!btn) return;

                e.preventDefault();
                const pieceId = btn.getAttribute('data-piece-id');
                btn.disabled = true;

                const toggleUrl = toggleScrapUrlTemplate.replace(':id', encodeURIComponent(pieceId));
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || @json(csrf_token());

                fetch(toggleUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => {
                    if (!res.ok) throw new Error('HTTP error ' + res.status);
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        const row = document.getElementById(`piece-row-${pieceId}`);
                        const badge = document.getElementById(`badge-${pieceId}`);
                        const actionCell = document.getElementById(`action-${pieceId}`);

                        netStockEl.textContent = Number(data.product_stock_quantity).toLocaleString('fa-IR');

                        if (data.is_scrapped) {
                            row?.classList.add('table-danger');
                        } else {
                            row?.classList.remove('table-danger');
                        }

                        if (badge) {
                            badge.className = data.badge_class;
                            badge.textContent = data.status_label;
                        }

                        if (actionCell) {
                            actionCell.innerHTML = `
                                <button type="button" class="btn btn-xs ${data.is_scrapped ? 'btn-danger' : 'btn-outline-danger'} toggle-scrap-btn py-0.5 px-2 fs-11" data-piece-id="${pieceId}">
                                    <i class="${data.is_scrapped ? 'ri-restart-line' : 'ri-fire-line'} me-1"></i>
                                    ${data.is_scrapped ? @json(__('Restore piece')) : @json(__('Scrap product'))}
                                </button>
                            `;
                        }
                    }
                })
                .catch(err => {
                    console.error(err);
                    btn.disabled = false;
                });
            });
        });
    </script>
@endsection
