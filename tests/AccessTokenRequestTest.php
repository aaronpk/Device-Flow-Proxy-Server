<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AccessTokenRequestTest extends PHPUnit\Framework\TestCase {

  public function testEmptyRequest() {
    $controller = new Controller();

    $request = new Request();
    $response = new Response();
    $response = $controller->access_token($request, $response);

    $data = json_decode($response->getContent());
    $this->assertEquals('invalid_request', $data->error);
  }

  public function testRequestMissingParameters() {
    $controller = new Controller();

    $request = new Request(['grant_type' => 'urn:ietf:params:oauth:grant-type:device_code']);
    $response = new Response();
    $response = $controller->access_token($request, $response);

    $data = json_decode($response->getContent());
    $this->assertEquals('invalid_request', $data->error);

    $request = new Request(['grant_type' => 'urn:ietf:params:oauth:grant-type:device_code', 'code' => 'foo']);
    $response = new Response();
    $response = $controller->access_token($request, $response);

    $data = json_decode($response->getContent());
    $this->assertEquals('invalid_request', $data->error);
  }

  public function testInvalidGrantType() {
    $controller = new Controller();

    $request = new Request(['grant_type' => 'foo', 'code' => 'foo'.microtime(true), 'client_id' => 'bar']);
    $response = new Response();
    $response = $controller->access_token($request, $response);

    $data = json_decode($response->getContent());
    $this->assertEquals('invalid_request', $data->error);
  }

  public function testInvalidAuthorizationCode() {
    $controller = new Controller();

    $request = new Request(['grant_type' => 'urn:ietf:params:oauth:grant-type:device_code', 'device_code' => 'foo.'.microtime(true), 'client_id' => 'bar']);
    $response = new Response();
    $response = $controller->access_token($request, $response);

    $data = json_decode($response->getContent());
    $this->assertEquals('invalid_grant', $data->error);
  }

  public function testRateLimiting() {
    $controller = new Controller();

    $request = new Request(['grant_type'=>'urn:ietf:params:oauth:grant-type:device_code', 'device_code'=>'foo.'.microtime(true), 'client_id'=>'bar']);
    $response = new Response();

    for($i=0; $i<12; $i++) {
        $response_data = $controller->access_token($request, $response);
        $data = json_decode($response_data->getContent());
        $this->assertNotEquals('slow_down', $data->error);
    }

    $response_data = $controller->access_token($request, $response);
    $data = json_decode($response_data->getContent());
    $this->assertEquals('slow_down', $data->error);
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
    $request = new Request(['grant_type'=>'urn:ietf:params:oauth:grant-type:device_code', 'client_id'=>'x', 'device_code'=>$device_code]);
    $response_data = $controller->access_token($request, $response);
    $data = json_decode($response_data->getContent());

    $this->assertEquals('authorization_pending', $data->error);
  }

  public function testAccessTokenGranted() {
    # obtain a device code
    $controller = new Controller();
    $response = new Response();

    $request = new Request(['response_type'=>'device_code', 'client_id'=>'x']);
    $response_data = $controller->generate_code($request, $response);
    $data = json_decode($response_data->getContent());
    $this->assertObjectNotHasAttribute('error', $data);

    $device_code = $data->device_code;

    # simulate the access token being granted
    Cache::set($device_code, [
      'status' => 'complete',
      'token_response' => [
        'access_token' => 'abc123',
        'expires_in' => 600,
        'custom' => 'foo'
      ]
    ]);

    # check the status of the device code
    $request = new Request(['grant_type'=>'urn:ietf:params:oauth:grant-type:device_code', 'client_id'=>'x', 'device_code'=>$device_code]);
    $response_data = $controller->access_token($request, $response);
    $data = json_decode($response_data->getContent());

    $this->assertObjectNotHasAttribute('error', $data);
    $this->assertEquals('abc123', $data->access_token);
    $this->assertEquals(600, $data->expires_in);
    $this->assertEquals('foo', $data->custom);
  }
}
