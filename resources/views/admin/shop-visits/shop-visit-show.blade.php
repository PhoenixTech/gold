@extends('layouts.app')

@section('title')
    {{ __('Shop visit') }} -
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">{{ __('Shop visit') }}</h4>
        <a href="{{ route('admin.shop-visit.index') }}" class="btn btn-outline-secondary btn-sm">
            {{ __('Show list') }}
        </a>
    </div>

    <table class="table table-bordered table-striped mb-4">
        <tr>
            <th>{{ __('submitted_at') }}</th>
            <td>{{ $item->submittedAtLabel() }}</td>
        </tr>
        <tr>
            <th>{{ __('Status') }}</th>
            <td><span class="{{ $item->statusBadgeClass() }}">{{ $item->statusLabel() }}</span></td>
        </tr>
        <tr>
            <th>{{ __('Mobile number') }}</th>
            <td dir="ltr">{{ $item->mobile }}</td>
        </tr>
        <tr>
            <th>{{ __('First name') }}</th>
            <td>{{ $item->first_name }}</td>
        </tr>
        <tr>
            <th>{{ __('Last name') }}</th>
            <td>{{ $item->last_name }}</td>
        </tr>
        <tr>
            <th>{{ __('Do you have a purchase?') }}</th>
            <td>{{ $item->purchaseLabel() }}</td>
        </tr>
        <tr>
            <th>{{ __('Has a personal production workshop') }}</th>
            <td>{{ $item->has_own_workshop ? __('Yes') : __('No') }}</td>
        </tr>
        <tr>
            <th>{{ __('Explain other reasons') }}</th>
            <td>{{ $item->other_reason ?: '-' }}</td>
        </tr>
        <tr>
            <th>{{ __('Shop categories') }}</th>
            <td>{{ implode('، ', $item->categoryLabels()) ?: '-' }}</td>
        </tr>
        <tr>
            <th>{{ __('Work style') }}</th>
            <td>{{ implode('، ', $item->workStyleLabels()) ?: '-' }}</td>
        </tr>
        <tr>
            <th>{{ __('Province') }}</th>
            <td>{{ $item->state?->name ?: '-' }}</td>
        </tr>
        <tr>
            <th>{{ __('City') }}</th>
            <td>{{ $item->city?->name ?: '-' }}</td>
        </tr>
        <tr>
            <th>{{ __('Shopping mall') }}</th>
            <td>{{ $item->mall ?: '-' }}</td>
        </tr>
        <tr>
            <th>{{ __('Exact address and plaque') }}</th>
            <td>{{ $item->address ?: '-' }}</td>
        </tr>
        <tr>
            <th>{{ __('Visitor') }}</th>
            <td>{{ $item->user?->name ?: __('Removed') }}</td>
        </tr>
    </table>
@endsection
