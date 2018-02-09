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

    $router->post('/magic', 'IndexController@createMagicLink');

    $router->put('/magic', 'IndexController@confirmMagicLogin');

    $router->post('/quick', 'IndexController@createQuickLink');

    $router->get('/fonts', 'IndexController@getFontList');

    $router->group(['middleware' => ['auth']], function () use ($router) {

        $router->put('/token', 'IndexController@refreshAccessToken');

        $router->delete('/token/{id:[0-9a-z]+}', 'IndexController@deleteAccessToken');

        $router->get('/profile', 'UserController@getProfile');

        $router->get('/devices', 'UserController@getDevices');

        $router->get('/files', 'FileController@getFiles');

        $router->post('/file', 'FileController@createFile');

        $router->put('/file/{id:[0-9]+}', 'FileController@updateFile');

        $router->get('/file/{id:[0-9]+}', 'FileController@getFile');

        $router->delete('/file/{id:[0-9]+}', 'FileController@deleteFile');

        $router->post('/team', 'TeamController@createTeam');

        $router->get('/file/{id:[0-9]+}/layers', 'LayerController@getFileLayers');

        $router->get('/file/{id:[0-9]+}/components', 'ComponentController@getFileComponents');

        $router->get('/layer/{id:[0-9]+}/layers', 'LayerController@getLayerChildren');

        $router->post('/file/{id:[0-9]+}/layer', 'LayerController@createFileLayer');

        $router->put('/layer/{id:[0-9]+}', 'LayerController@editLayer');

        $router->delete('/layer/{id:[0-9]+}', 'LayerController@deleteLayer');

        $router->post('/file/{id}/component', 'ComponentController@createComponent');

        $router->get('/component/{id:[0-9]+}', 'ComponentController@getComponent');

        $router->get('/components', 'ComponentController@getComponents');

        $router->delete('/component/{id:[0-9]+}', 'ComponentController@deleteComponent');

        $router->put('/component/{id:[0-9]+}', 'ComponentController@updateComponent');

        $router->post('/component/{id:[0-9]+}/layer', 'LayerController@createComponentLayer');

        $router->get('/component/{id:[0-9]+}/layers', 'LayerController@getComponentLayers');

        $router->post('/layer/{id:[0-9]+}/transform', 'LayerController@addTransform');

        $router->get('/job/{id:[a-z0-9]+}', 'JobController@getJob');

        $router->post('/file/{id}/designToken', 'DesignTokenController@createDesignToken');

        $router->get('/file/{id}/designTokens', 'DesignTokenController@getDesignTokens');

        $router->put('/designToken/{id}', 'DesignTokenController@updateDesignToken');

        $router->delete('/designToken/{id}', 'DesignTokenController@deleteDesignToken');

        $router->post('/storage/write', 'StorageController@write');
    });
});