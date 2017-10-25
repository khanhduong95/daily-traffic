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
	private $minutes = [];

    function __construct(){
        $minutesDiff = env('MINUTES_DIFFERENCE', 15);
        for ($i = 0; $i < 60; $i += $minutesDiff){
            $this->minutes[] = sprintf("%02d", $i);
        }
    }

	public function index(Request $request)
	{		
		$this->authorize('readList', Traffic::class);

		$pageSize = $this->getPageSize($request->input('per_page'));

		$res = Traffic::orderBy('id', 'desc')->paginate($pageSize);
		return response()->json($res);
	}

	public function indexByPlace(Request $request, $place_id)
	{		
		$this->authorize('readList', Traffic::class);

		$place = Place::findOrFail($place_id);

		$pageSize = $this->getPageSize($request->input('per_page'));

		$res = $place->traffic()->orderBy('id', 'desc')->paginate($pageSize);
		return response()->json($res);
	}

	public function indexByPlaceAndUser(Request $request, $user_id, $place_id)
	{		
		$user = User::findOrFail($user_id);
		$this->authorize('read', $user);

		$place = Place::findOrFail($place_id);

		$pageSize = $this->getPageSize($request->input('per_page'));

		$res = $place->traffic()->where('user_id', $user_id)->orderBy('id', 'desc')->paginate($pageSize);
		return response()->json($res);
	}

	public function add(Request $request, $user_id, $place_id)
	{           
		$user = User::findOrFail($user_id);
		$this->authorize('write', $user);

        Place::findOrFail($place_id);

		$this->validate($request, [
            'hour' => 'bail|required|date_format:H',
            'minute' => 'bail|required|date_format:i|in:'.implode(',', $this->minutes),
            'dates' => 'bail|required|array|min:1|max:31',
            'dates.*' => 'bail|required|date_format:Y-m-d|after:-1 year|before:+2 years',
        ]);

        $time = $request->input('hour').':'.$request->input('minute').':00';

		$existTraffic = Traffic::select('time')
                      ->where([
                          'user_id' => $user_id,
                          'place_id' => $place_id,
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
                'user_id' => $user_id,
                'place_id' => $place_id,
                'time' => $tk,
            ];
		}
        if (empty($toInsert)) throw new ConflictHttpException;

		Traffic::insert($toInsert);

		return response(null, 201, ['Location' => $request->url()]);
	}

	public function detail(Request $request, $id)
	{
		$traffic = Traffic::findOrFail($id);
        $this->authorize('read', $traffic);
        
		return response()->json($traffic);
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
            'dates' => 'bail|required|array|min:1|max:31',
            'dates.*' => 'bail|required|date_format:Y-m-d H:i:s',
        ]);
		
        $dates = $request->input('dates');

        Traffic::where([
            'user_id' => $user_id,
            'place_id' => $place_id,
        ])->whereIn('time', $dates)->firstOrFail();

        Traffic::where([
            'user_id' => $user_id,
            'place_id' => $place_id,
        ])->whereIn('time', $dates)->delete();

		return response(null, 204);
	}
}
