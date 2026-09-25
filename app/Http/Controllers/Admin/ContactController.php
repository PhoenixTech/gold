<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminModel;
use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContactSaveRequest;
use App\Models\Contact;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    use ResolvesAdminModel;
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Contact::class)
            ->columns(['name', 'subject', 'mobile', 'email', 'created_at', 'is_answered'], ['id', 'hash'])
            ->searchable(['name', 'subject', 'mobile', 'email', 'body'])
            ->buttons([
                'show' => ['title' => 'Detail', 'class' => 'btn-outline-secondary', 'icon' => 'ri-eye-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-delete-bin-line'],
            ])
            ->build($request);

        return view('admin.contacts.contact-list', $tableData);
    }

    public function show($hash): View
    {
        $item = Contact::where('hash', $hash)->orWhere('id', $hash)->firstOrFail();

        return view('admin.contacts.contact-show', compact('item'));
    }

    public function create(): View
    {
        return view('admin.contacts.contact-form');
    }

    public function store(ContactSaveRequest $request): JsonResponse|RedirectResponse
    {
        $contact = new Contact;
        $contact->fill($request->validated());
        $contact->save();

        logAdmin(__METHOD__, Contact::class, $contact->id);

        return $this->respondAfterSave($request, $contact, __('As you wished created successfully'), 'admin.contact.show');
    }

    public function edit(Contact|string|int $item): View
    {
        $item = $this->resolveContact($item);

        return view('admin.contacts.contact-form', compact('item'));
    }

    public function update(Request $request, Contact|string|int $item): JsonResponse|RedirectResponse
    {
        $item = $this->resolveContact($item);
        $item->fill($request->all());
        $item->save();

        logAdmin(__METHOD__, Contact::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.contact.show');
    }

    public function destroy(Contact|string|int $item): RedirectResponse
    {
        $item = $this->resolveContact($item);

        logAdmin(__METHOD__, Contact::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(Contact::class, $request->input('action'), (array) $request->input('id', []));
    }

    public function reply(Request $request, Contact|string|int $item): RedirectResponse
    {
        $item = $this->resolveContact($item);
        $body = (string) $request->input('bodya', $request->input('body'));

        $item->is_answered = true;
        $item->body .= '<hr>'.__('Answer: <br>').$body;
        $item->save();

        Mail::raw($body, function ($message) use ($item) {
            $message->from(getSetting('email', config('mail.from.address')), config('app.name'));
            $message->to($item->email);
            $message->subject('reply: '.config('app.name', 'xshop').' پاسخ تماس با ');
        });

        logAdmin(__METHOD__, Contact::class, $item->id);

        return redirect()->back()->with(['message' => __('Your Email sent')]);
    }

    protected function resolveContact(Contact|string|int $item): Contact
    {
        return $this->resolveModel(Contact::class, $item);
    }
}
