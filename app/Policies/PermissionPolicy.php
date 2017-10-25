<?php

namespace App\Policies;

use App\User;
use App\Permission;

class PermissionPolicy
{
    public function readList(User $user)
    {	    
        if (substr_count($user->current_token, '.') < 2)
            return false;

        return Permission::where([
            'user_id' => $user->id,
            'table_name' => Permission::TABLE_NAME,
        ])->first() != null;
    }

	public function read(User $user, Permission $permission)
	{
		if ($user->id == $permission->user_id)
			return true;
	    
        if (substr_count($user->current_token, '.') < 2)
            return false;

		return Permission::where([
            'user_id' => $user->id,
            'table_name' => Permission::TABLE_NAME,
        ])->first() != null;
	}

    public function write(User $user)
    {
        if (substr_count($user->current_token, '.') < 2)
            return false;
        
        return Permission::where([
            'user_id' => $user->id,
            'table_name' => Permission::TABLE_NAME,
            'write' => true,
        ])->first() != null;
    }
}