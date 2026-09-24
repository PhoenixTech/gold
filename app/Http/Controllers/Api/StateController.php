<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityCollection;
use App\Http\Resources\StateCollection;
use App\Models\State;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StateController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return StateCollection::collection(State::all());
    }

    public function show(State $state): AnonymousResourceCollection
    {
        return CityCollection::collection($state->cities);
    }
}
