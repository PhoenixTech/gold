<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\TicketSaveRequest;
use App\Models\Ticket;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Ticket::whereNull('parent_id'))
            ->columns(['title', 'status', 'customer_id'], ['id'])
            ->searchable([])
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-close-line'],
            ])
            ->build($request);

        return view('admin.tickets.ticket-list', $tableData);
    }

    public function create(): View
    {
        return view('admin.tickets.ticket-form');
    }

    public function store(TicketSaveRequest $request): JsonResponse|RedirectResponse
    {
        $ticket = new Ticket;
        $ticket->fill($request->validated());
        $ticket->save();

        logAdmin(__METHOD__, Ticket::class, $ticket->id);

        return $this->respondAfterSave($request, $ticket, __('As you wished created successfully'), 'admin.ticket.edit');
    }

    public function edit(Ticket|string|int $item): View
    {
        $item = $this->resolveTicket($item);

        return view('admin.tickets.ticket-form', compact('item'));
    }

    public function update(Request $request, Ticket|string|int $item): JsonResponse|RedirectResponse
    {
        $item = $this->resolveTicket($item);
        $item->answer = $request->input('answer');
        $item->status = $request->input('status');
        $item->user_id = auth()->id();
        $item->save();

        if ($request->has('answers')) {
            foreach ($request->input('answers', []) as $id => $answer) {
                Ticket::where('id', $id)->update(['answer' => $answer]);
            }
        }

        logAdmin(__METHOD__, Ticket::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.ticket.edit');
    }

    public function destroy(Ticket|string|int $item): RedirectResponse
    {
        $item = $this->resolveTicket($item);

        logAdmin(__METHOD__, Ticket::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(
            Ticket::class,
            $request->input('action'),
            (array) $request->input('id', []),
            function (string $action, ?string $subAction, array $ids): ?string {
                if ($action === 'close') {
                    Ticket::whereIn('id', $ids)->update(['status' => 'CLOSED']);

                    return __(':COUNT items closed successfully', ['COUNT' => count($ids)]);
                }

                if ($action === 'pending') {
                    Ticket::whereIn('id', $ids)->update(['status' => 'PENDING']);

                    return __(':COUNT items pending successfully', ['COUNT' => count($ids)]);
                }

                if ($action === 'answered') {
                    Ticket::whereIn('id', $ids)->update(['status' => 'ANSWERED']);

                    return __(':COUNT items answered successfully', ['COUNT' => count($ids)]);
                }

                return null;
            }
        );
    }

    protected function resolveTicket(Ticket|string|int $item): Ticket
    {
        if ($item instanceof Ticket) {
            return $item;
        }

        return Ticket::where('id', $item)->firstOrFail();
    }
}
