<?php

namespace App\Http\Controllers;

use App\Services\AdminDashboardStats;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(AdminDashboardStats $dashboard): View
    {
        return view('home', array_merge($dashboard->data(), [
            'canEditPrices' => auth()->user()->hasAnyAccess('setting'),
        ]));
    }
}
