<?php

namespace App\Services;

use App\Enums\ShopVisitStatus;
use App\Models\ShopVisit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ShopVisitRecorder
{
    public function current(User $user): ShopVisit
    {
        return DB::transaction(function () use ($user): ShopVisit {
            $open = ShopVisit::query()
                ->where('user_id', $user->id)
                ->open()
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($open !== null) {
                return $open;
            }

            return ShopVisit::query()->create([
                'user_id' => $user->id,
                'status' => ShopVisitStatus::Collecting,
                'state_id' => ShopVisit::tehranStateId(),
                'city_id' => ShopVisit::tehranCityId(),
            ]);
        });
    }

    /**
     * @param  array{mobile: string, first_name: string, last_name: string, has_purchase: bool, has_own_workshop: bool, other_reason: ?string}  $data
     */
    public function saveStepOne(ShopVisit $visit, array $data): ShopVisit
    {
        $visit->fill([
            'mobile' => $data['mobile'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'has_purchase' => $data['has_purchase'],
            'has_own_workshop' => $data['has_own_workshop'],
            'other_reason' => $data['other_reason'],
            'status' => ShopVisitStatus::StepTwo,
        ]);
        $visit->save();

        return $visit;
    }

    /**
     * @param  array{categories: list<string>, work_styles: list<string>, state_id: int, city_id: int, mall: string, address: string}  $data
     */
    public function saveStepTwo(ShopVisit $visit, array $data): ShopVisit
    {
        $visit->fill([
            'categories' => $data['categories'],
            'work_styles' => $data['work_styles'],
            'state_id' => $data['state_id'],
            'city_id' => $data['city_id'],
            'mall' => $data['mall'],
            'address' => $data['address'],
            'status' => ShopVisitStatus::Completed,
            'submitted_at' => now(),
        ]);
        $visit->save();

        return $visit;
    }
}
