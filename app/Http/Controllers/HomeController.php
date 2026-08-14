<?php

namespace App\Http\Controllers;

use App\Helpers\TDate;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Visitor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // make device data
        $mobiles_count = Visitor::where('created_at', '>=', Carbon::now()->subMonth())->where('is_mobile', 1)->count();
        $all_visitor = Visitor::where('created_at', '>=', Carbon::now()->subMonth())->count();

        return view('home', compact('all_visitor', 'mobiles_count'));
    }
}
