<?php

use App\User;
use App\Place;
use App\Traffic;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class TrafficTest extends TestCase
{
    use DatabaseMigrations;
    /**
     * A basic test example.
     *
     * @return void
     */

    public function testAddOneDay()
    {
        $user = factory(User::class)->create();
        $place = factory(Place::class)->make();
        
        $date = date('d-m-Y');
        $time = date('H').':00';

        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $this->actingAs($user)
            ->post('/api/user/'.$userId.'/traffic', [
                'latitude' => $place->latitude,
                'longitude' => $place->longitude,
                'date' => $date,
                'time' => $time,
            ]);

        $this->assertEquals(201, $this->response->status());

        $this->seeInDatabase(Place::TABLE_NAME, [
            'latitude' => $place->latitude,
            'longitude' => $place->longitude,
        ]);

        $placeId = Place::where([
            ['latitude', $place->latitude],
            ['longitude', $place->longitude],
        ])->firstOrFail()->id;

        $this->seeInDatabase(Traffic::TABLE_NAME, [
            'user_id' => $userId,
            'place_id' => $placeId,
            'time' => date('Y-m-d', strtotime($date)).' '.$time.':00',
        ]);

        $trafficId = Traffic::where([
            ['user_id', $userId],
            ['place_id', $placeId],
            ['time', date('Y-m-d', strtotime($date)).' '.$time.':00'],
        ])->firstOrFail()->id;
        
        $this->actingAs($user)
            ->get('/api/traffic/'.$trafficId);
        
        $this->assertEquals(200, $this->response->status());
    }
    
    public function testDeleteOneDay()
    {
        $user = factory(User::class)->create();
        $place = factory(Place::class)->create();
        
        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $placeId = Place::where([
            ['latitude', $place->latitude],
            ['longitude', $place->longitude],
        ])->firstOrFail()->id;

        $date = date('d-m-Y');
        $time = date('H').':00';

        $this->actingAs($user)
            ->post('/api/user/'.$userId.'/place/'.$placeId.'/traffic', [
                'date' => $date,
                'time' => $time,
            ]);

        $this->assertEquals(201, $this->response->status());

        $this->seeInDatabase(Traffic::TABLE_NAME, [
            'user_id' => $userId,
            'place_id' => $placeId,
            'time' => date('Y-m-d', strtotime($date)).' '.$time.':00',
        ]);

        $trafficId = Traffic::where([
            ['user_id', $userId],
            ['place_id', $placeId],
            ['time', date('Y-m-d', strtotime($date)).' '.$time.':00'],
        ])->firstOrFail()->id;

        $this->actingAs($user)
            ->delete('/api/traffic/'.$trafficId);

        $this->assertEquals(204, $this->response->status());

        $this->missingFromDatabase(Traffic::TABLE_NAME, [
            'id' => $trafficId,
        ]);
    }

    public function testAddMultiDay()
    {
        $user = factory(User::class)->create();
        $place = factory(Place::class)->make();
        
        $date = '9-'.date('Y');
        $daysInMonth = 30;

        $time = date('H').':00';
        
        $today = date('l');
        $count = 0;
        for ($i = 1; $i <= $daysInMonth; $i++){
            if (date('l', strtotime($i.'-'.$date)) == $today)
                $count++;
        }
        
        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $this->actingAs($user)
            ->post('/api/user/'.$userId.'/traffic', [
                'latitude' => $place->latitude,
                'longitude' => $place->longitude,
                'weekly' => $today,
                'date' => $date,
                'time' => $time,
            ]);

        $this->assertEquals(201, $this->response->status());

        $this->seeInDatabase(Place::TABLE_NAME, [
            'latitude' => $place->latitude,
            'longitude' => $place->longitude,
        ]);

        $placeId = Place::where([
            ['latitude', $place->latitude],
            ['longitude', $place->longitude],
        ])->firstOrFail()->id;

        $this->assertEquals($count, Traffic::where([
            'user_id' => $userId,
            'place_id' => $placeId,
        ])->count());                
    }
    
    public function testDeleteMultiDay()
    {
        $user = factory(User::class)->create();
        $place = factory(Place::class)->create();
        
        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $placeId = Place::where([
            ['latitude', $place->latitude],
            ['longitude', $place->longitude],
        ])->firstOrFail()->id;

        $date = '8-'.date('Y');
        $daysInMonth = 31;

        $time = date('H').':00';
        
        $today = date('l');
        $count = 0;
        for ($i = 1; $i <= $daysInMonth; $i++){
            if (date('l', strtotime($i.'-'.$date)) == $today)
                $count++;
        }
        
        $this->actingAs($user)
            ->post('/api/user/'.$userId.'/place/'.$placeId.'/traffic', [
                'weekly' => $today,
                'date' => $date,
                'time' => $time,
            ]);

        $this->assertEquals(201, $this->response->status());

        $this->assertEquals($count, Traffic::where([
            'user_id' => $userId,
            'place_id' => $placeId,
        ])->count());                

        $this->actingAs($user)
            ->delete('/api/user/'.$userId.'/place/'.$placeId.'/traffic', [
                'weekly' => $today,
                'date' => $date,
                'time' => $time,
            ]);

        $this->assertEquals(204, $this->response->status());
        
        $this->missingFromDatabase(Traffic::TABLE_NAME, [
            'user_id' => $userId,
            'place_id' => $placeId,
        ]);
    }

    public function testAddFullMonth()
    {
        $user = factory(User::class)->create();
        $place = factory(Place::class)->make();
        
        $date = '9-'.date('Y');
        $time = date('H').':00';

        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $this->actingAs($user)
            ->post('/api/user/'.$userId.'/traffic', [
                'latitude' => $place->latitude,
                'longitude' => $place->longitude,
                'weekly' => 'everyday',
                'date' => $date,
                'time' => $time,
            ]);

        $this->assertEquals(201, $this->response->status());

        $this->seeInDatabase(Place::TABLE_NAME, [
            'latitude' => $place->latitude,
            'longitude' => $place->longitude,
        ]);

        $placeId = Place::where([
            ['latitude', $place->latitude],
            ['longitude', $place->longitude],
        ])->firstOrFail()->id;

        $this->assertEquals(30, Traffic::where([
            'user_id' => $userId,
            'place_id' => $placeId,
        ])->count());                
    }
    
    public function testDeleteFullMonth()
    {
        $user = factory(User::class)->create();
        $place = factory(Place::class)->create();
        
        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $placeId = Place::where([
            ['latitude', $place->latitude],
            ['longitude', $place->longitude],
        ])->firstOrFail()->id;

        $date = '8-'.date('Y');
        $time = date('H').':00';

        $this->actingAs($user)
            ->post('/api/user/'.$userId.'/place/'.$placeId.'/traffic', [
                'weekly' => 'everyday',
                'date' => $date,
                'time' => $time,
            ]);

        $this->assertEquals(201, $this->response->status());

        $this->assertEquals(31, Traffic::where([
            'user_id' => $userId,
            'place_id' => $placeId,
        ])->count());                

        $this->actingAs($user)
            ->delete('/api/user/'.$userId.'/place/'.$placeId.'/traffic', [
                'weekly' => 'everyday',
                'date' => $date,
                'time' => $time,
            ]);

        $this->assertEquals(204, $this->response->status());
        
        $this->missingFromDatabase(Traffic::TABLE_NAME, [
            'user_id' => $userId,
            'place_id' => $placeId,
        ]);
    }
        
}
