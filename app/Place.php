<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Place extends Model
{

    const TABLE_NAME = 'places';

    protected $fillable = [
			   'latitude', 'longitude', 'created_at', 'updated_at',
    ];

}
