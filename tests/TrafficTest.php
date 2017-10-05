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
        $user->current_token = dechex(time()).'.'.str_random().'.'.str_random();
        
        $place = factory(Place::class)->make();
        
        $date = date('Y-m-d');
        $time = date('H').':00';

        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $this->actingAs($user)
            ->post('/api/user/'.$userId.'/traffic', [
                'latitude' => $place->latitude,
                'longitude' => $place->longitude,
                'dates' => [$date],
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
            'time' => $date.' '.$time.':00',
        ]);

        $trafficId = Traffic::where([
            ['user_id', $userId],
            ['place_id', $placeId],
            ['time', $date.' '.$time.':00'],
        ])->firstOrFail()->id;
        
        $this->actingAs($user)
            ->get('/api/traffic/'.$trafficId);
        
        $this->assertEquals(200, $this->response->status());
    }
    
    public function testDeleteOneDay()
    {
        $user = factory(User::class)->create();
        $user->current_token = dechex(time()).'.'.str_random().'.'.str_random();

        $place = factory(Place::class)->create();
        
        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $placeId = Place::where([
            ['latitude', $place->latitude],
            ['longitude', $place->longitude],
        ])->firstOrFail()->id;

        $date = date('Y-m-d');
        $time = date('H').':00';

        $this->actingAs($user)
            ->post('/api/user/'.$userId.'/place/'.$placeId.'/traffic', [
                'dates' => [$date],
                'time' => $time,
            ]);

        $this->assertEquals(201, $this->response->status());

        $this->seeInDatabase(Traffic::TABLE_NAME, [
            'user_id' => $userId,
            'place_id' => $placeId,
            'time' => $date.' '.$time.':00',
        ]);

        $trafficId = Traffic::where([
            ['user_id', $userId],
            ['place_id', $placeId],
            ['time', $date.' '.$time.':00'],
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
        $user->current_token = dechex(time()).'.'.str_random().'.'.str_random();

        $place = factory(Place::class)->make();
        
        $month = date('Y-m');

        $time = date('H').':00';
        
        $dates = [];
        for ($i = 1; $i < 32; $i++){
            $dk = $month.'-'.$i;
            if (date_parse_from_format('Y-m-d', $dk)['warning_count'] > 0) continue;
            $dates[] = $dk;
        }
        
        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $this->actingAs($user)
            ->post('/api/user/'.$userId.'/traffic', [
                'latitude' => $place->latitude,
                'longitude' => $place->longitude,
                'dates' => $dates,
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

        $this->assertEquals(count($dates), Traffic::where([
            'user_id' => $userId,
            'place_id' => $placeId,
        ])->count());                
    }
    
    public function testDeleteMultiDay()
    {
        $user = factory(User::class)->create();
        $user->current_token = dechex(time()).'.'.str_random().'.'.str_random();

        $place = factory(Place::class)->create();
        
        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $placeId = Place::where([
            ['latitude', $place->latitude],
            ['longitude', $place->longitude],
        ])->firstOrFail()->id;

        $month = date('Y-m');

        $time = date('H').':00';
        
        $dates = [];
        for ($i = 1; $i < 32; $i++){
            $dk = $month.'-'.$i;
            if (date_parse_from_format('Y-m-d', $dk)['warning_count'] > 0) continue;
            $dates[] = $dk;
        }

        $this->actingAs($user)
            ->post('/api/user/'.$userId.'/place/'.$placeId.'/traffic', [
                'dates' => $dates,
                'time' => $time,
            ]);

        $this->assertEquals(201, $this->response->status());

        $this->assertEquals(count($dates), Traffic::where([
            'user_id' => $userId,
            'place_id' => $placeId,
        ])->count());                

        $this->actingAs($user)
            ->delete('/api/user/'.$userId.'/place/'.$placeId.'/traffic', [
                'dates' => $dates,
                'time' => $time,
            ]);

        $this->assertEquals(204, $this->response->status());
        
        $this->missingFromDatabase(Traffic::TABLE_NAME, [
            'user_id' => $userId,
            'place_id' => $placeId,
        ]);
    }
        
}
