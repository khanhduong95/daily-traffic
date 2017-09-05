<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Traffic extends Model
{

    protected $table = 'traffic';
       
    const TABLE_NAME = 'traffic';

    protected $fillable = [
			   'user_id', 'place_id', 'frequency', 'created_at', 'updated_at',
    ];

}
