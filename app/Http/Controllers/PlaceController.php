<?php

namespace App\Http\Controllers;

use Exception;
use App\User;
use App\Place;
use App\Traffic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlaceController extends Controller
{

	public function getPlacesByUser(Request $request)
	{
		return $this->renderJson(
					 Place::select(Place::TABLE_NAME.'.id', Place::TABLE_NAME.'.latitude', Place::TABLE_NAME.'.longitude', DB::raw('count('.Traffic::TABLE_NAME.'.time) as frequency'))
					 ->join(Traffic::TABLE_NAME, Traffic::TABLE_NAME.'.place_id', '=', Place::TABLE_NAME.'.id')
					 ->join(User::TABLE_NAME, Traffic::TABLE_NAME.'.user_id', '=', User::TABLE_NAME.'.id')
					 ->where(User::TABLE_NAME.'.id', $request->user()->id)
					 ->groupBy(Traffic::TABLE_NAME.'.place_id')
					 ->orderBy($request->input('sort') == 'frequency' ? DB::raw('count('.Traffic::TABLE_NAME.'.time)') : Traffic::TABLE_NAME.'.id', $request->input('order') == 'asc' ? 'asc' : 'desc')
					 ->paginate(10)
					 );
	}

}
