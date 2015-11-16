<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyUserCodeTest extends PHPUnit_Framework_TestCase {

  public function testEmptyRequest() {
    $controller = new Controller();

    $request = new Request();
    $response = new Response();
    $response = $controller->verify_code($request, $response);

    $data = json_decode($response->getContent());
    $this->assertEquals($data->error, 'invalid_request');
  }

  public function testInvalidUserCode() {
    $controller = new Controller();

    $request = new Request(['code'=>'xxxx']);
    $response = new Response();
    $response = $controller->verify_code($request, $response);

    $data = json_decode($response->getContent());
    $this->assertEquals($data->error, 'invalid_request');
    $this->assertEquals($data->error_description, 'Code not found');
  }

  public function testRedirectsToAuthServerGivenCode() {
    $controller = new Controller();

    # First generate a code
    $request = new Request(['response_type'=>'device_code', 'client_id'=>'x']);
    $response = new Response();
    $response = $controller->generate_code($request, $response);
    $data = json_decode($response->getContent());

    $request = new Request(['code'=>$data->user_code]);
    $response = new Response();
    $response = $controller->verify_code($request, $response);

    $authURL = Config::$authServerURL . '?response_type=code&client_id=x&redirect_uri=' . urlencode(Config::$baseURL . '/auth/redirect');

    $responseString = $response->__toString();
    preg_match('/Location:\s+([^\s]+)/', $responseString, $location);
    $this->assertEquals($location[1], $authURL);
  }

  public function testRedirectsToAuthServerWithScopeGivenCode() {
    $controller = new Controller();

    # First generate a code
    $request = new Request(['response_type'=>'device_code', 'client_id'=>'x', 'scope'=>'foo']);
    $response = new Response();
    $response = $controller->generate_code($request, $response);
    $data = json_decode($response->getContent());

    $request = new Request(['code'=>$data->user_code]);
    $response = new Response();
    $response = $controller->verify_code($request, $response);

    $authURL = Config::$authServerURL . '?response_type=code&client_id=x&redirect_uri=' . urlencode(Config::$baseURL . '/auth/redirect') . '&scope=foo';

    $responseString = $response->__toString();
    preg_match('/Location:\s+([^\s]+)/', $responseString, $location);
    $this->assertEquals($location[1], $authURL);
  }

}
