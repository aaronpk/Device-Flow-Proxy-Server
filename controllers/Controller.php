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
    $response->setContent($this->_json($data));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

  private function html_error(Response $response, $error, $error_description) {
    $response->setStatusCode(400);
    $response->setContent(view('error', [
      'error' => $error,
      'error_description' => $error_description
    ]));
    return $response;
  }

  private function success(Response $response, $data) {
    $response->setContent($this->_json($data));
    $response->headers->set('Content-Type', 'application/json');
    return $response;
  }

  # Home Page
  public function index(Request $request, Response $response) {
    $response->setContent(view('index', [
      'title' => 'Device Flow Proxy Server'
    ]));
    return $response;
  }

  # A device submits a request here to generate a new device and user code
  public function generate_code(Request $request, Response $response) {
    # Params:
    # client_id
    # scope

    # client_id is required
    if($request->get('client_id') == null) {
      return $this->error($response, 'invalid_request', 'Missing client_id');
    }

    # We've validated everything we can at this stage.
    # Generate a verification code and cache it along with the other values in the request.
    $device_code = bin2hex(random_bytes(32));
    # Generate a PKCE code_verifier and store it in the cache too
    $pkce_verifier = bin2hex(random_bytes(32));
    $cache = [
      'client_id' => $request->get('client_id'),
      'client_secret' => $request->get('client_secret'),
      'scope' => $request->get('scope'),
      'device_code' => $device_code,
      'pkce_verifier' => $pkce_verifier,
    ];
    $user_code = random_alpha_string(4).'-'.random_alpha_string(4);
    Cache::set(str_replace('-', '', $user_code), $cache, 300); # store without the hyphen

    # Add a placeholder entry with the device code so that the token route knows the request is pending
    Cache::set($device_code, [
      'timestamp' => time(),
      'status' => 'pending'
    ], 300);

    $data = [
      'device_code' => $device_code,
      'user_code' => $user_code,
      'verification_uri' => Config::$baseURL . '/device',
      'expires_in' => 300,
      'interval' => round(60/Config::$limitRequestsPerMinute)
    ];

    return $this->success($response, $data);
  }

  # The user visits this page in a web browser
  # This interface provides a prompt to enter a device code, which then begins the actual OAuth flow
  public function device(Request $request, Response $response) {
    $response->setContent(view('device', ['code' => $request->get('code')]));
    return $response;
  }

  # The browser submits a form that is a GET request to this route, which verifies
  # and looks up the user code, and then redirects to the real authorization server
  public function verify_code(Request $request, Response $response) {
    if($request->get('code') == null) {
      return $this->html_error($response, 'invalid_request', 'No code was entered');
    }

    $user_code = $request->get('code');
    # Remove hyphens and convert to uppercase to make it easier for users to enter the code
    $user_code = strtoupper(str_replace('-', '', $user_code));

    $cache = Cache::get($user_code);
    if(!$cache) {
      return $this->html_error($response, 'invalid_request', 'Code not found');
    }    

    $state = bin2hex(random_bytes(16));
    Cache::set('state:'.$state, [
      'user_code' => $user_code,
      'timestamp' => time(),
    ], 300);

    $pkce_challenge = base64_urlencode(hash('sha256', $cache->pkce_verifier, true));

    // TODO: might need to make this configurable to support OAuth servers that have
    // custom parameters for the auth endpoint
    $query = [
      'response_type' => 'code',
      'client_id' => $cache->client_id,
      'redirect_uri' => Config::$baseURL . '/auth/redirect',
      'state' => $state,
      'code_challenge' => $pkce_challenge,
      'code_challenge_method' => 'S256',
    ];
    if($cache->scope)
      $query['scope'] = $cache->scope;

    $authURL = Config::$authEndpoint . '?' . http_build_query($query);

    $response->setStatusCode(302);
    $response->headers->set('Location', $authURL);
    return $response;
  }

  # After the user logs in and authorizes the app on the real auth server, they will
  # be redirected back to here. We'll need to exchange the auth code for an access token,
  # and then show a message that instructs the user to go back to their TV and wait.
  public function redirect(Request $request, Response $response) {
    # Verify input params
    if($request->get('state') == false || $request->get('code') == false) {
      return $this->html_error($response, 'Invalid Request', 'Request was missing parameters');
    }

    # Check that the state parameter matches
    if(!($state=Cache::get('state:'.$request->get('state')))) {
      return $this->html_error($response, 'Invalid State', 'The state parameter was invalid');
    }

    # Look up the info from the user code provided in the state parameter
    $cache = Cache::get($state->user_code);

    # Exchange the authorization code for an access token

    # TODO: Might need to provide a way to customize this request in case of
    # non-standard OAuth 2 services

    $params = [
      'grant_type' => 'authorization_code',
      'code' => $request->get('code'),
      'redirect_uri' => Config::$baseURL . '/auth/redirect',
      'client_id' => $cache->client_id,
      'code_verifier' => $cache->pkce_verifier,
    ];
    if($cache->client_secret) {
      $params['client_secret'] = $cache->client_secret;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, Config::$tokenEndpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $token_response = curl_exec($ch);
    $access_token = json_decode($token_response);

    if(!$access_token || !property_exists($access_token, 'access_token')) {
      # If there are any problems getting an access token, kill the request and display an error
      Cache::delete($state->user_code);
      Cache::delete($cache->device_code);
      return $this->html_error($response, 'Error Logging In', 'There was an error getting an access token from the service <p><pre>'.$token_response.'</pre></p>');
    }

    # Stash the access token in the cache and display a success message
    Cache::set($cache->device_code, [
      'status' => 'complete',
      'token_response' => $access_token
    ], 120);
    Cache::delete($state->user_code);

    $response->setContent(view('signed-in', [
      'title' => 'Signed In'
    ]));
    return $response;
  }

  # Meanwhile, the device is continually posting to this route waiting for the user to
  # approve the request. Once the user approves the request, this route returns the access token.
  # In addition to the standard OAuth error responses defined in https://tools.ietf.org/html/rfc6749#section-4.2.2.1
  # the server should return: authorization_pending and slow_down
  public function access_token(Request $request, Response $response) {

    if($request->get('device_code') == null || $request->get('client_id') == null || $request->get('grant_type') == null) {
      return $this->error($response, 'invalid_request');
    }

    # This server only supports the device_code response type
    if($request->get('grant_type') != 'urn:ietf:params:oauth:grant-type:device_code') {
      return $this->error($response, 'unsupported_grant_type', 'Only \'urn:ietf:params:oauth:grant-type:device_code\' is supported.');
    }

    $device_code = $request->get('device_code');

    #####################
    ## RATE LIMITING

    # Count the number of requests per minute
    $bucket = 'ratelimit-'.floor(time()/60).'-'.$device_code;

    if(Cache::get($bucket) >= Config::$limitRequestsPerMinute) {
      return $this->error($response, 'slow_down');
    }

    # Mark for rate limiting
    Cache::incr($bucket);
    Cache::expire($bucket, 60);
    #####################

    # Check if the device code is in the cache
    $data = Cache::get($device_code);

    if(!$data) {
      return $this->error($response, 'invalid_grant');
    }

    if($data && $data->status == 'pending') {
      return $this->error($response, 'authorization_pending');
    } else if($data && $data->status == 'complete') {
      # return the raw access token response from the real authorization server
      Cache::delete($device_code);
      return $this->success($response, $data->token_response);
    } else {
      return $this->error($response, 'invalid_grant');
    }
  }

  private function _json($data) {
    return json_encode($data, JSON_PRETTY_PRINT+JSON_UNESCAPED_SLASHES)."\n";
  }

}
