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

    $router->post('/login', 'IndexController@login');

    $router->group(['middleware' => ['auth']], function () use ($router) {
        $router->get('/tokens', 'IndexController@getTokens');

        $router->post('/token/refresh', 'IndexController@refreshAccessToken');
    });
});