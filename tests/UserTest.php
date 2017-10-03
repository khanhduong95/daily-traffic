<?php

use App\User;
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
        $user = factory(User::class)->make([
            'password' => $password,
        ]);

        $this->post('/api/user', [
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
        $user = factory(User::class)->create([
            'password' => app('hash')->make($password),
        ]);

        $this->get('/api/token', [
            'HTTP_Email' => $user->email,
            'HTTP_Password' => $password,
        ]);

        $this->assertEquals(200, $this->response->status());

        $api_token = json_decode($this->response->getContent())->token;
        $this->seeInDatabase(User::TABLE_NAME, [
            'email' => $user->email,
            'api_token' => $api_token,
        ]);

        $this->get('/api/me?token='.$api_token);

        $this->assertEquals(200, $this->response->status());
    }
 
    public function testUpdate()
    {
        $user = factory(User::class)->create();
        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $newUser = factory(User::class)->make([
            'id' => $userId,
        ]);

        $this->actingAs($user)->put('/api/user/'.$userId.'/info', [
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
    }

    public function testNewPassword()
    {
        $password = str_random(10);
        $user = factory(User::class)->create([
            'password' => app('hash')->make($password),
        ]);
        $userId = User::where('email', $user->email)->firstOrFail()->id;
        
        $newPassword = str_random(10);
        $this->actingAs($user)->put('/api/user/'.$userId.'/password', [
            'current_password' => $password,
            'new_password' => $newPassword,
            'new_password_confirmation' => $newPassword,
        ]);

        $this->assertEquals(204, $this->response->status());

        $this->get('/api/token', [
            'HTTP_Email' => $user->email,
            'HTTP_Password' => $newPassword,
        ]);
        
        $this->assertEquals(200, $this->response->status());
    }

    public function testDelete()
    {
        $user = factory(User::class)->create();
        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $this->actingAs($user)
            ->delete('/api/user/'.$userId);
        
        $this->assertEquals(204, $this->response->status());        
                
        $this->missingFromDatabase(User::TABLE_NAME, [
            'id' => $userId,
        ]);
    }
}
