<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GenerateDeviceCodeTest extends PHPUnit\Framework\TestCase {

  public function testEmptyRequest() {
    $controller = new Controller();
    $request = new Request();
    $response = new Response();
    $response = $controller->generate_code($request, $response);
    $data = json_decode($response->getContent());
    $this->assertEquals($data->error, 'invalid_request');
  }

  public function testGeneratesCode() {
    $controller = new Controller();
    $request = new Request(['client_id'=>'x']);
    $response = new Response();
    $response = $controller->generate_code($request, $response);
    $data = json_decode($response->getContent());
    # Make sure there's no error
    $this->assertObjectNotHasAttribute('error', $data);
    # Check the expected properties exist
    $this->assertObjectHasAttribute('device_code', $data);
    $this->assertObjectHasAttribute('user_code', $data);
    $this->assertObjectHasAttribute('verification_uri', $data);
    # Make sure the values are as expected
    $this->assertStringMatchesFormat('%x', $data->device_code);
    $this->assertStringMatchesFormat('%s', $data->user_code);
    $this->assertStringMatchesFormat('%s/device', $data->verification_uri);
    # Check that the info is cached against the user code
    $cache = Cache::get(str_replace('-','',$data->user_code));
    $this->assertNotNull($cache);
    $this->assertEquals($cache->client_id, 'x');
    $this->assertEquals($cache->device_code, $data->device_code);
  }

  public function testGeneratesCodeWithScope() {
    $controller = new Controller();
    $request = new Request(['response_type'=>'device_code', 'client_id'=>'x', 'scope'=>'user']);
    $response = new Response();
    $response = $controller->generate_code($request, $response);
    $data = json_decode($response->getContent());
    # Make sure there's no error
    $this->assertObjectNotHasAttribute('error', $data);
    # Check that the info is cached against the user code
    $cache = Cache::get(str_replace('-','',$data->user_code));
    $this->assertNotNull($cache);
    $this->assertEquals($cache->client_id, 'x');
    $this->assertEquals($cache->device_code, $data->device_code);
    $this->assertEquals($cache->scope, 'user');
  }

}
