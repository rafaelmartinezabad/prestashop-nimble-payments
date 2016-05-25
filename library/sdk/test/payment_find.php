<?php
//show code
highlight_file("/info/payment_find.php");
?>
<br />
------------------------------------------------------------------------------------------------------------
<br />

<?php
/* High Level call */
require_once '../lib/Nimble/base/NimbleAPI.php';

$params = array(
        'clientId' => '729DFCD7A2B4643A0DA3D4A7E537FC6E',
        'clientSecret' => 'jg26cI3O1mB0$eR&fo6a2TWPmq&gyQoUOG6tClO%VE*N$SN9xX27@R4CTqi*$4EO',
        'mode' => 'demo'
);

$IdPayment = 541;

$NimbleApi = new NimbleAPI($params);
$p = new Payments();
$response = $p->FindPaymentClient($NimbleApi, $IdPayment);

?>
--------------
<?php

/* Low Level call */
$NimbleApi = new NimbleAPI($params);
$NimbleApi->uri = ConfigSDK::NIMBLE_API_BASE_URL .'payments/'.$IdPayment;
$NimbleApi->method = 'GET';
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