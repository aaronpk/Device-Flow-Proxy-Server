<?php
chdir('..');
include('vendor/autoload.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
$router = new League\Route\RouteCollection;
$templates = new League\Plates\Engine(dirname(__FILE__).'/../views');

$router->addRoute('GET', '/', 'Controller::index');
$router->addRoute('GET', '/device', 'Controller::device');
$router->addRoute('POST', '/device/code', 'Controller::generate_code');

$dispatcher = $router->getDispatcher();
$request = Request::createFromGlobals();
$response = $dispatcher->dispatch($request->getMethod(), $request->getPathInfo());
$response->send();
