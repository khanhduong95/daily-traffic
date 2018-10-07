<?php

namespace App\Policies;

use App\User;
use App\Visit;
use App\Permission;

class VisitPolicy
{
    public function readList(User $user)
    {
        return Permission::where([
            'user_id' => $user->id,
            'table_name' => 'visits',
        ])->first() != null;
    }

    public function read(User $user, Visit $visit)
    {
        if ($user->id == $visit->user_id) {
            return true;
        }
        
        return Permission::where([
            'user_id' => $user->id,
            'table_name' => 'visits',
        ])->first() != null;
    }

    public function write(User $user, Visit $visit)
    {
        if ($user->id == $visit->user_id) {
            return true;
        }
        
        return Permission::where([
            'user_id' => $user->id,
            'table_name' => 'visits',
            'write' => true,
        ])->first() != null;
    }
}
