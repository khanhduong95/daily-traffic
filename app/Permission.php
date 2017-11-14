<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
	const TABLE_NAME = 'permissions';
	static $models = [
		   User::class,
		   Place::class,
		   Visit::class,
		   Permission::class,
		   ];

    protected $appends = ['_links'];

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
            'user' => route(User::TABLE_NAME.'.detail', ['id' => $this->user_id]),
        ];
    }
}
