<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\EvaluationSaveRequest;
use App\Models\Evaluation;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Evaluation::class)
            ->columns(['title'], ['id'])
            ->searchable(['title'])
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-close-line'],
            ])
            ->build($request);

        return view('admin.evaluations.evaluation-list', $tableData);
    }

    public function create(): View
    {
        return view('admin.evaluations.evaluation-form');
    }

    public function store(EvaluationSaveRequest $request): JsonResponse|RedirectResponse
    {
        $evaluation = new Evaluation;
        $this->saveEvaluationData($evaluation, $request);

        logAdmin(__METHOD__, Evaluation::class, $evaluation->id);

        return $this->respondAfterSave($request, $evaluation, __('As you wished created successfully'), 'admin.evaluation.edit');
    }

    public function edit(Evaluation|string|int $item): View
    {
        $item = $this->resolveEvaluation($item);

        return view('admin.evaluations.evaluation-form', compact('item'));
    }

    public function update(EvaluationSaveRequest $request, Evaluation|string|int $item): JsonResponse|RedirectResponse
    {
        $item = $this->resolveEvaluation($item);
        $this->saveEvaluationData($item, $request);

        logAdmin(__METHOD__, Evaluation::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.evaluation.edit');
    }

    public function destroy(Evaluation|string|int $item): RedirectResponse
    {
        $item = $this->resolveEvaluation($item);

        logAdmin(__METHOD__, Evaluation::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Evaluation::onlyTrashed())
            ->columns(['title'], ['id', 'deleted_at'])
            ->searchable(['title'])
            ->buttons([
                'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
            ])
            ->build($request);

        return view('admin.evaluations.evaluation-list', $tableData);
    }

    public function restore($item): RedirectResponse
    {
        $target = Evaluation::withTrashed()->where('id', $item)->firstOrFail();

        logAdmin(__METHOD__, Evaluation::class, $target->id);
        $target->restore();

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(Evaluation::class, $request->input('action'), (array) $request->input('id', []));
    }

    protected function resolveEvaluation(Evaluation|string|int $item): Evaluation
    {
        if ($item instanceof Evaluation) {
            return $item;
        }

        return Evaluation::where('id', $item)->firstOrFail();
    }

    protected function saveEvaluationData(Evaluation $evaluation, Request $request): void
    {
        $evaluation->title = $request->input('title');
        $evaluation->evaluationable_type = $request->filled('evaluationable_type') ? $request->input('evaluationable_type') : null;
        $evaluation->evaluationable_id = $request->filled('evaluationable_id') ? $request->input('evaluationable_id') : null;
        $evaluation->save();
    }
}
