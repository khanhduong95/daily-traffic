<?php

namespace App\Policies;

use App\User;
use App\Place;
use App\Permission;

class PlacePolicy
{
    public function write(User $user)
    {
        return Permission::where([
            'user_id' => $user->id,
            'table_name' => 'places',
            'write' => true,
        ])->first() != null;
    }
}
