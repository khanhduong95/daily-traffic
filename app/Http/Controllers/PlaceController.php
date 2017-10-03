<?php

namespace App\Http\Controllers;

use Exception;
use App\User;
use App\Place;
use App\Traffic;
use Illuminate\Http\Request;

class PlaceController extends Controller
{

	public function index(Request $request)
	{
		$pageSize = $this->getPageSize($request->input('page_size'));
		$res = Place::orderBy('id', 'desc')->paginate($pageSize);
		return response()->json($res);
	}

	public function detail($id)
	{
		$place = Place::findOrFail($id);
		return response()->json($place);
	}

	public function getPlacesByUser(Request $request, $id)
	{
		$user = User::findOrFail($id);
		$this->authorize('read', $user);

		$pageSize = $this->getPageSize($request->input('page_size'));
		return response()->json(
            Place::select(Place::TABLE_NAME.'.id', Place::TABLE_NAME.'.latitude', Place::TABLE_NAME.'.longitude', app('db')->raw('count('.Traffic::TABLE_NAME.'.time) as frequency'))
            ->join(Traffic::TABLE_NAME, Traffic::TABLE_NAME.'.place_id', '=', Place::TABLE_NAME.'.id')
            ->join(User::TABLE_NAME, Traffic::TABLE_NAME.'.user_id', '=', User::TABLE_NAME.'.id')
            ->where(User::TABLE_NAME.'.id', $user->id)
            ->groupBy(Traffic::TABLE_NAME.'.place_id')
            ->orderBy($request->input('sort') == 'frequency' ? app('db')->raw('count('.Traffic::TABLE_NAME.'.time)') : Traffic::TABLE_NAME.'.id', $request->input('order') == 'asc' ? 'asc' : 'desc')
            ->paginate($pageSize)
        );
	}

	public function delete(Request $request, $id)
	{
		$place = Place::findOrFail($id);
		$this->authorize('write', $place);

		$place->delete();
		return response(null, 204);
	}
}
