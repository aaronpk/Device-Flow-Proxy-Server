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

    $responseString = $response->__toString();
    preg_match('/Location:\s+([^\s]+)/', $responseString, $location);
    $authURL = parse_url($location[1]);
    parse_str($authURL['query'], $params);

    $this->assertEquals($params['response_type'], 'code');
    $this->assertEquals($params['client_id'], 'x');
    $this->assertEquals($params['redirect_uri'], Config::$baseURL . '/auth/redirect');
    $this->assertArrayNotHasKey('scope', $params);
    $this->assertNotEmpty($params['state']);
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

    $responseString = $response->__toString();
    preg_match('/Location:\s+([^\s]+)/', $responseString, $location);
    $authURL = parse_url($location[1]);
    parse_str($authURL['query'], $params);

    $this->assertEquals($params['response_type'], 'code');
    $this->assertEquals($params['client_id'], 'x');
    $this->assertEquals($params['scope'], 'foo');
    $this->assertEquals($params['redirect_uri'], Config::$baseURL . '/auth/redirect');
    $this->assertNotEmpty($params['state']);
  }

}
