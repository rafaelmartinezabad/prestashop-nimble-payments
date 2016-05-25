NimblePayments SDK for PHP
======================

NimblePayments SDK for PHP makes it easy to access the NimblePayments REST API to add payment services to your e-commerce or site.

## Requirements

* PHP 5.2 or above
* curl & json extensions must be enabled

## Installation

### From source

1. Download or clone this repo. It includes SDK and samples.
2. Go to your PHP project directory. If you do not have one, just create a directory and enter in it.
3. Unzip, and copy directory to your project location
4. Now you are ready to include NimblePayments SDK in your scripts

## Configuration

Is not necessary configure nothing. Only mention that exist a file named base/ConfigSDK.php with some parameters such as URL API of NimbleePayments, but you don't need modify them.
Developers that want to use this library should add their specific API keys when they create a new object of NimbleAPI class.

``` php
require_once './nimble-dev-sdk-php-master/lib/Nimble/base/NimbleAPI.php';

$params = array(
        'clientId' => '729DFCD7A2B4643A0DA3D4A7E537FC6E',
        'clientSecret' => 'jg26cI3O1mB0$eR&fo6a2TWPmq&gyQoUOG6tClO%VE*N$SN9xX27@R4CTqi*$4EO',
        'mode' => 'sandbox'
);

$NimbleApi = new NimbleAPI($params);
```

> Now we are ready to make operations such as send payments, get payments, etc. Next will see a sample of send payment. 

> In addition, the parameter 'mode' have two possible values: sandbox or real. 'Sandbox' is used to call to the demo environment for make tests and 'real' is for call to real environment.

### Environments

NimbleePayments has two environments:

* Sandbox: testing environment
* Real: real environment

## Usage

### Payment

See detailed information about [payments](https://github.com/nimblepayments/sdk-php/wiki/Payment) with NimbleePayments.

## Test

In `test` folder you will find scripts implementing a basics operations that uses NimbleePayments SDK as payment platform.
