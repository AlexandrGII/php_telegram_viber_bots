<?php
require_once("viber-bot-php/vendor/autoload.php");

use Viber\Client;

$apiKey = 'fnsnflsknflknaslkdjnflaskjndef'; 
$webhookUrl = 'https://server.dom.io/msgapi/viber/bot.php'; 

try {
	$client = new Client([ 'token' => $apiKey ]);
	$result = $client->setWebhook($webhookUrl);
	echo "Success!\n";
} catch (Exception $e) {
	echo "ERROR";
	print_r($e);
}
