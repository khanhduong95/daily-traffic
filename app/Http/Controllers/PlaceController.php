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
        if ($request->input('statistics')) {
            $query = Place::select(
                'places.*',
                app('db')->raw('count(visits.time) as frequency')
            )->leftJoin('visits', 'visits.place_id', '=', 'places.id')
                   ->groupBy('visits.place_id')
                   ->orderBy('frequency', $request->input('order', 'desc'));
        } else {
            $query = Place::orderBy('id', $request->input('order', 'desc'));
        }
        
        return response()->json($query->paginate($pageSize));
    }

    public function indexByUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $pageSize = $this->getPageSize($request->input('per_page'));
        $query = $user->places();
        if ($request->input('statistics')) {
            $query->select(
                'places.*',
                app('db')->raw('count(visits.time) as frequency')
            )->groupBy('visits.place_id')
                   ->orderBy('frequency', $request->input('order') == 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('id', $request->input('order') == 'asc' ? 'asc' : 'desc');
        }
        
        return response()->json($query->paginate($pageSize));
    }

    public function add(Request $request)
    {
        $this->validate($request, [
            'latitude' => 'bail|required|numeric|between:'.(-Place::MAX_LATITUDE).','.Place::MAX_LATITUDE,
            'longitude' => 'bail|required|numeric|between:'.(-Place::MAX_LONGITUDE).','.Place::MAX_LONGITUDE
            .'|unique:places,longitude,NULL,id,latitude,'.$request->input('latitude'),
        ]);
        
        $place = new Place($request->only('latitude', 'longitude'));
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
            'longitude' => 'bail|required|numeric|between:'.(-Place::MAX_LONGITUDE).','.Place::MAX_LONGITUDE
            .'|unique:places,longitude,'.$place->id.',id,latitude,'.$request->input('latitude'),
        ]);
        
        $place->fill($request->only('latitude', 'longitude'));
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
