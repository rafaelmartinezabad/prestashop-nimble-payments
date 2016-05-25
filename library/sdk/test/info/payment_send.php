<?php // This file is only for show the code in the screen, through the scripts placed in test folder ?>

Send payment - /payments
------------------------------

<?php
//require_once 'PATH_TO_SDK/lib/Nimble/base/NimbleAPI.php';
require_once '../lib/Nimble/base/NimbleAPI.php';

$payment = array(
        'amount' => 1010,
        'currency' => 'EUR',
        'customerData' => 'idSample12345',
        'paymentSuccessUrl' => 'https://my-commerce.com/payments/success',
        'paymentErrorUrl' => 'https://my-commerce.com/payments/error'
        );

$params = array(
        'clientId' => 'REPLACEME_DEMO_CLIENT_ID',
        'clientSecret' => 'REPLACEME_DEMO_CLIENT_SECRET',
        'mode' => 'demo'
);

/**
 * High level call.
 *
 * @param $NimbleApi var is the object returned in the authorization step.
 * @param $payment is an array with parameters about transaction to send.
 */

$NimbleApi = new NimbleAPI($params);
$p = new Payments();
$response = $p->SendPaymentClient($NimbleApi, $payment);

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

$NimbleApi->setPostfields(json_encode($payment));
$NimbleApi->uri = ConfigSDK::NIMBLE_API_BASE_URL . 'payments';
$NimbleApi->method = 'POST';
$response2 = $NimbleApi->rest_api_call(); 
?>