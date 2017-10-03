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
	private $dates = ['everyday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
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
		
        $validations = ['time' => 'bail|required|date_format:H:i'];
        $weekly = $request->input('weekly');
        if ($weekly && in_array($weekly, $this->dates)){
            $validations['date'] = 'bail|required|date_format:m-Y';
        }
        else {
            $validations['date'] = 'bail|required|date_format:d-m-Y';
        }

		$this->validate($request, $validations);
		
		$frequency = $this->getFrequency($request->input('date'), $request->input('time'), $weekly);

        Traffic::where([
            'user_id' => $user_id,
            'place_id' => $place_id,
        ])->whereIn('time', $frequency)->firstOrFail();

        Traffic::where([
            'user_id' => $user_id,
            'place_id' => $place_id,
        ])->whereIn('time', $frequency)->delete();

		return response(null, 204);
	}

    private function createTraffic($request, $validations, $userId, $placeId = null)
    {
		$user = User::findOrFail($userId);
		$this->authorize('write', $user);

        $validations['time'] = 'bail|required|date_format:H:i';
        $weekly = $request->input('weekly');
        if ($weekly && in_array($weekly, $this->dates)){
            $validations['date'] = 'bail|required|date_format:m-Y|after:-1 year|before:+2 years';
        }
        else {
            $validations['date'] = 'bail|required|date_format:d-m-Y|after:-1 year|before:+2 years';
        }

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
        
		$frequency = $this->getFrequency($request->input('date'), $request->input('time'), $weekly);

		$existTraffic = Traffic::select('time')
                      ->where([
                          'user_id' => $userId,
                          'place_id' => $placeId
                      ])->get();

		$existTimes = [];
		foreach ($existTraffic as $tk){
            $existTime = $tk->time;
			$existTimes[date_create_from_format('Y-m-d H:i:s', $existTime)->getTimestamp()] = $existTime;
		}

		$toInsertTraffic = [];
		foreach (array_diff($frequency, array_keys($existTimes)) as $tk){
			$toInsertTraffic[] = [
                'user_id' => $userId,
                'place_id' => $placeId,
                'time' => $tk,
            ];
		}
        if (empty($toInsertTraffic)) throw new ConflictHttpException;

		Traffic::insert($toInsertTraffic);

		return response(null, 201, ['Location' => '/api/user/'.$userId.'/place/'.$placeId.'/traffic']);
    }

    
	private function getFrequency($date, $time, $weekly = null)
	{
        $timeParts = explode(':', $time);
		$hour = intval($timeParts[0]);
		$minute = intval($timeParts[1]);
		if (!in_array($minute, $this->minutes)) 
			throw new UnprocessableEntityHttpException('You can only choose one of the following minute: '.implode(', ', $this->minutes));

		return $this->getDays($date, $hour.':'.$this->formatDoubleNumber($minute).':00', $weekly);
	}

	private function getDays($date, $time, $weekly)
	{
		$res = [];
        
		if ($weekly && in_array($weekly, $this->dates)){
			for ($i = 1; $i < 32; $i++){
                $dateStr = $i.'-'.$date;
                if ($weekly != 'everyday' && date('l', strtotime($dateStr)) != $weekly) continue;

                $checkFormat = date_parse_from_format('d-m-Y', $dateStr);
                if ($checkFormat['warning_count'] > 0 || $checkFormat['error_count'] > 0) continue;

                $res[] = $checkFormat['year'].'-'.$checkFormat['month'].'-'.$checkFormat['day'].' '.$time;
			}
		}
		else {
            $res[] = date_create_from_format('d-m-Y H:i:s', $date.' '.$time)->format('Y-m-d H:i:s');
		}
		return $res;
	}

	private function formatDoubleNumber($smallNumber)
	{
		return $smallNumber < 10 ? '0'.$smallNumber : $smallNumber;
	}

}
