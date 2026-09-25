<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminModel;
use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionSaveRequest;
use App\Models\Question;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionController extends Controller
{
    use ResolvesAdminModel;
    use RespondsWithAdmin;

    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Question::class)
            ->columns(['body', 'product_id', 'status'], ['id'])
            ->searchable(['body', 'answer'])
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-close-line'],
            ])
            ->build($request);

        return view('admin.questions.question-list', $tableData);
    }

    public function create(): View
    {
        return view('admin.questions.question-form');
    }

    public function store(QuestionSaveRequest $request): JsonResponse|RedirectResponse
    {
        $question = new Question;
        $question->fill($request->validated());
        $question->save();

        logAdmin(__METHOD__, Question::class, $question->id);

        return $this->respondAfterSave($request, $question, __('As you wished created successfully'), 'admin.question.edit');
    }

    public function edit(Question|string|int $item): View
    {
        $item = $this->resolveQuestion($item);

        return view('admin.questions.question-form', compact('item'));
    }

    public function update(QuestionSaveRequest $request, Question|string|int $item): JsonResponse|RedirectResponse
    {
        $item = $this->resolveQuestion($item);
        $item->fill($request->validated());
        $item->save();

        logAdmin(__METHOD__, Question::class, $item->id);

        return $this->respondAfterSave($request, $item, __('As you wished updated successfully'), 'admin.question.edit');
    }

    public function destroy(Question|string|int $item): RedirectResponse
    {
        $item = $this->resolveQuestion($item);

        logAdmin(__METHOD__, Question::class, $item->id);
        $item->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(
            Question::class,
            $request->input('action'),
            (array) $request->input('id', []),
            function (string $action, ?string $subAction, array $ids): ?string {
                if ($action === 'publish') {
                    Question::whereIn('id', $ids)->update(['status' => 1]);

                    return __(':COUNT items published successfully', ['COUNT' => count($ids)]);
                }

                if ($action === 'draft') {
                    Question::whereIn('id', $ids)->update(['status' => 0]);

                    return __(':COUNT items drafted successfully', ['COUNT' => count($ids)]);
                }

                return null;
            }
        );
    }

    protected function resolveQuestion(Question|string|int $item): Question
    {
        return $this->resolveModel(Question::class, $item);
    }
}
