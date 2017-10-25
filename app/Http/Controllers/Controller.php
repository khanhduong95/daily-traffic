<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
	//
	protected function getPageSize($ps)
	{
		$pageSize = intval(env('MAX_PAGE_SIZE', 15));
		if ($ps != null){
			$ps = intval($ps);
			if ($ps <= $pageSize && $ps > 0){
				$pageSize = $ps;
			}
		}
		return $pageSize;
	}
}
