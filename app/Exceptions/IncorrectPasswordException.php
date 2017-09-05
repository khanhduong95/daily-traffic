<?php

namespace App\Exceptions;

use Exception;

class IncorrectPasswordException extends Exception
{

	public function __construct($message = 'Password is not correct.', $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}

}