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

$app->group(['namespace' => 'App\Http\Controllers', 'prefix' => 'api', 'middleware' => 'auth'], function () use ($app) {
    //User
    $app->get('me', 'UserController@current');
    $app->delete('token', 'UserController@deleteToken');
    $app->put('password', 'UserController@updatePassword');

    //Place
    $app->delete('place/{id}', 'PlaceController@delete');
});

$app->group(['namespace' => 'App\Http\Controllers', 'prefix' => 'api/user', 'middleware' => 'auth'], function () use ($app) {
    //User
    $app->put('{id}', 'UserController@updateInfo');
    $app->delete('{id}', 'UserController@delete');

    //Place
    $app->get('{id}/place', 'PlaceController@indexByUser');

    //Traffic
    $app->get('{id}/traffic', 'TrafficController@indexByUser');
    $app->post('{id}/traffic', 'TrafficController@addByUser');
    $app->get('{user_id}/place/{place_id}/traffic', 'TrafficController@indexByPlaceAndUser');
    $app->post('{user_id}/place/{place_id}/traffic', 'TrafficController@addByPlaceAndUser');
    $app->delete('{user_id}/place/{place_id}/traffic', 'TrafficController@deleteByPlaceAndUser');

    //Permission
    $app->get('{id}/permission', 'PermissionController@index');
    $app->post('{id}/permission', 'PermissionController@addByUser');
});

$app->group(['namespace' => 'App\Http\Controllers', 'prefix' => 'api/traffic', 'middleware' => 'auth'], function () use ($app) {
    //Traffic
    $app->get('', 'TrafficController@index');
    $app->get('{id}', 'TrafficController@detail');
    $app->delete('{id}', 'TrafficController@delete');
});

$app->group(['namespace' => 'App\Http\Controllers', 'prefix' => 'api/permission', 'middleware' => 'auth'], function () use ($app) {
    //Permission
    $app->get('', 'PermissionController@index');
    $app->get('{id}', 'PermissionController@detail');
    $app->put('{id}', 'PermissionController@update');
    $app->delete('{id}', 'PermissionController@delete');
});

$app->group(['namespace' => 'App\Http\Controllers', 'prefix' => 'api'], function () use ($app) {		
    //User
    $app->get('', 'UserController@index');
    $app->post('user', 'UserController@add');
    $app->get('user/{id}', 'UserController@detail');	
    $app->get('token', 'UserController@getToken');
		
    //Place
    $app->get('place', 'PlaceController@index');
    $app->get('place/{id}', 'PlaceController@detail');
});