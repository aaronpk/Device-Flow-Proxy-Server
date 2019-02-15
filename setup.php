<?php
chdir(__DIR__);

if(file_exists('/config.php')) {
	echo "Config file already exists: config.php\n";
	die();
}

$file = file_get_contents('config.template.php');
$secret = bin2hex(random_bytes(32));
$file = str_replace('{secret}', $secret, $file);
file_put_contents('config.php', $file);

echo "Generated a random secret and copied it to config.php\n";
echo "Now fill in your authorization server URLs in that file.\n";
