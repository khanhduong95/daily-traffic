<?php

namespace App\Policies;

use App\User;
use App\Traffic;
use App\Permission;

class TrafficPolicy
{
	public function readList(User $user)
	{
        if (substr_count($user->current_token, '.') < 2)
            return false;

		return Permission::where([
            'id' => $user->id,
            'table_name' => Traffic::TABLE_NAME,
        ])->first() != null;		
	}

	public function read(User $user, Traffic $traffic)
	{
		if ($user->id == $traffic->user_id)
			return true;
	    
        if (substr_count($user->current_token, '.') < 2)
            return false;

		return Permission::where([
            'id' => $user->id,
            'table_name' => Traffic::TABLE_NAME,
        ])->first() != null;
	}

	public function write(User $user, Traffic $traffic)
	{
		if ($user->id == $traffic->user_id)
			return true;
	    
        if (substr_count($user->current_token, '.') < 2)
            return false;

		return Permission::where([
            'id' => $user->id,
            'table_name' => Traffic::TABLE_NAME,
            'write' => true,
        ])->first() != null;
	}
}