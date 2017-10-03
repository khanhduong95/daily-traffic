<?php

namespace App\Policies;

use App\User;
use App\Permission;

class UserPolicy
{
	public function readList(User $user)
	{
		return Permission::where([
					  'id' => $user->id,
					  'table_name' => User::TABLE_NAME,
					  ])->first() != null;
	}
	
	public function read(User $user, User $targetUser)
	{
		if ($user->id == $targetUser->id)
			return true;
	    
		return Permission::where([
					  'id' => $user->id,
					  'table_name' => User::TABLE_NAME,
					  ])->first() != null;
	}

	public function write(User $user, User $targetUser)
	{
		if ($user->id == $targetUser->id)
			return true;
	    
		return Permission::where([
					  'id' => $user->id,
					  'table_name' => User::TABLE_NAME,
					  'write' => true,
					  ])->first() != null;
	}

}