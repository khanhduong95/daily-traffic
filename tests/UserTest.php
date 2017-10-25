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

        $this->seeInDatabase(User::TABLE_NAME, [
            'email' => $user->email,
        ]);
    }

    public function testLogin()
    {
        $password = str_random(10);
        $app_password = str_random(10);
        $user = factory(User::class)->create([
            'password' => app('hash')->make($password),
            'app_password' => app('hash')->make($app_password),
        ]);

        //PASSWORD
        $this->get('/api/token', [
            'HTTP_Authorization' => 'Basic '.base64_encode($user->email.':'.$password),
        ]);

        $this->assertEquals(200, $this->response->status());

        $this->get('/api/token?email='.$user->email.'&password='.$password)
                                      ->seeJson([
                                          'full_permission' => true,
                                      ]);

        $this->assertEquals(200, $this->response->status());
        
        $token = json_decode($this->response->getContent())->token;
        $this->seeInDatabase(User::TABLE_NAME, [
            'email' => $user->email,
            'token' => $token,
        ]);

        //APP_PASSWORD
        $this->get('/api/token', [
            'HTTP_Authorization' => 'Basic '.base64_encode($user->email.':'.$app_password),
        ]);

        $this->assertEquals(200, $this->response->status());
        
        $this->get('/api/token?email='.$user->email.'&password='.$app_password)
                                      ->seeJson([
                                          'full_permission' => false,
                                      ]);
        
        $this->assertEquals(200, $this->response->status());

        $token = json_decode($this->response->getContent())->token;
        $this->seeInDatabase(User::TABLE_NAME, [
            'email' => $user->email,
            'app_token' => $token,
        ]);
    }
 
    public function testUpdate()
    {
        $user = factory(User::class)->create();
        
        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $newUser = factory(User::class)->make([
            'id' => $userId,
        ]);

        //TOKEN
        $user->current_token = dechex(time()).'.'.str_random().'.'.str_random();
        
        $this->actingAs($user)
            ->put('/api/users/'.$userId, [
                'name' => $newUser->name,
                'email' => $newUser->email,
                'birthday' => $newUser->birthday,
                'phone' => $newUser->phone,
            ]);
        
        $this->assertEquals(204, $this->response->status());

        $this->seeInDatabase(User::TABLE_NAME, [
            'id' => $userId,
            'name' => $newUser->name,
            'email' => $newUser->email,
            'birthday' => $newUser->birthday,
            'phone' => $newUser->phone,
        ]);

        //APP_TOKEN
        $user->current_token = dechex(time()).'.'.str_random();
        
        $this->actingAs($user)
            ->put('/api/users/'.$userId);
        
        $this->assertEquals(403, $this->response->status());
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
            'table_name' => User::TABLE_NAME,
            'user_id' => $adminId,
            'write' => true,
        ]);
        
        //TOKEN
        $admin->current_token = dechex(time()).'.'.str_random().'.'.str_random();
        
        $this->actingAs($admin)
            ->put('/api/users/'.$userId, [
                'name' => $newUser->name,
                'email' => $newUser->email,
                'birthday' => $newUser->birthday,
                'phone' => $newUser->phone,
            ]);
        
        $this->assertEquals(204, $this->response->status());

        $this->seeInDatabase(User::TABLE_NAME, [
            'id' => $userId,
            'name' => $newUser->name,
            'email' => $newUser->email,
            'birthday' => $newUser->birthday,
            'phone' => $newUser->phone,
        ]);

        //APP_TOKEN
        $admin->current_token = dechex(time()).'.'.str_random();
        
        $this->actingAs($admin)
            ->put('/api/users/'.$userId);
        
        $this->assertEquals(403, $this->response->status());

        Permission::where('table_name', User::TABLE_NAME)
            ->where('user_id', $adminId)
            ->delete();      

        //APP_TOKEN
        $admin->current_token = dechex(time()).'.'.str_random().'.'.str_random();
        
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

        $user->current_token = dechex(time()).'.'.str_random().'.'.str_random();
        
        $newPassword = str_random(10);
        $newAppPassword = str_random(10);

        $this->actingAs($user)
            ->put('/api/password', [
                'current_password' => $password,
                'new_password' => $newPassword,
            ]);

        $this->assertEquals(204, $this->response->status());

        $this->get('/api/token', [
            'HTTP_Authorization' => 'Basic '.base64_encode($user->email.':'.$newPassword),
        ])->seeJson([
            'full_permission' => true,
        ]);
        
        $this->assertEquals(200, $this->response->status());

        $user->current_token = dechex(time()).'.'.str_random().'.'.str_random();
        
        $this->actingAs($user)
            ->put('/api/password', [
                'current_password' => $newPassword,
                'new_app_password' => $newAppPassword,
            ]);

        $this->assertEquals(204, $this->response->status());

        $this->get('/api/token', [
            'HTTP_Authorization' => 'Basic '.base64_encode($user->email.':'.$newAppPassword),
        ])->seeJson([
            'full_permission' => false,
        ]);
        
        $this->assertEquals(200, $this->response->status());
    }

    public function testDelete()
    {
        $user = factory(User::class)->create();

        $user->current_token = dechex(time()).'.'.str_random().'.'.str_random();        
        
        $userId = User::where('email', $user->email)->firstOrFail()->id;
        
        $this->actingAs($user)
            ->delete('/api/users/'.$userId);
        
        $this->assertEquals(204, $this->response->status());        
        
        $this->missingFromDatabase(User::TABLE_NAME, [
            'id' => $userId,
        ]);
    }
}
