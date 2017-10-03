<?php

namespace App\Providers;

use App\User;
use App\Place;
use App\Traffic;
use App\Permission;
use App\Policies\UserPolicy;
use App\Policies\PlacePolicy;
use App\Policies\TrafficPolicy;
use App\Policies\PermissionPolicy;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

	/**
	 * Boot the authentication services for the application.
	 *
	 * @return void
	 */
	public function boot()
	{
		// Here you may define how you wish users to be authenticated for your Lumen
		// application. The callback which receives the incoming request instance
		// should return either a User instance or null. You're free to obtain
		// the User instance via an API token or any other method necessary.

        $gate = app(Gate::class);
		$gate->policy(User::class, UserPolicy::class);
		$gate->policy(Place::class, PlacePolicy::class);
		$gate->policy(Traffic::class, TrafficPolicy::class);
		$gate->policy(Permission::class, PermissionPolicy::class);

		app('auth')->viaRequest('api', function ($request) {
				if ($request->header('authorization')) {
                    try {
                        $token = trim(substr($request->header('authorization'), strlen('Token')+1));
                        if (strlen($token) > 0)
                            return User::where('api_token', $token)->first();
                    }
                    catch (\Exception $e){}
				}
				if ($request->has('token')) {
					return User::where('api_token', $request->input('token'))->first();
				}
			});
	}
}
