<?php

use App\User;
use App\Permission;
use App\Place;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class PlaceTest extends TestCase
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
        $place = factory(Place::class)->make();

        $this->actingAs($user)
            ->post('/api/places', [
                'latitude' => $place->latitude,
                'longitude' => $place->longitude,
            ]);
        
        $this->assertEquals(201, $this->response->status());

        $this->seeInDatabase(Place::TABLE_NAME, [
            'latitude' => $place->latitude,
            'longitude' => $place->longitude,
        ]);
    }

    public function testUpdate()
    {
        $user = factory(User::class)->create();
        $place = factory(Place::class)->create();
        $newPlace = factory(Place::class)->make();

        $userId = User::where('email', $user->email)
                ->firstOrFail()->id;
        $placeId = Place::where('latitude', $place->latitude)
                 ->where('longitude', $place->longitude)
                 ->firstOrFail()->id;

        Permission::insert([
            'table_name' => Place::TABLE_NAME,
            'user_id' => $userId,
            'write' => true,
        ]);
        
        //TOKEN
        $user->current_token = dechex(time()).'.'.str_random().'.'.str_random();

        $this->actingAs($user)
            ->put('/api/places/'.$placeId, [
                'latitude' => $newPlace->latitude,
                'longitude' => $newPlace->longitude,
            ]);

        $this->assertEquals(204, $this->response->status());

        $this->seeInDatabase(Place::TABLE_NAME, [
            'id' => $placeId,
            'latitude' => $newPlace->latitude,
            'longitude' => $newPlace->longitude,
        ]);

        //TOKEN
        $user->current_token = dechex(time()).'.'.str_random();

        $this->actingAs($user)
            ->put('/api/places/'.$placeId);
        
        $this->assertEquals(403, $this->response->status());
        
        Permission::where('table_name', Place::TABLE_NAME)
            ->where('user_id', $userId)
            ->delete();      
        //TOKEN
        $user->current_token = dechex(time()).'.'.str_random().'.'.str_random();

        $this->actingAs($user)
            ->put('/api/places/'.$placeId);
        
        $this->assertEquals(403, $this->response->status());
    }

    public function testDelete()
    {
        $user = factory(User::class)->create();
        $place = factory(Place::class)->create();

        $userId = User::where('email', $user->email)
                ->firstOrFail()->id;
        $placeId = Place::where('latitude', $place->latitude)
                 ->where('longitude', $place->longitude)
                 ->firstOrFail()->id;

        Permission::insert([
            'table_name' => Place::TABLE_NAME,
            'user_id' => $userId,
            'write' => true,
        ]);
        
        //TOKEN
        $user->current_token = dechex(time()).'.'.str_random().'.'.str_random();
        
        $this->actingAs($user)
            ->delete('/api/places/'.$placeId);
        
        $this->assertEquals(204, $this->response->status());
        
        $this->missingFromDatabase(Place::TABLE_NAME, [
            'id' => $placeId,
        ]);
    }
}
