<?php

namespace App\Exceptions;

use Exception;

class InvalidFrequencyException extends Exception
{

	public function __construct($message = 'Frequency format is not valid.', $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}

}