<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public static $tables = ['users', 'places', 'visits', 'permissions'];

    protected $appends = ['_links'];

    public function getLinksAttribute()
    {
        $request = app('request');
        if ($request->has('previous_path')) {
            $currentUrl = url($request->input('previous_path'));
        } else {
            $currentUrl = $request->url();
        }
        
        $idPath = '/'.$this->id;
        if (! ends_with($currentUrl, $idPath)) {
            $currentUrl .= $idPath;
        }
        
        return [
            'self' => $currentUrl,
            'user' => route('users.detail', ['id' => $this->user_id]),
        ];
    }
}
