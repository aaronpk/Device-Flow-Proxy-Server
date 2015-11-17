<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AccessTokenRequestTest extends PHPUnit_Framework_TestCase {

  public function testEmptyRequest() {
    $controller = new Controller();

    $request = new Request();
    $response = new Response();
    $response = $controller->access_token($request, $response);

    $data = json_decode($response->getContent());
    $this->assertEquals($data->error, 'invalid_request');
  }

  public function testRequestMissingParameters() {
    $controller = new Controller();

    $request = new Request(['grant_type' => 'authorization_code']);
    $response = new Response();
    $response = $controller->access_token($request, $response);

    $data = json_decode($response->getContent());
    $this->assertEquals($data->error, 'invalid_request');

    $request = new Request(['grant_type' => 'authorization_code', 'code' => 'foo']);
    $response = new Response();
    $response = $controller->access_token($request, $response);

    $data = json_decode($response->getContent());
    $this->assertEquals($data->error, 'invalid_request');
  }

  public function testInvalidGrantType() {
    $controller = new Controller();

    $request = new Request(['grant_type' => 'foo', 'code' => 'foo'.microtime(true), 'client_id' => 'bar']);
    $response = new Response();
    $response = $controller->access_token($request, $response);

    $data = json_decode($response->getContent());
    $this->assertEquals($data->error, 'invalid_request');
  }

  public function testInvalidAuthorizationCode() {
    $controller = new Controller();

    $request = new Request(['grant_type' => 'authorization_code', 'code' => 'foo.'.microtime(true), 'client_id' => 'bar']);
    $response = new Response();
    $response = $controller->access_token($request, $response);

    $data = json_decode($response->getContent());
    $this->assertEquals($data->error, 'invalid_grant');
  }

  public function testRateLimiting() {
    $controller = new Controller();

    $request = new Request(['grant_type'=>'authorization_code', 'code'=>'foo.'.microtime(true), 'client_id'=>'bar']);
    $response = new Response();

    $response_data = $controller->access_token($request, $response);
    $data = json_decode($response_data->getContent());
    $this->assertNotEquals($data->error, 'slow_down');

    $response_data = $controller->access_token($request, $response);
    $data = json_decode($response_data->getContent());
    $this->assertEquals($data->error, 'slow_down');
  }

  public function testAuthorizationPending() {
    # obtain a device code
    $controller = new Controller();
    $response = new Response();

    $request = new Request(['response_type'=>'device_code', 'client_id'=>'x']);
    $response_data = $controller->generate_code($request, $response);
    $data = json_decode($response_data->getContent());
    $this->assertObjectNotHasAttribute('error', $data);

    $device_code = $data->device_code;

    # check the status of the device code
    $request = new Request(['grant_type'=>'authorization_code', 'client_id'=>'x', 'code'=>$device_code]);
    $response_data = $controller->access_token($request, $response);
    $data = json_decode($response_data->getContent());

    $this->assertEquals($data->error, 'authorization_pending');
  }
}
