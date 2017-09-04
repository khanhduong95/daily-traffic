<?php

namespace App\Http\Controllers;

use Exception;
use App\User;
use App\Place;
use App\Traffic;
use App\Exceptions\NotLoggedInException;
use App\Exceptions\IncorrectPasswordException;
use App\Exceptions\InvalidFrequencyException;
use App\Exceptions\NoPermissionException;
use Illuminate\Http\Request;

class TrafficController extends Controller
{

	const MAX_LATITUDE = 90;
	const MAX_LONGITUDE = 180;
	private $dates;
	private $years;

	function __construct()
	{
		$this->dates = ['everyday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
		$this->years = [date('Y'), date('Y', strtotime('+1 year'))];
	}

	private function getFrequencies($freqStr)
	{
		$parts = explode(' ', $freqStr);
		if (count($parts) != 3) throw new InvalidFrequencyException;

		$year = $parts[2];
		if (!in_array($year, $this->years)) throw new Exception('You can only choose this year or next year!');

		return $this->getMonths($year, $parts[1], $parts[0]);
	}

	private function getMonths($year, $month, $day)
	{
		if ($month == 'everymonth'){
			$res = [];
			for ($i = 1; $i < 13; $i++){
				$res = array_merge($res, $this->getDays($year, $i, $day));
			}
			return $res;
		}
		else {
			$month = intval($month);
			if ($month < 1 || $month > 12) throw new Exception('Month is not valid!');
			return $this->getDays($year, $month, $day);
		}
	}

	private function getDays($year, $month, $day)
	{
		$res = [];
		if (in_array($day, $this->dates)){
			for ($i = 1; $i < 32; $i++){
				$time = strtotime($i.'-'.$month.'-'.$year);
				if (date('m', $time) != $month) continue;
			        if ($day != 'everyday' && date('dayname', $time) != $day) continue;
				$res[] = $year.'-'.$month.'-'.$i.' '.'00:00:00';				
			}
		}
		return $res;
	}

	public function addTraffic(Request $request)
	{
		$user = $request->user();
		if (! $user) throw new NotLoggedInException;
		
		$userId = $user->id;
		$this->validate($request, [
					   'latitude' => 'bail|required|numeric|between:'.(-self::MAX_LATITUDE).','.self::MAX_LATITUDE,
					   'longitude' => 'bail|required|numeric|between:'.(-self::MAX_LONGITUDE).','.self::MAX_LONGITUDE,
					   'frequency' => 'bail|required'
					   ]);
		
		$frequencies = $this->getFrequencies($request->input('frequency'));
		$latitude = $request->input('latitude');
		$longitude = $request->input('longitude');
		
		$place = Place::firstOrCreate([
					       'latitude' => $latitude,
					       'longitude' => $longitude
					       ]);

		$placeId = $place->id;
		
		$existTraffic = Traffic::where([
						'user_id' => $userId,
						'place_id' => $placeId
						])->get();

		$existFreqs = [];
		foreach ($existTraffic as $tk){
			$existFreqs[] = $tk->frequency;
		}
		$toInsertTraffic = [];
		foreach (array_diff($frequencies, $existFreqs) as $fk){
			$toInsertTraffic[] = [
					      'user_id' => $userId,
					      'place_id' => $placeId,
					      'frequency' => $fk
					      ];
		}
		Traffic::insert($toInsertTraffic);

		return $this->renderJson('');
	}

	public function deleteTraffic(Request $request, $id)
	{
		$user = $request->user();
		if (! $user) throw new NotLoggedInException;
		$userId = $user->id;

		$traffic = Traffic::find($id);
		if (! $traffic) throw new Exception('Traffic not found!');
		if ($traffic->user_id != $userId) throw new NoPermissionException;
		$traffic->delete();

		return $this->renderJson('');
	}

	public function deleteTrafficByPlace(Request $request, $id)
	{
		$user = $request->user();
		if (! $user) throw new NotLoggedInException;
		
		$userId = $user->id;
		if (! Place::find($id)) throw new Exception('Place not found!');

		$this->validate($request, [
					   'frequency' => 'bail|required'
					   ]);
		
		$frequencies = $this->getFrequencies($request->input('frequency'));
		
	        Traffic::where([
				'user_id' => $userId,
				'place_id' => $id
				])->whereIn('frequency', $frequencies)->delete();

		return $this->renderJson('');
	}

}
