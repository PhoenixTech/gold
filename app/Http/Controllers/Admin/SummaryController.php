<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardStats;
use Illuminate\Contracts\View\View;

class SummaryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(AdminDashboardStats $dashboard): View
    {
        return view('admin.summary.index', array_merge($dashboard->summary(), [
            'canEditPrices' => auth()->user()->hasAnyAccess('setting'),
        ]));
    }
}
