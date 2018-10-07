<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $fillable = ['user_id', 'place_id', 'frequency'];

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
            'place' => route('places.detail', ['id' => $this->place_id]),
        ];
    }
}
