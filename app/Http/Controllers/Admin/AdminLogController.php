<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\User;
use App\Services\Admin\AdminTableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLogController extends Controller
{
    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(AdminLog::class)
            ->columns(['action', 'user_id', 'created_at'], ['id', 'loggable_type', 'loggable_id'])
            ->searchable(['action'])
            ->buttons([])
            ->build($request);

        return view('admin.commons.adminlogs', $tableData);
    }

    public function log(User|string|int $user): RedirectResponse
    {
        $id = $user instanceof User ? $user->id : (User::where('email', $user)->value('id') ?? $user);

        return redirect()->route('admin.adminlog.index', ['filter[user_id]' => '['.$id.']']);
    }

    public function cleanup(): RedirectResponse
    {
        $count = AdminLog::where('created_at', '<=', now()->subMonth())->delete();

        return redirect()->route('admin.adminlog.index')
            ->with('message', __(':COUNT items deleted successfully', ['COUNT' => $count]));
    }
}
