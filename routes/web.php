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

$router->group(['middleware' => 'cors'], function () use ($router) {

    $router->get('/', 'IndexController@index');

    $router->post('/token', 'IndexController@login');

    $router->group(['middleware' => ['auth']], function () use ($router) {

        $router->put('/token', 'IndexController@refreshAccessToken');

        $router->delete('/token/{id:[0-9a-z]+}', 'IndexController@deleteAccessToken');

        $router->get('/profile', 'UserController@getProfile');

        $router->get('/devices', 'UserController@getDevices');

        $router->get('/files', 'FileController@getFiles');
    });
});