<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    const TABLE_NAME = 'users';

    protected $fillable = [
        'name', 'email',
    ];

    protected $appends = ['_links'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'app_password', 'token', 'app_token', 'current_token', 'remember_token', 'created_at', 'updated_at',
    ];

    public function permissions()
    {
        return $this->hasMany(Permission::class);
    }

    public function places()
    {
        return $this->belongsToMany(Place::class, Visit::TABLE_NAME);
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
            Place::TABLE_NAME => $currentUrl.'/'.Place::TABLE_NAME,
            Permission::TABLE_NAME => $currentUrl.'/'.Permission::TABLE_NAME,
        ];
    }
}
