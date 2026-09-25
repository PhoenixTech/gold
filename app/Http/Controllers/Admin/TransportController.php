<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransportSaveRequest;
use App\Models\Transport;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransportController extends Controller
{
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Transport::class)
            ->columns(['title', 'price', 'is_default', 'icon'], ['id'])
            ->searchable(['title', 'description'])
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-close-line'],
            ])
            ->build($request);

        return view('admin.transports.transport-list', $tableData);
    }

    public function create(): View
    {
        return view('admin.transports.transport-form');
    }

    public function store(TransportSaveRequest $request): JsonResponse|RedirectResponse
    {
        $transport = new Transport;
        $this->saveTransportData($transport, $request);

        logAdmin(__METHOD__, Transport::class, $transport->id);

        return $this->respondAfterSave($request, $transport, __('As you wished created successfully'), 'admin.transport.edit');
    }

    public function edit(Transport|string|int $item): View
    {
        $item = $this->resolveTransport($item);

        return view('admin.transports.transport-form', compact('item'));
    }

    public function update(TransportSaveRequest $request, Transport|string|int $item): JsonResponse|RedirectResponse
    {
        $item = $this->resolveTransport($item);
        $this->saveTransportData($item, $request);

        logAdmin(__METHOD__, Transport::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.transport.edit');
    }

    public function destroy(Transport|string|int $item): RedirectResponse
    {
        $item = $this->resolveTransport($item);

        logAdmin(__METHOD__, Transport::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Transport::onlyTrashed())
            ->columns(['title', 'price', 'is_default', 'icon'], ['id', 'deleted_at'])
            ->searchable(['title', 'description'])
            ->buttons([
                'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
            ])
            ->build($request);

        return view('admin.transports.transport-list', $tableData);
    }

    public function restore($item): RedirectResponse
    {
        $target = Transport::withTrashed()->where('id', $item)->firstOrFail();

        logAdmin(__METHOD__, Transport::class, $target->id);
        $target->restore();

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(Transport::class, $request->input('action'), (array) $request->input('id', []));
    }

    protected function resolveTransport(Transport|string|int $item): Transport
    {
        if ($item instanceof Transport) {
            return $item;
        }

        return Transport::where('id', $item)->firstOrFail();
    }

    protected function saveTransportData(Transport $transport, Request $request): void
    {
        $transport->price = $request->input('price');
        $transport->title = $request->input('title');
        $transport->icon = $request->input('icon');
        $transport->description = $request->input('description');
        $transport->is_default = $request->has('is_default');
        $transport->requires_delivery_code = $request->has('requires_delivery_code');

        if ($request->has('is_default')) {
            Transport::where('is_default', 1)
                ->when($transport->id, fn ($q) => $q->where('id', '<>', $transport->id))
                ->update(['is_default' => 0]);
            $transport->is_default = 1;
        }

        $transport->save();
    }
}
