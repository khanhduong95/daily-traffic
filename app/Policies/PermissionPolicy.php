<?php

namespace App\Policies;

use App\User;
use App\Permission;

class PermissionPolicy
{
    public function readList(User $user)
    {
        return Permission::where([
            'user_id' => $user->id,
            'table_name' => 'permissions',
        ])->first() != null;
    }

    public function read(User $user, Permission $permission)
    {
        if ($user->id == $permission->user_id) {
            return true;
        }
        
        return Permission::where([
            'user_id' => $user->id,
            'table_name' => 'permissions',
        ])->first() != null;
    }

    public function write(User $user)
    {
        return Permission::where([
            'user_id' => $user->id,
            'table_name' => 'permissions',
            'write' => true,
        ])->first() != null;
    }
}
