<?php

namespace App\Http\Controllers;

use App\Models\ShopVisit;
use App\Models\State;
use App\Services\AdminDashboardStats;
use App\Services\ShopVisitRecorder;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(AdminDashboardStats $dashboard, ShopVisitRecorder $recorder): View
    {
        if (auth()->user()->isVisitor()) {
            $visit = $recorder->current(auth()->user());
            $states = State::query()->with('cities')->orderBy('id')->get();
            $selectedStateId = old('state_id', $visit->state_id) ?: ShopVisit::tehranStateId();
            $selectedCityId = old('city_id', $visit->city_id) ?: ShopVisit::tehranCityId();

            return view('admin.shop-visits.visitor-form', [
                'visit' => $visit,
                'states' => $states,
                'selectedStateId' => $selectedStateId,
                'selectedCityId' => $selectedCityId,
            ]);
        }

        return view('home', array_merge($dashboard->data(), [
            'canEditPrices' => auth()->user()->hasAnyAccess('setting'),
        ]));
    }
}
