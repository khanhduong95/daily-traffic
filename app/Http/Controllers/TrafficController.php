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
	private $minutes;

	function __construct()
	{
		$this->minutes = [0, 15, 30, 45];
		$this->dates = ['everyday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
		$this->years = [date('Y'), date('Y', strtotime('+1 year'))];
	}

	private function formatDoubleNumber($smallNumber)
	{
		return $smallNumber < 10 ? '0'.$smallNumber : $smallNumber;
	}

	private function getFrequency($freqStr)
	{
		$parts = explode(' ', $freqStr);
		if (count($parts) != 5) throw new InvalidFrequencyException;

		$year = $parts[2];
		if (!in_array($year, $this->years)) 
			throw new Exception('You can only choose one of the following year: '.implode(', ', $this->years));
		
		$hour = intval($parts[3]);
		if ($hour < 0 || $hour > 23) throw new Exception('Hour is not valid.');
		$minute = intval($parts[4]);
		if (!in_array($minute, $this->minutes)) 
			throw new Exception('You can only choose one of the following minute: '.implode(', ', $this->minutes));

		return $this->getMonths($year, $parts[1], $parts[0], $this->formatDoubleNumber($hour), $this->formatDoubleNumber($minute));
	}

	private function getMonths($year, $month, $day, $hour, $minute)
	{
		if ($month == 'everymonth'){
			$res = [];
			for ($i = 1; $i < 13; $i++){
				$res = array_merge($res, $this->getDays($year, $this->formatDoubleNumber($i), $day, $hour, $minute));
			}
			return $res;
		}
		else {
			$month = intval($month);
			if ($month < 1 || $month > 12) throw new Exception('Month is not valid.');
			return $this->getDays($year, $this->formatDoubleNumber($month), $day, $hour, $minute);
		}
	}

	private function getDays($year, $month, $day, $hour, $minute)
	{
		$res = [];
		if (in_array($day, $this->dates)){
			for ($i = 1; $i < 32; $i++){
				$time = strtotime($i.'-'.$month.'-'.$year);
				if (date('m', $time) != $month) continue;
			        if ($day != 'everyday' && date('dayname', $time) != $day) continue;
				$res[] = $year.'-'.$month.'-'.$this->formatDoubleNumber($i).' '.$hour.':'.$minute.':00';				
			}
		}
		else {
			$day = intval($day);
			if ($day < 1 || $day > 31) throw new Exception('Day is not valid.');
			$time = strtotime($day.'-'.$month.'-'.$year);
			if (date('m', $time) == $month)
				$res[] = $year.'-'.$month.'-'.$this->formatDoubleNumber($day).' '.$hour.':'.$minute.':00';
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
		
		$frequency = $this->getFrequency($request->input('frequency'));

		if (empty($frequency)) throw new Exception('Frequency is not valid.');

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

		$existTimes = [];
		foreach ($existTraffic as $tk){
			$existTimes[] = $tk->time;
		}
		$toInsertTraffic = [];
		foreach (array_diff($frequency, $existTimes) as $tk){
			$toInsertTraffic[] = [
					      'user_id' => $userId,
					      'place_id' => $placeId,
					      'time' => $tk
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
		if (! $traffic) throw new Exception('Traffic not found.');
		if ($traffic->user_id != $userId) throw new NoPermissionException;
		$traffic->delete();

		return $this->renderJson('');
	}

	public function deleteTrafficByPlace(Request $request, $id)
	{
		$user = $request->user();
		if (! $user) throw new NotLoggedInException;
		
		$userId = $user->id;
		if (! Place::find($id)) throw new Exception('Place not found.');

		$this->validate($request, [
					   'frequency' => 'bail|required'
					   ]);
		
		$frequency = $this->getFrequency($request->input('frequency'));
		
	        Traffic::where([
				'user_id' => $userId,
				'place_id' => $id
				])->whereIn('time', $frequency)->delete();

		return $this->renderJson('');
	}

}
