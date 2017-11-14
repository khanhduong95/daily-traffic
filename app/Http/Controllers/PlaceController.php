<?php

namespace App\Http\Controllers;

use Exception;
use App\User;
use App\Place;
use App\Visit;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PlaceController extends Controller
{
	public function index(Request $request)
	{
		$pageSize = $this->getPageSize($request->input('per_page'));
        if ($request->has('statistics'))
            $query = Place::select(Place::TABLE_NAME.'.*', app('db')->raw('count('.Visit::TABLE_NAME.'.time) as frequency'))
                   ->leftJoin(Visit::TABLE_NAME, Visit::TABLE_NAME.'.place_id', '=', Place::TABLE_NAME.'.id')
                   ->groupBy(Visit::TABLE_NAME.'.place_id')
                   ->orderBy('frequency', $request->input('order') == 'asc' ? 'asc' : 'desc');

        else 
            $query = Place::select(Place::TABLE_NAME.'.id', Place::TABLE_NAME.'.latitude', Place::TABLE_NAME.'.longitude')
                   ->leftJoin(Visit::TABLE_NAME, Visit::TABLE_NAME.'.place_id', '=', Place::TABLE_NAME.'.id')
                   ->groupBy(Visit::TABLE_NAME.'.place_id')
                   ->orderBy(Place::TABLE_NAME.'.id', $request->input('order') == 'asc' ? 'asc' : 'desc');
        
        
		return response()->json($query->paginate($pageSize));
	}

	public function indexByUser(Request $request, $id)
	{
        $user = User::findOrFail($id);

		$pageSize = $this->getPageSize($request->input('per_page'));
        $query = $user->places();
        if ($request->has('statistics'))
            $query = $query->select(Place::TABLE_NAME.'.*', app('db')->raw('count('.Visit::TABLE_NAME.'.time) as frequency'))
                   ->groupBy(Visit::TABLE_NAME.'.place_id')
                   ->orderBy('frequency', $request->input('order') == 'asc' ? 'asc' : 'desc');

        else 
            $query = $query->groupBy(Visit::TABLE_NAME.'.place_id')
                   ->orderBy(Place::TABLE_NAME.'.id', $request->input('order') == 'asc' ? 'asc' : 'desc');        
        
		return response()->json($query->paginate($pageSize));
	}

	public function add(Request $request)
	{
        $this->validate($request, [
            'latitude' => 'bail|required|numeric|between:'.(-Place::MAX_LATITUDE).','.Place::MAX_LATITUDE,
            'longitude' => 'bail|required|numeric|between:'.(-Place::MAX_LONGITUDE).','.Place::MAX_LONGITUDE,
        ]);
        
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $place = Place::where('latitude', $latitude)
               ->where('longitude', $longitude)
               ->first();
        
        if ($place) throw new ConflictHttpException;

        $place = new Place;
        $place->latitude = $latitude;
        $place->longitude = $longitude;
        $place->save();

		return response(null, 201, ['Location' => $request->url().'/'.$place->id]);        
	}

	public function detail(Request $request, $id)
	{
		$place = Place::findOrFail($id);
		return response()->json($place);
	}

	public function update(Request $request, $id)
	{
		$place = Place::findOrFail($id);
		$this->authorize('write', $place);

        $this->validate($request, [
            'latitude' => 'bail|required|numeric|between:'.(-Place::MAX_LATITUDE).','.Place::MAX_LATITUDE,
            'longitude' => 'bail|required|numeric|between:'.(-Place::MAX_LONGITUDE).','.Place::MAX_LONGITUDE,
        ]);
        
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        $exist = Place::where('latitude', $latitude)
               ->where('longitude', $longitude)
               ->first();
        
        if ($exist && $exist->id != $id) throw new ConflictHttpException;
        
        $place->latitude = $latitude;
        $place->longitude = $longitude;
        $place->save();

		return response(null, 204);
	}

	public function delete(Request $request, $id)
	{
		$place = Place::findOrFail($id);
		$this->authorize('write', $place);

		$place->delete();
		return response(null, 204);
	}
}
