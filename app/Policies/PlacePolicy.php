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
				      'id' => $user->id,
				      'table_name' => Place::TABLE_NAME,
				      'write' => true,
				      ])->first() != null;
    }

}