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
            'table_name' => Permission::TABLE_NAME,
            'write' => true,
        ]);

        $admin->current_token = dechex(time()).'.'.str_random().'.'.str_random();

        $this->actingAs($admin)
            ->post('/api/users/'.$userId.'/permissions', [
                'table_name' => User::TABLE_NAME,
                'write' => true,
            ]);

        $this->assertEquals(201, $this->response->status());
        
        $this->seeInDatabase(Permission::TABLE_NAME, [
            'user_id' => $userId,
            'table_name' => User::TABLE_NAME,
            'write' => true,
        ]);

        $admin->current_token = dechex(time()).'.'.str_random();

        $this->actingAs($admin)
            ->post('/api/users/'.$userId.'/permissions');
        
        $this->assertEquals(403, $this->response->status());

        $user->current_token = dechex(time()).'.'.str_random().'.'.str_random();

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
                'table_name' => Permission::TABLE_NAME,
                'write' => true,
            ],
            [
                'user_id' => $userId,
                'table_name' => Permission::TABLE_NAME,
                'write' => false,
            ],
        ]);

        $permissionId = Permission::where('user_id', $userId)
                      ->where('table_name', Permission::TABLE_NAME)
                      ->firstOrFail()->id;

        $admin->current_token = dechex(time()).'.'.str_random().'.'.str_random();

        $this->actingAs($admin)
            ->put('/api/permissions/'.$permissionId, [
                'write' => true,
            ]);
        
        $this->assertEquals(204, $this->response->status());
        
        $this->seeInDatabase(Permission::TABLE_NAME, [
            'id' => $permissionId,
            'user_id' => $userId,
            'table_name' => Permission::TABLE_NAME,
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
                'table_name' => Permission::TABLE_NAME,
                'write' => true,
            ],
            [
                'user_id' => $userId,
                'table_name' => Permission::TABLE_NAME,
                'write' => false,
            ],
        ]);

        $permissionId = Permission::where('user_id', $userId)
                      ->where('table_name', Permission::TABLE_NAME)
                      ->firstOrFail()->id;

        $admin->current_token = dechex(time()).'.'.str_random().'.'.str_random();

        $this->actingAs($admin)
            ->delete('/api/permissions/'.$permissionId);
        
        $this->assertEquals(204, $this->response->status());
        
        $this->missingFromDatabase(Permission::TABLE_NAME, [
            'id' => $permissionId,
        ]);
    }
    
}
