<?php

use App\User;
use App\Place;
use App\Traffic;
use App\Permission;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class PermissionTest extends TestCase
{
    use DatabaseMigrations;
    /**
     * A basic test example.
     *
     * @return void
     */

    public function testAdd()
    {
        $user = factory(User::class)->create();
        $admin = factory(User::class)->create();

        $userId = User::where('email', $user->email)->firstOrFail()->id;
        $adminId = User::where('email', $admin->email)->firstOrFail()->id;
        
        Permission::insert([
            'user_id' => $adminId,
            'table_name' => 'permissions',
            'write' => true,
        ]);

        $this->actingAs($admin)
            ->post('/api/users/'.$userId.'/permissions', [
                'table_name' => 'users',
                'write' => true,
            ]);

        $this->assertEquals(201, $this->response->status());
        
        $this->seeInDatabase('permissions', [
            'user_id' => $userId,
            'table_name' => 'users',
            'write' => true,
        ]);

        $this->actingAs($user)
            ->post('/api/users/'.$userId.'/permissions');
        
        $this->assertEquals(403, $this->response->status());
    }
    
    public function testUpdate()
    {
        $user = factory(User::class)->create();
        $admin = factory(User::class)->create();

        $userId = User::where('email', $user->email)->firstOrFail()->id;
        $adminId = User::where('email', $admin->email)->firstOrFail()->id;
        
        Permission::insert([
            [
                'user_id' => $adminId,
                'table_name' => 'permissions',
                'write' => true,
            ],
            [
                'user_id' => $userId,
                'table_name' => 'permissions',
                'write' => false,
            ],
        ]);

        $permissionId = Permission::where('user_id', $userId)
                      ->where('table_name', 'permissions')
                      ->firstOrFail()->id;

        $this->actingAs($admin)
            ->put('/api/permissions/'.$permissionId, [
                'write' => true,
            ]);
        
        $this->assertEquals(204, $this->response->status());
        
        $this->seeInDatabase('permissions', [
            'id' => $permissionId,
            'user_id' => $userId,
            'table_name' => 'permissions',
            'write' => true,
        ]);
    }
    
    public function testDelete()
    {
        $user = factory(User::class)->create();
        $admin = factory(User::class)->create();

        $userId = User::where('email', $user->email)->firstOrFail()->id;
        $adminId = User::where('email', $admin->email)->firstOrFail()->id;
        
        Permission::insert([
            [
                'user_id' => $adminId,
                'table_name' => 'permissions',
                'write' => true,
            ],
            [
                'user_id' => $userId,
                'table_name' => 'permissions',
                'write' => false,
            ],
        ]);

        $permissionId = Permission::where('user_id', $userId)
                      ->where('table_name', 'permissions')
                      ->firstOrFail()->id;

        $this->actingAs($admin)
            ->delete('/api/permissions/'.$permissionId);
        
        $this->assertEquals(204, $this->response->status());
        
        $this->missingFromDatabase('permissions', [
            'id' => $permissionId,
        ]);
    }    
}
