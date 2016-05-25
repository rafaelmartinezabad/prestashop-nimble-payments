<?php
//show code
highlight_file("/info/payment_send.php");
?>
<br />
------------------------------------------------------------------------------------------------------------
<br />

<?php
require_once '../lib/Nimble/base/NimbleAPI.php';

$payment = array(
         'amount' => 1010,
	 	 'currency' => 'EUR',
         'customerData' => 'idSample12345',
         'paymentSuccessUrl' => 'https://my-commerce.com/payments/success',
         'paymentErrorUrl' => 'https://my-commerce.com/payments/error'
        );

$params = array(
        'clientId' => '729DFCD7A2B4643A0DA3D4A7E537FC6E',
        'clientSecret' => 'jg26cI3O1mB0$eR&fo6a2TWPmq&gyQoUOG6tClO%VE*N$SN9xX27@R4CTqi*$4EO',
        'mode' => 'demo'
);

/* High Level call */
$NimbleApi = new NimbleAPI($params);
$p = new Payments();
$response = $p->SendPaymentClient($NimbleApi, $payment);

?>

<?php
/* Low Level call */
$NimbleApi = new NimbleAPI($params);

$NimbleApi->setPostfields(json_encode($payment));
$NimbleApi->uri = ConfigSDK::NIMBLE_API_BASE_URL . 'payments';
$NimbleApi->method = 'POST';
$response2 = $NimbleApi->rest_api_call(); 
?>

<br /><pre>
Response: (var_dump($response))
<?php
var_dump($response);
?>

<br /><pre>
Response2: (var_dump($response2))
<?php
var_dump($response2);
?>