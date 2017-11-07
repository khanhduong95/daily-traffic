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
    $app->delete('token', 'UserController@deleteToken');
    $app->put('password', 'UserController@updatePassword');
});

$app->group(['namespace' => 'App\Http\Controllers', 'prefix' => 'api/users', 'middleware' => 'auth'], function () use ($app) {
    //User
    $app->put('{id}', 'UserController@updateInfo');
    $app->delete('{id}', 'UserController@delete');

    //Place
    $app->get('{id}/places', 'PlaceController@indexByUser');

    //Traffic
    $app->get('{user_id}/places/{place_id}/traffic', 'TrafficController@indexByPlaceAndUser');
    $app->post('{user_id}/places/{place_id}/traffic', 'TrafficController@add');
    $app->delete('{user_id}/places/{place_id}/traffic', 'TrafficController@deleteByPlaceAndUser');

    //Permission
    $app->get('{id}/permissions', 'PermissionController@indexByUser');
    $app->post('{id}/permissions', 'PermissionController@addByUser');
});

$app->group(['namespace' => 'App\Http\Controllers', 'prefix' => 'api/places', 'middleware' => 'auth'], function () use ($app) {
    //Place
    $app->post('', 'PlaceController@add');
    $app->put('{id}', 'PlaceController@update');
    $app->delete('{id}', 'PlaceController@delete');
});

$app->group(['namespace' => 'App\Http\Controllers', 'prefix' => 'api/traffic', 'middleware' => 'auth'], function () use ($app) {
    //Traffic
    $app->get('', 'TrafficController@index');
    $app->get('{id}', ['as' => 'traffic.detail', 'uses' => 'TrafficController@detail']);
    $app->delete('{id}', 'TrafficController@delete');
});

$app->group(['namespace' => 'App\Http\Controllers', 'prefix' => 'api/permissions', 'middleware' => 'auth'], function () use ($app) {
    //Permission
    $app->get('', 'PermissionController@index');
    $app->get('{id}', ['as' => 'permissions.detail', 'uses' => 'PermissionController@detail']);
    $app->put('{id}', 'PermissionController@update');
    $app->delete('{id}', 'PermissionController@delete');
});

$app->group(['namespace' => 'App\Http\Controllers', 'prefix' => 'api'], function () use ($app) {		
    $app->get('', 'HomeController@index');    
    $app->get('token', 'UserController@getToken');		
});

$app->group(['namespace' => 'App\Http\Controllers', 'prefix' => 'api/users'], function () use ($app) {		
    //User
    $app->get('', 'UserController@index');
    $app->post('', 'UserController@add');
    $app->get('{id}', ['as' => 'users.detail', 'uses' => 'UserController@detail']);	

    $app->get('{user_id}/permissions/{permission_id}', function($userId, $permissionId) use ($app){
        return redirect(route('permissions.detail', ['id' => $permissionId]).'?previous_path='.$app['request']->path().'&'.$app['request']->getQueryString());
    });
    $app->get('{user_id}/places/{place_id}', function($userId, $placeId) use ($app){
        return redirect(route('places.detail', ['id' => $placeId]).'?previous_path='.$app['request']->path());
    });
    $app->get('{user_id}/places/{place_id}/traffic/{traffic_id}', function($userId, $placeId, $trafficId) use ($app){
        return redirect(route('traffic.detail', ['id' => $trafficId]).'?previous_path='.$app['request']->path().'&'.$app['request']->getQueryString());
    });
});

$app->group(['namespace' => 'App\Http\Controllers', 'prefix' => 'api/places'], function () use ($app) {		
    //Place
    $app->get('', 'PlaceController@index');
    $app->get('{id}', ['as' => 'places.detail', 'uses' => 'PlaceController@detail']);
    $app->get('{place_id}/traffic/{traffic_id}', function($placeId, $trafficId) use ($app){
        return redirect(route('traffic.detail', ['id' => $trafficId]).'?previous_path='.$app['request']->path().'&'.$app['request']->getQueryString());
    });
});