<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyUserCodeTest extends PHPUnit\Framework\TestCase {

  public function testEmptyRequest() {
    $controller = new Controller();

    $request = new Request();
    $response = new Response();
    $response = $controller->verify_code($request, $response);

    $error = $response->getContent();
    $this->assertStringContainsString('No code was entered', $error);
  }

  public function testInvalidUserCode() {
    $controller = new Controller();

    $request = new Request(['code'=>'xxxx']);
    $response = new Response();
    $response = $controller->verify_code($request, $response);

    $error = $response->getContent();
    $this->assertStringContainsString('invalid_request', $error);
    $this->assertStringContainsString('Code not found', $error);
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

    $this->assertEquals('code', $params['response_type']);
    $this->assertEquals('x', $params['client_id']);
    $this->assertEquals(Config::$baseURL . '/auth/redirect', $params['redirect_uri']);
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

    $this->assertEquals('code', $params['response_type']);
    $this->assertEquals('x', $params['client_id']);
    $this->assertEquals('foo', $params['scope']);
    $this->assertEquals(Config::$baseURL . '/auth/redirect', $params['redirect_uri']);
    $this->assertNotEmpty($params['state']);
  }

}
