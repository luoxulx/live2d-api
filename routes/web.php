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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix'=>'api', 'namespace' => 'Api'], function () use ($router) {
    $router->get('add', 'Live2dController@checkUpdate');
    $router->get('get', 'Live2dController@getSkin');
    $router->get('rand', 'Live2dController@randByParent');
    $router->get('switch', 'Live2dController@switchByParent');
    $router->get('rand_textures', 'Live2dController@randTexturesByParent');
    $router->get('switch_textures', 'Live2dController@switchTexturesByParent');
});
