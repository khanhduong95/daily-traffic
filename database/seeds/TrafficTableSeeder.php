<?php

use App\User;
use App\Place;
use App\Traffic;
use Illuminate\Database\Seeder;

class TrafficTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (! Traffic::first()){
            $users = User::get();
            $places = Place::get();
            $numUsers = count($users);
            $numPlaces = count($places);
            for ($i = 0; $i < 50; $i++)
                factory(Traffic::class)->create([
                    'user_id' => $users[random_int(0, $numUsers - 1)]->id,
                    'place_id' => $places[random_int(0, $numPlaces - 1)]->id,
                ]);
        }
    }
}
