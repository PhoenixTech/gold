<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\State;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $faker = Faker::create(config('app.faker_locale'));

        Customer::factory(35)->create();
        foreach (Customer::all() as $customer) {
            $s = State::inRandomOrder()->first();
            $c = $s->cities()->inRandomOrder()->first();
            $customer->addresses()->create([
                'state_id' => $s->id,
                'city_id' => $c->id,
                'zip' => rand(12345, 54321),
                'lat' => $c->lat,
                'lng' => $c->lng,
                'address' => $faker->address,
            ]);
        }
    }
}
