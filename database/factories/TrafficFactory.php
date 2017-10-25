<?php

/*
  |--------------------------------------------------------------------------
  | Model Factories
  |--------------------------------------------------------------------------
  |
  | Here you may define all of your model factories. Model factories give
  | you a convenient way to create models for testing and seeding your
  | database. Just tell the factory how a default model should look.
  |
*/

$factory->define(App\Traffic::class, function ($faker) {
    return [        
        'time' => $faker->date.' '.$faker->time,
    ];
});