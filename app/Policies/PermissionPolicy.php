<?php

namespace App\Policies;

use App\User;
use App\Permission;

class PermissionPolicy
{

    public function read(User $user)
    {	    
	    return Permission::where([
				      'id' => $user->id,
				      'table_name' => Permission::TABLE_NAME,
				      ])->first() != null;
    }

    public function write(User $user)
    {
	    return Permission::where([
				      'id' => $user->id,
				      'table_name' => Permission::TABLE_NAME,
				      'write' => true,
				      ])->first() != null;
    }

}