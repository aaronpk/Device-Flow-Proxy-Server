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

  # Home Page
  public function index(Request $request, Response $response) {
    $response->setContent(view('index', [
      'title' => 'TV Auth'
    ]));
    return $response;
  }

  # A device submits a request here to generate a new device and user code
  public function generate_code(Request $request, Response $response) {
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
      // TODO: might need to also store client_secret here so that we can use it later
      'scope' => $request->get('scope'),
      'device_code' => $device_code
    ];
    $user_code = rand(100000,999999);

    Cache::set($user_code, $cache);

    $data = [
      'device_code' => $device_code,
      'user_code' => $user_code,
      'verification_uri' => Config::$baseURL . '/device',
      'interval' => 5
    ];

    $response->setContent(json_encode($data));
    $response->headers->set('Content-Type', 'application/json');

    return $response;
  }

  # The user visits this page in a web browser
  # This interface provides a prompt to enter a device code, which then begins the actual OAuth flow
  public function device(Request $request, Response $response) {

  }

  # The browser submits a form that is a GET request to this route, which verifies
  # and looks up the user code, and then redirects to the real authorization server
  public function verify_code(Request $request, Response $response) {
    if($request->get('code') == null) {
      return $this->error($response, 'invalid_request');
    }

    // TODO: this response is not defined in the spec, should it be? or just part of a tutorial?
    $cache = Cache::get($request->get('code'));
    if(!$cache) {
      return $this->error($response, 'invalid_request', 'Code not found');
    }

    // TODO: might need to make this configurable to allow for non-standard OAuth 2 servers
    $query = [
      'response_type' => 'code',
      'client_id' => $cache->client_id,
      'redirect_uri' => Config::$baseURL . '/auth/redirect',
    ];
    if($cache->scope)
      $query['scope'] = $cache->scope;
    
    $authURL = Config::$authServerURL . '?' . http_build_query($query);

    $response->headers->set('Location', $authURL);
    return $response;
  }

}
