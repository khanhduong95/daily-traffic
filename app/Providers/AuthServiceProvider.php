<?php

namespace App\Providers;

use Exception;
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
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

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

		app('auth')->viaRequest('api', function ($request){
            try {
                $token = $request->input('token');
                $time = null;
                if (! $token && $request->header('authorization')){
                    $token = trim(substr($request->header('authorization'), strlen('Bearer')+1));
                }
                if ($token){
                    $time = intval(hexdec(explode('.', $token)[0]));
                }
            }
            catch (Exception $e){
                throw new UnprocessableEntityHttpException('The token is invalid.');
            }
            $expires = intval(env('TOKEN_EXPIRE', 900));
            if ($time){
                if ($expires > 0 && $time < strtotime('-'.$expires.' seconds'))
                    throw new UnprocessableEntityHttpException('The token has been expired.');

                $user = User::where('token', $token)
                      ->orWhere('app_token', $token)
                      ->firstOrFail();

                $user->current_token = $token;
                return $user;
            }
        });
	}

}
