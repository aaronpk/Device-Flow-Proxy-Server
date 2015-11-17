<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$templates = new League\Plates\Engine(dirname(__FILE__).'/../views');

class RedirectTest extends PHPUnit_Framework_TestCase {

  public function testEmptyRequest() {
    $controller = new Controller();

    $request = new Request();
    $response = new Response();
    $response = $controller->redirect($request, $response);

    $html = $response->getContent();
    $this->assertContains('Invalid Request', $html);
  }

  public function testInvalidState() {
    $controller = new Controller();

    $request = new Request(['code'=>'foo', 'state'=>'foo']);
    $response = new Response();
    $response = $controller->redirect($request, $response);

    $html = $response->getContent();
    $this->assertContains('Invalid State', $html);
  }

}
