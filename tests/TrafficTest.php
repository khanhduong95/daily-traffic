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
        $userId = User::where('email', $user->email)->firstOrFail()->id;
        
        $place = factory(Place::class)->create();
        $placeId = Place::where('latitude', $place->latitude)
                 ->where('longitude', $place->longitude)
                 ->firstOrFail()->id;        
        
        $date = date('Y-m-d');
        $hour = date('H');
        $minute = '00';

        $this->actingAs($user)
            ->post('/api/users/'.$userId.'/places/'.$placeId.'/traffic', [
                'dates' => [$date],
                'hour' => $hour,
                'minute' => $minute,
            ]);

        $this->assertEquals(201, $this->response->status());

        $this->seeInDatabase(Traffic::TABLE_NAME, [
            'user_id' => $userId,
            'place_id' => $placeId,
            'time' => $date.' '.$hour.':'.$minute.':00',
        ]);

        $trafficId = Traffic::where('user_id', $userId)
                   ->where('place_id', $placeId)
                   ->where('time', $date.' '.$hour.':'.$minute.':00')
                   ->firstOrFail()->id;
        
        $this->actingAs($user)
            ->get('/api/traffic/'.$trafficId);
        
        $this->assertEquals(200, $this->response->status());
    }
    
    public function testDeleteOneDay()
    {
        $user = factory(User::class)->create();
        $user->current_token = dechex(time()).'.'.str_random().'.'.str_random();
        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $place = factory(Place::class)->create();                
        $placeId = Place::where('latitude', $place->latitude)
                 ->where('longitude', $place->longitude)
                 ->firstOrFail()->id;

        $traffic = factory(Traffic::class)->create([
            'user_id' => $userId,
            'place_id' => $placeId,
        ]);

        $trafficId = Traffic::where('user_id', $userId)
                   ->where('place_id', $placeId)
                   ->where('time', $traffic->time)
                   ->firstOrFail()->id;

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
        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $place = factory(Place::class)->create();
        $placeId = Place::where('latitude', $place->latitude)
                 ->where('longitude', $place->longitude)
                 ->firstOrFail()->id;
        
        $month = date('Y-m');

        $hour = date('H');
        $minute = '00';
        
        $dates = [];
        for ($i = 1; $i < 32; $i++){
            $dk = $month.'-'.$i;
            if (date_parse_from_format('Y-m-d', $dk)['warning_count'] > 0) continue;
            $dates[] = $dk;
        }
        
        $this->actingAs($user)
            ->post('/api/users/'.$userId.'/places/'.$placeId.'/traffic', [
                'dates' => $dates,
                'hour' => $hour,
                'minute' => $minute,
            ]);

        $this->assertEquals(201, $this->response->status());

        $this->seeInDatabase(Place::TABLE_NAME, [
            'latitude' => $place->latitude,
            'longitude' => $place->longitude,
        ]);

        $this->assertEquals(count($dates), Traffic::where([
            'user_id' => $userId,
            'place_id' => $placeId,
        ])->count());                
    }
    
    public function testDeleteMultiDay()
    {
        $user = factory(User::class)->create();
        $user->current_token = dechex(time()).'.'.str_random().'.'.str_random();
        $userId = User::where('email', $user->email)->firstOrFail()->id;

        $place = factory(Place::class)->create();

        $placeId = Place::where('latitude', $place->latitude)
                 ->where('longitude', $place->longitude)
                 ->firstOrFail()->id;

        $dates = [];
        for ($i = 0; $i < 31; $i++){
            $traffic = factory(Traffic::class)->create([
                'user_id' => $userId,
                'place_id' => $placeId,                
            ]);
            $dates[] = $traffic->time;
        }        

        $this->actingAs($user)
            ->delete('/api/users/'.$userId.'/places/'.$placeId.'/traffic', [
                'dates' => $dates,
            ]);

        $this->assertEquals(204, $this->response->status());
        
        $this->missingFromDatabase(Traffic::TABLE_NAME, [
            'user_id' => $userId,
            'place_id' => $placeId,
        ]);
    }
        
}
