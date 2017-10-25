<?php

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (! User::first()){
            User::insert([
                'name' => 'Administrator',
                'email' => 'admin@example.com',
                'password' => Hash::make('123456'),
            ]);
            for ($i = 0; $i < 10; $i++)
                factory(User::class)->create([
                    'password' => app('hash')->make('123456'.$i),
                ]);
        }
    }
}
