<?php

namespace App\Exceptions;

use Exception;

class NotLoggedInException extends Exception
{

	public function __construct($message = 'You are not logged in!', $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}

}