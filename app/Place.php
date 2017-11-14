<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    const TABLE_NAME = 'places';

	const MAX_LATITUDE = 90;
	const MAX_LONGITUDE = 180;

    protected $fillable = [
			   'latitude', 'longitude', 'created_at', 'updated_at',
    ];

    protected $appends = ['_links'];

    public function visit()
    {
        return $this->hasMany(Visit::class);
    }

    public function getLinksAttribute()
    {
        $request = app('request');
        if ($request->has('previous_path'))
            $currentUrl = url($request->input('previous_path'));
        else
            $currentUrl = $request->url();

        $idPath = '/'.$this->id;
        if (! ends_with($currentUrl, $idPath))
            $currentUrl .= $idPath;

        return [
            'self' => $currentUrl,
            Visit::TABLE_NAME => $currentUrl.'/'.Visit::TABLE_NAME,
        ];
    }
}
