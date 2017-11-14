<?php

namespace App\Policies;

use App\User;
use App\Visit;
use App\Permission;

class VisitPolicy
{
	public function readList(User $user)
	{
        if (substr_count($user->current_token, '.') < 2)
            return false;

		return Permission::where([
            'user_id' => $user->id,
            'table_name' => Visit::TABLE_NAME,
        ])->first() != null;		
	}

	public function read(User $user, Visit $visit)
	{
		if ($user->id == $visit->user_id)
			return true;
	    
        if (substr_count($user->current_token, '.') < 2)
            return false;

		return Permission::where([
            'user_id' => $user->id,
            'table_name' => Visit::TABLE_NAME,
        ])->first() != null;
	}

	public function write(User $user, Visit $visit)
	{
		if ($user->id == $visit->user_id)
			return true;
	    
        if (substr_count($user->current_token, '.') < 2)
            return false;

		return Permission::where([
            'user_id' => $user->id,
            'table_name' => Visit::TABLE_NAME,
            'write' => true,
        ])->first() != null;
	}
}