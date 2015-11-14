<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Controller {

  private function error(Response $response, $error, $error_description=false) {
    $data = [
      'error' => $error
    ];
    if($error_description) {
      $data['error_description'] = $error_description;
    }

    $response->setStatusCode(400);
    $response->setContent(json_encode($data));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

  public function index(Request $request, Response $response) {
    $response->setContent(view('index', [
      'title' => 'TV Auth'
    ]));
    return $response;
  }

  public function generate_code(Request $request, Response $response) {
    $data = [];

    # Params:
    # client_id
    # scope
    # response_type=device_code

    # This server only supports the device_code response type
    if($request->get('response_type') != 'device_code') {
      return $this->error($response, 'unsupported_response_type', 'Only \'device_code\' is supported.');
    }

    # client_id is required
    if($request->get('client_id') == null) {
      return $this->error($response, 'invalid_request');
    }

    # We've validated everything we can at this stage.
    # Generate a verification code and cache it along with the other values in the request.
    $device_code = hash('sha256', time().rand().$request->get('client_id'));
    $cache = [
      'client_id' => $request->get('client_id'),
      'scope' => $request->get('scope'),
      'device_code' => $device_code
    ];
    $user_code = rand(100000,999999);

    Cache::set($user_code, $cache);

    $data = [
      'device_code' => $device_code,
      'user_code' => $user_code,
      'verification_uri' => Config::$baseURL . '/device'
    ];

    $response->setContent(json_encode($data));
    $response->headers->set('Content-Type', 'application/json');

    return $response;
  }

}
