<?php

namespace App\Http\Controllers;

use Exception;
use App\User;
use App\Place;
use App\Visit;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Illuminate\Http\Request;

class VisitController extends Controller
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
		$this->authorize('readList', Visit::class);

		$pageSize = $this->getPageSize($request->input('per_page'));

		$res = Visit::orderBy('id', 'desc')->paginate($pageSize);
		return response()->json($res);
	}

	public function indexByPlace(Request $request, $place_id)
	{		
		$this->authorize('readList', Visit::class);

		$place = Place::findOrFail($place_id);

		$pageSize = $this->getPageSize($request->input('per_page'));

		$res = $place->visit()->orderBy('id', 'desc')->paginate($pageSize);
		return response()->json($res);
	}

	public function indexByPlaceAndUser(Request $request, $user_id, $place_id)
	{		
		$user = User::findOrFail($user_id);
		$this->authorize('read', $user);

		$place = Place::findOrFail($place_id);

		$pageSize = $this->getPageSize($request->input('per_page'));

		$res = $place->visit()->where('user_id', $user_id)->orderBy('id', 'desc')->paginate($pageSize);
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

		$existVisit = Visit::select('time')
                      ->where([
                          'user_id' => $user_id,
                          'place_id' => $place_id,
                      ])->get();

		$existTimes = [];
		foreach ($existVisit as $tk){
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

		Visit::insert($toInsert);

		return response(null, 201, ['Location' => $request->url()]);
	}

	public function detail(Request $request, $id)
	{
		$visit = Visit::findOrFail($id);
        $this->authorize('read', $visit);
        
		return response()->json($visit);
	}

	public function delete(Request $request, $id)
	{
		$visit = Visit::findOrFail($id);
		$this->authorize('write', $visit);

		$visit->delete();
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

        Visit::where([
            'user_id' => $user_id,
            'place_id' => $place_id,
        ])->whereIn('time', $dates)->firstOrFail();

        Visit::where([
            'user_id' => $user_id,
            'place_id' => $place_id,
        ])->whereIn('time', $dates)->delete();

		return response(null, 204);
	}
}
