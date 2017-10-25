<?php

use App\User;
use App\Permission;
use Illuminate\Database\Seeder;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (! Permission::first()){
            $admin = User::where('email', 'admin@example.com')->first();
            if ($admin) {
                $adminId = $admin->id;
                $toInsertPermission = [];
                foreach (Permission::$models as $mk){
                    $toInsertPermission[] = [
                        'table_name' => $mk::TABLE_NAME,
                        'write' => true,
                        'user_id' => $adminId
                    ];
                }
                Permission::insert($toInsertPermission);
            }
        }
    }
}
