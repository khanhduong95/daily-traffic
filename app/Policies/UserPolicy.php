<?php

namespace App\Policies;

use App\User;
use App\Permission;

class UserPolicy
{
    public function read(User $user, User $targetUser)
    {
        if ($user->id == $targetUser->id) {
            return true;
        }

        return Permission::where([
            'user_id' => $user->id,
            'table_name' => 'users',
        ])->first() != null;
    }

    public function write(User $user, User $targetUser)
    {
        if ($user->id == $targetUser->id) {
            return true;
        }
        
        return Permission::where([
            'user_id' => $user->id,
            'table_name' => 'users',
            'write' => true,
        ])->first() != null;
    }
}
