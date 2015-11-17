<?php
chdir('..');
include('vendor/autoload.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
$router = new League\Route\RouteCollection;
$templates = new League\Plates\Engine(dirname(__FILE__).'/../views');

$router->addRoute('GET', '/', 'Controller::index');

# Browser routes
$router->addRoute('GET', '/device', 'Controller::device');
$router->addRoute('GET', '/auth/verify_code', 'Controller::verify_code');
$router->addRoute('GET', '/auth/redirect', 'Controller::redirect');

# Device API
$router->addRoute('POST', '/device/code', 'Controller::generate_code');
$router->addRoute('POST', '/device/token', 'Controller::access_token');

$dispatcher = $router->getDispatcher();
$request = Request::createFromGlobals();
$response = $dispatcher->dispatch($request->getMethod(), $request->getPathInfo());
$response->send();
