<?php

use App\User;
use App\Permission;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class UserTest extends TestCase
{
    use DatabaseMigrations;
    /**
     * A basic test example.
     *
     * @return void
     */

    public function testRegister()
    {
        $password = str_random(10);
        $user = factory(User::class)->make();

        $this->post('/api/users', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $this->assertEquals(201, $this->response->status());

        $this->seeInDatabase('users', [
            'email' => $user->email,
        ]);
    }

    public function testLogin()
    {
        $password = str_random(10);
        $user = factory(User::class)->create([
            'password' => app('hash')->make($password),
        ]);

        //PASSWORD
        $this->get('/api/token', [
            'HTTP_Authorization' => 'Basic '.base64_encode($user->email.':'.$password),
        ]);

        $this->assertEquals(200, $this->response->status());

        $this->get('/api/token?email='.$user->email.'&password='.$password);

        $this->assertEquals(200, $this->response->status());
        
        $token = json_decode($this->response->getContent())->token;
        $this->seeInDatabase('users', [
            'email' => $user->email,
            'token' => $token,
        ]);
    }
 
    public function testUpdate()
    {
        $user = factory(User::class)->create();
        
        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $newUser = factory(User::class)->make([
            'id' => $userId,
        ]);

        $this->actingAs($user)
            ->put('/api/users/'.$userId, [
                'name' => $newUser->name,
                'email' => $newUser->email,
                'birthday' => $newUser->birthday,
                'phone' => $newUser->phone,
            ]);
        
        $this->assertEquals(204, $this->response->status());

        $this->seeInDatabase('users', [
            'id' => $userId,
            'name' => $newUser->name,
            'email' => $newUser->email,
            'birthday' => $newUser->birthday,
            'phone' => $newUser->phone,
        ]);
    }

    public function testUpdateAsAdmin()
    {
        $admin = factory(User::class)->create();
        $user = factory(User::class)->create();
        
        $adminId = User::where('email', $admin->email)->firstOrFail()->id;
        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $newUser = factory(User::class)->make([
            'id' => $userId,
        ]);

        Permission::insert([
            'table_name' => 'users',
            'user_id' => $adminId,
            'write' => true,
        ]);
        
        $this->actingAs($admin)
            ->put('/api/users/'.$userId, [
                'name' => $newUser->name,
                'email' => $newUser->email,
                'birthday' => $newUser->birthday,
                'phone' => $newUser->phone,
            ]);
        
        $this->assertEquals(204, $this->response->status());

        $this->seeInDatabase('users', [
            'id' => $userId,
            'name' => $newUser->name,
            'email' => $newUser->email,
            'birthday' => $newUser->birthday,
            'phone' => $newUser->phone,
        ]);

        Permission::where('table_name', 'users')
            ->where('user_id', $adminId)
            ->delete();      

        $this->actingAs($admin)
            ->put('/api/users/'.$userId);
        
        $this->assertEquals(403, $this->response->status());
    }

    public function testNewPassword()
    {
        $password = str_random(10);
        $user = factory(User::class)->create([
            'password' => app('hash')->make($password),
        ]);

        $newPassword = str_random(10);

        $this->actingAs($user)
            ->put('/api/password', [
                'current_password' => $password,
                'new_password' => $newPassword,
            ]);

        $this->assertEquals(204, $this->response->status());

        $this->get('/api/token', [
            'HTTP_Authorization' => 'Basic '.base64_encode($user->email.':'.$newPassword),
        ]);
        
        $this->assertEquals(200, $this->response->status());
        
        $this->actingAs($user)
            ->put('/api/password', [
                'current_password' => $newPassword,
                'new_password' => str_random(6),
            ]);

        $this->assertEquals(204, $this->response->status());
    }

    public function testDelete()
    {
        $user = factory(User::class)->create();

        $userId = User::where('email', $user->email)->firstOrFail()->id;
        
        $this->actingAs($user)
            ->delete('/api/users/'.$userId);
        
        $this->assertEquals(204, $this->response->status());        
        
        $this->missingFromDatabase('users', [
            'id' => $userId,
        ]);
    }
}
