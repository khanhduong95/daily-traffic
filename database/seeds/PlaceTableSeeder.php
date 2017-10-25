<?php

use App\Place;
use Illuminate\Database\Seeder;

class PlaceTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (! Place::first()){
            for ($i = 0; $i < 10; $i++)
                factory(Place::class)->create();
        }
    }
}
