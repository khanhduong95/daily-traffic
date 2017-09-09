<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

/* $app->get('/', function () use ($app) { */
/*     return $app->version(); */
/* }); */

$app->group(['namespace' => 'App\Http\Controllers', 'middleware' => 'auth'], function () use ($app) {
		//User
		$app->put('/api/user', 'UserController@updateInfo');
		$app->put('/api/password', 'UserController@updatePassword');
		$app->delete('/api/logout', 'UserController@logout');

		//Place
		$app->get('/api/places', 'PlaceController@getPlacesByUser');

		//Traffic
		$app->post('/api/traffic', 'TrafficController@addTraffic');
		$app->delete('/api/traffic/{id}', 'TrafficController@deleteTraffic');
		$app->delete('/api/traffic/place/{id}', 'TrafficController@deleteTrafficByPlace');
	});

//User
$app->post('/api/register', 'UserController@register');
$app->get('/api/login', 'UserController@login');
$app->get('/api/user/{id}', 'UserController@detail');
