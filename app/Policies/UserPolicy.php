<?php

namespace App\Policies;

use App\User;
use App\Permission;

class UserPolicy
{
	public function read(User $user, User $targetUser)
	{
		if ($user->id == $targetUser->id)
			return true;
	    	    
        if (substr_count($user->current_token, '.') < 2)
            return false;

		return Permission::where([
            'user_id' => $user->id,
            'table_name' => User::TABLE_NAME,
        ])->first() != null;
	}

	public function write(User $user, User $targetUser)
	{
        if (substr_count($user->current_token, '.') < 2)
            return false;

		if ($user->id == $targetUser->id)
			return true;
	    
		return Permission::where([
            'user_id' => $user->id,
            'table_name' => User::TABLE_NAME,
            'write' => true,
        ])->first() != null;
	}

}