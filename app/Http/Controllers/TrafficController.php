<?php

namespace App\Http\Controllers;

use Exception;
use App\User;
use App\Place;
use App\Traffic;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Illuminate\Http\Request;

class TrafficController extends Controller
{
	const MAX_LATITUDE = 90;
	const MAX_LONGITUDE = 180;
	private $minutes = [0, 15, 30, 45];

	public function index(Request $request)
	{		
		$this->authorize('readList', Traffic::class);

		$ps = $request->input('page_size');
		$pageSize = $this->getPageSize($request->input('page_size'));

		$res = Traffic::orderBy('id', 'desc')->paginate($pageSize);
		return response()->json($res);
	}

	public function indexByUser(Request $request, $id)
	{		
		$user = User::findOrFail($id);
		$this->authorize('read', $user);

		$pageSize = $this->getPageSize($request->input('page_size'));

		$res = Traffic::where('user_id', $id)->orderBy('id', 'desc')->paginate($pageSize);
		return response()->json($res);
	}

	public function indexByPlaceAndUser(Request $request, $place_id, $user_id)
	{		
		$user = User::findOrFail($user_id);
		$this->authorize('read', $user);

		$ps = $request->input('page_size');
		$pageSize = $this->getPageSize($request->input('page_size'));

		$res = Traffic::where([
            'user_id' => $user_id,
            'place_id' => $place_id
        ])->orderBy('id', 'desc')->paginate($pageSize);
		return response()->json($res);
	}

	public function detail($id)
	{
		$traffic = Traffic::findOrFail($id);
		$this->authorize('read', $traffic);

		return response()->json($traffic);
	}

	public function addByUser(Request $request, $user_id)
	{
		return $this->createTraffic($request, [
            'latitude' => 'bail|required|numeric|between:'.(-self::MAX_LATITUDE).','.self::MAX_LATITUDE,
            'longitude' => 'bail|required|numeric|between:'.(-self::MAX_LONGITUDE).','.self::MAX_LONGITUDE,
        ], $user_id);
	}

	public function addByPlaceAndUser(Request $request, $place_id, $user_id)
	{
        return $this->createTraffic($request, [], $user_id, $place_id);
	}

	public function delete(Request $request, $id)
	{
		$traffic = Traffic::findOrFail($id);
		$this->authorize('write', $traffic);

		$traffic->delete();
		return response(null, 204);
	}

	public function deleteByPlaceAndUser(Request $request, $place_id, $user_id)
	{
		$user = User::findOrFail($user_id);
		$this->authorize('write', $user);
	    
		$this->validate($request, [
            'time' => 'bail|required|date_format:H:i',
            'dates' => 'bail|required|array|min:1|max:31',
            'dates.*' => 'bail|required|date_format:Y-m-d',
        ]);
		
        $time = $request->input('time').':00';
        $dates = $request->input('dates');

		$toDelete = [];
        foreach ($dates as $dk){
			$toDelete[] = $dk.' '.$time;
		}

        Traffic::where([
            'user_id' => $user_id,
            'place_id' => $place_id,
        ])->whereIn('time', $toDelete)->firstOrFail();

        Traffic::where([
            'user_id' => $user_id,
            'place_id' => $place_id,
        ])->whereIn('time', $toDelete)->delete();

		return response(null, 204);
	}

    private function createTraffic($request, $validations, $userId, $placeId = null)
    {
		$user = User::findOrFail($userId);
		$this->authorize('write', $user);

        $validations['time'] = 'bail|required|date_format:H:i';
        $validations['dates'] = 'bail|required|array|min:1|max:31';
        $validations['dates.*'] = 'bail|required|date_format:Y-m-d|after:-1 year|before:+2 years';

		$this->validate($request, $validations);

        if ($placeId){
            Place::findOrFail($placeId);
        }
        else {
            $placeId = Place::firstOrCreate([
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
            ])->id;
        }
        
        $time = $request->input('time');
        $minute = intval(explode(':', $time)[1]);
		if (!in_array($minute, $this->minutes)) 
			throw new UnprocessableEntityHttpException('You can only choose one of the following minute: '.implode(', ', $this->minutes));
        $time .= ':00';

		$existTraffic = Traffic::select('time')
                      ->where([
                          'user_id' => $userId,
                          'place_id' => $placeId
                      ])->get();

		$existTimes = [];
		foreach ($existTraffic as $tk){
            $existTime = $tk->time;
			$existTimes[] = date_create_from_format('Y-m-d H:i:s', $existTime)->getTimestamp();
		}

        $dates = $request->input('dates');
		$toInsert = [];
        foreach ($dates as $dk){
            $tk = $dk.' '.$time;
            if (in_array(date_create_from_format('Y-m-d H:i:s', $tk)->getTimestamp(), $existTimes)) continue;
			$toInsert[] = [
                'user_id' => $userId,
                'place_id' => $placeId,
                'time' => $tk,
            ];
		}
        if (empty($toInsert)) throw new ConflictHttpException;

		Traffic::insert($toInsert);

		return response(null, 201, ['Location' => '/api/user/'.$userId.'/place/'.$placeId.'/traffic']);
    }
    
}
