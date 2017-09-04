<?php

namespace App\Exceptions;

use Exception;

class NoPermissionException extends Exception
{

	public function __construct($message = 'You don\'t have permission to do this operation!', $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}

}