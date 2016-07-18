#Nimble Payments Prestashop Module

##Generating nimblepayments-prestashop.zip
It is possible to generate a unique plugin to be used in your code using the Composer. To that end, in a empty folder run the following commands:
```
git clone git@github.com:nimblepayments/prestashop-nimble-payments.git nimblepayment
cd nimblepayment/
composer.phar update
composer.phar zip
```
The zip file ```nimblepayments-prestashop.zip``` is generated in the current folder.
