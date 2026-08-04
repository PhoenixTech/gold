@if(hasRoute('bulk'))
    <div class="d-flex align-items-center justify-content-end gap-2">
        <select data-bulk-action class="form-select form-select-sm" name="action"
                style="min-width:150px">
            <option value=""></option>
            @if(strpos(request()->url(),'trashed') != false)
                <option value="restore">{{__("Batch restore")}}</option>
            @else
                <option value="delete">{{__("Batch delete")}}</option>
            @endif
            @yield('bulk')
        </select>
        <button type="submit" data-bulk-run class="btn btn-sm btn-primary" disabled>
            <i class="ri-check-double-line"></i>
            {{__("Run")}}<span data-bulk-count class="d-none"></span>
        </button>
    </div>
@endif
