<?php

if(!isset($argv[1])) {
	echo "Usage: php client.php client_id\n";
	die();
}

$client_id = $argv[1];
$base_url = $argv[2] ?? 'http://localhost:8080';

$ch = curl_init($base_url.'/device/code');
$params = [
	'client_id' => $client_id,
];
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$start = json_decode($response, true);

if(!isset($start['device_code'])) {
	echo "Something went wrong trying to start the Device Flow\n";
	echo "Here is the raw response from the server:\n";
	echo $response."\n";
	die();
}

echo "Please visit this URL in your browser, and confirm the code in the browser matches the code shown:\n";

echo $start['verification_uri'].'?code='.$start['user_code']."\n";
echo $start['user_code']."\n";

$done = false;
while($done == false) {
	sleep($start['interval']);

	$ch = curl_init($base_url.'/device/token');
	$params = [
		'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
		'client_id' => $client_id,
		'device_code' => $start['device_code'],
	];
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	$token = json_decode($response, true);

	if(isset($token['access_token'])) {
		echo "You successfully logged in!\n";
		echo "Here is the access token returned from the server:\n";
		echo $token['access_token']."\n";
		die();
	}
	if(isset($token['error']) && $token['error'] != 'authorization_pending') {
		echo "The token endpoint returned an unrecoverable error:\n";
		echo $response."\n";
		die();
	}
	# go back to the top and wait...
}
