<?php
//show code
highlight_file("/info/authentication.php");
?>
<br />
------------------------------------------------------------------------------------------------------------
<br />
<?php
require_once '../lib/Nimble/base/NimbleAPI.php';

$params = array(
        'clientId' => '729DFCD7A2B4643A0DA3D4A7E537FC6E',
        'clientSecret' => 'jg26cI3O1mB0$eR&fo6a2TWPmq&gyQoUOG6tClO%VE*N$SN9xX27@R4CTqi*$4EO',
        'mode' => 'demo'
);

/* High & Low Level call */
$response = new NimbleAPI($params);

?>

<br /> <pre>
Response: (var_dump($response))
<?php
var_dump($response);
?>