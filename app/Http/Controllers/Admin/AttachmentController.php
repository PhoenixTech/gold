<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttachmentSaveRequest;
use App\Models\Attachment;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use App\Services\SlugService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttachmentController extends Controller
{
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Attachment::class)
            ->columns(['title', 'ext', 'is_fillable'], ['slug', 'id'])
            ->searchable(['title', 'subtitle', 'body'])
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'show' => ['title' => 'Detail', 'class' => 'btn-outline-secondary', 'icon' => 'ri-eye-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-delete-bin-line'],
            ])
            ->build($request);

        return view('admin.attachments.attachment-list', $tableData);
    }

    public function create(): View
    {
        return view('admin.attachments.attachment-form');
    }

    public function store(AttachmentSaveRequest $request, SlugService $slugService): JsonResponse|RedirectResponse
    {
        $attachment = new Attachment;
        $this->saveAttachmentData($attachment, $request, $slugService);

        logAdmin(__METHOD__, Attachment::class, $attachment->id);

        return $this->respondAfterSave($request, $attachment, __('As you wished created successfully'), 'admin.attachment.edit');
    }

    public function edit(Attachment|string|int $item): View
    {
        $item = $this->resolveAttachment($item);

        return view('admin.attachments.attachment-form', compact('item'));
    }

    public function update(AttachmentSaveRequest $request, Attachment|string|int $item, SlugService $slugService): JsonResponse|RedirectResponse
    {
        $item = $this->resolveAttachment($item);
        $this->saveAttachmentData($item, $request, $slugService);

        logAdmin(__METHOD__, Attachment::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.attachment.edit');
    }

    public function destroy(Attachment|string|int $item): RedirectResponse
    {
        $item = $this->resolveAttachment($item);

        logAdmin(__METHOD__, Attachment::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function detach(Attachment|string|int $item)
    {
        $attachment = $this->resolveAttachment($item);
        $attachment->attachable_id = null;
        $attachment->attachable_type = null;
        $attachment->save();

        logAdmin(__METHOD__, static::class, $attachment->id);

        if (request()->ajax()) {
            return ['OK' => true, 'message' => __('As you wished detached successfully')];
        }

        return redirect()->back()->with(['message' => __('As you wished detached successfully')]);
    }

    public function attaching(Request $request, SlugService $slugService): array
    {
        $attachment = new Attachment;
        $this->saveAttachmentData($attachment, $request, $slugService);

        logAdmin(__METHOD__, static::class, $attachment->id);

        return ['OK' => true, 'data' => $attachment, 'message' => __('File uploaded successfully')];
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(Attachment::class, $request->input('action'), (array) $request->input('id', []));
    }

    public function show($item)
    {
        $attachment = $this->resolveAttachment($item);
        if ($attachment && method_exists($attachment, 'webUrl')) {
            return redirect($attachment->webUrl());
        }

        return redirect()->route('admin.attachment.index');
    }

    protected function resolveAttachment(Attachment|string|int $item): Attachment
    {
        if ($item instanceof Attachment) {
            return $item;
        }

        return Attachment::where('slug', $item)->first()
            ?? Attachment::where('id', $item)->firstOrFail();
    }

    protected function saveAttachmentData(Attachment $attachment, Request $request, SlugService $slugService): void
    {
        $attachment->title = $request->input('title');
        $titleForSlug = $request->filled('slug') ? $request->input('slug') : $attachment->title;
        $attachment->slug = $slugService->makeUnique(Attachment::class, $titleForSlug, $attachment->id);
        $attachment->body = $request->input('body');
        $attachment->subtitle = $request->input('subtitle');
        $attachment->is_fillable = $request->has('is_fillable');

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $name = time().'-'.$file->getClientOriginalName();
            $file->storeAs('public/attachments', $name);
            $attachment->file = $name;
            $attachment->size = $file->getSize();
            $attachment->ext = $file->getClientOriginalExtension();
        }

        if ($request->filled('attachable_id')) {
            $attachment->attachable_type = $request->input('attachable_type');
            $attachment->attachable_id = $request->input('attachable_id');
        } else {
            $attachment->attachable_type = null;
            $attachment->attachable_id = null;
        }

        $attachment->save();
    }
}
