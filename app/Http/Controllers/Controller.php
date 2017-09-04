<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
	//
	protected function renderJson($data, $message = 'Success!')
	{
		return response()->json([
					 'error' => 0,
					 'message' => $message,
					 'data' => $data
					 ]);
	}
}
