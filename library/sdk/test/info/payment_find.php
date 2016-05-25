<?php // This file is only for show the code in the screen, through the scripts placed in test folder ?>
Find payment - /payments/{id}
------------------------------

<?php
require_once '../lib/Nimble/base/NimbleAPI.php';

$params = array(
        'clientId' => 'REPLACEME_DEMO_CLIENT_ID',
        'clientSecret' => 'REPLACEME_DEMO_CLIENT_SECRET',
        'mode' => 'demo'
);

$IdPayment = 12345;

/**
 * High level call.
 *
 * @param $NimbleApi var is the object returned in the authorization step.
 */

$NimbleApi = new NimbleAPI($params);
$p = new Payments();
$response = $p->FindPaymentClient($NimbleApi, $IdPayment);

?>

<?php
/**
 * Low level call.
 *
 * @param $NimbleApi var is the object return in the authorization step.
 * @param $payment is an array with parameters about payment to send.
 */

$NimbleApi = new NimbleAPI($params);
$NimbleApi->uri_oauth  = ConfigSDK::OAUTH_URL;
$NimbleApi->setGetfields('?grant_type=client_credentials&scope=PAYMENT');
$NimbleApi->method = 'POST';
$NimbleApi->authorization->buildAuthorizationHeader();
$NimbleApi->rest_api_call();

$NimbleApi->uri = ConfigSDK::NIMBLE_API_BASE_URL . 'payments/'.$IdPayment;
$NimbleApi->method = 'GET';
$response = $NimbleApi->rest_api_call();

?>
