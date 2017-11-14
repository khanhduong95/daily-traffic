<?php

namespace App\Http\Controllers;

use App\User;
use App\Place;
use App\Permission;
use App\Visit;
use Exception;
use Illuminate\Http\Request;

class HomeController extends Controller
{

	public function index(Request $request)
	{        
        $baseUrl = $request->url();
		return response()->json([
            '_links' => [
                'self' => $baseUrl,
                'token' => $baseUrl.'/token',
                User::TABLE_NAME => $baseUrl.'/'.User::TABLE_NAME,
                Place::TABLE_NAME => $baseUrl.'/'.Place::TABLE_NAME,
                Visit::TABLE_NAME => $baseUrl.'/'.Visit::TABLE_NAME,
                Permission::TABLE_NAME => $baseUrl.'/'.Permission::TABLE_NAME,
            ],
        ]);
	}
}
