<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
	const TABLE_NAME = 'permissions';
	static $models = [
		   User::class,
		   Place::class,
		   Traffic::class,
		   Permission::class,
		   ];
}
