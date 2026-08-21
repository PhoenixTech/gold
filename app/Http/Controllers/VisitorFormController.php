<?php

namespace App\Http\Controllers;

use App\Enums\ShopVisitStatus;
use App\Http\Requests\ShopVisitStepOneRequest;
use App\Http\Requests\ShopVisitStepTwoRequest;
use App\Services\ShopVisitRecorder;
use Illuminate\Http\RedirectResponse;

class VisitorFormController extends Controller
{
    public function storeStepOne(ShopVisitStepOneRequest $request, ShopVisitRecorder $recorder): RedirectResponse
    {
        $visit = $recorder->current($request->user());
        if ($visit->status === ShopVisitStatus::StepTwo) {
            return redirect()
                ->route('admin.home')
                ->withErrors(['mobile' => __('Finish the address step of the open form first.')]);
        }

        $recorder->saveStepOne($visit, [
            'mobile' => $request->string('mobile')->toString(),
            'first_name' => $request->string('first_name')->toString(),
            'last_name' => $request->string('last_name')->toString(),
            'has_purchase' => $request->boolean('has_purchase'),
            'has_own_workshop' => $request->boolean('has_purchase') ? false : $request->boolean('has_own_workshop'),
            'other_reason' => $request->boolean('has_purchase')
                ? null
                : ($request->filled('other_reason') ? $request->string('other_reason')->toString() : null),
        ]);

        return redirect()
            ->route('admin.home')
            ->with(['message' => __('Step one saved. Fill the address after leaving the shop.')]);
    }

    public function storeStepTwo(ShopVisitStepTwoRequest $request, ShopVisitRecorder $recorder): RedirectResponse
    {
        $visit = $recorder->current($request->user());
        if ($visit->status !== ShopVisitStatus::StepTwo) {
            return redirect()
                ->route('admin.home')
                ->withErrors(['address' => __('Confirm the first step before saving the address.')]);
        }

        $recorder->saveStepTwo($visit, [
            'categories' => array_values($request->input('categories', [])),
            'work_styles' => array_values($request->input('work_styles', [])),
            'state_id' => $request->integer('state_id'),
            'city_id' => $request->integer('city_id'),
            'mall' => $request->string('mall')->toString(),
            'address' => $request->string('address')->toString(),
        ]);

        return redirect()
            ->route('admin.home')
            ->with(['message' => __('Visit saved. You can start a new blank form.')]);
    }
}
