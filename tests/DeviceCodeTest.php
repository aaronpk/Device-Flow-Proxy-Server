<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceCodeTest extends PHPUnit_Framework_TestCase {

  public function testEmptyRequest() {
    $controller = new Controller();
    $request = new Request();
    $response = new Response();
    $response = $controller->generate_code($request, $response);
    $data = json_decode($response->getContent());
    $this->assertEquals($data->error, 'unsupported_response_type');
  }

  public function testMissingClientID() {
    $controller = new Controller();
    $request = new Request(['response_type'=>'device_code']);
    $response = new Response();
    $response = $controller->generate_code($request, $response);
    $data = json_decode($response->getContent());
    $this->assertEquals($data->error, 'invalid_request');
  }

  public function testGeneratesCode() {
    $controller = new Controller();
    $request = new Request(['response_type'=>'device_code', 'client_id'=>'x']);
    $response = new Response();
    $response = $controller->generate_code($request, $response);
    $data = json_decode($response->getContent());
    $this->assertObjectNotHasAttribute('error', $data);
    $this->assertObjectHasAttribute('device_code', $data);
    $this->assertObjectHasAttribute('user_code', $data);
    $this->assertObjectHasAttribute('verification_uri', $data);
    $this->assertStringMatchesFormat('%x', $data->device_code);
    $this->assertInternalType('integer', $data->user_code);
    $this->assertStringMatchesFormat('%s/device', $data->verification_uri);
  }

}
