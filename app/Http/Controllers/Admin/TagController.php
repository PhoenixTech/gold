<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminTableService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Tags\Tag;

class TagController extends Controller
{
    public function index(Request $request, AdminTableService $tableService): View
    {
        $tableData = $tableService->for(Tag::class)
            ->columns(['name', 'slug'], ['id'])
            ->searchable(['name', 'slug'])
            ->buttons([])
            ->build($request);

        return view('admin.tags.tag-list', $tableData);
    }
}
