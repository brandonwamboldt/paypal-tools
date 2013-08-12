PayPal Library
==============

This is a PHP library for building applications that use PayPal. It contains classes to help deal with IPN requests, PDT requests and generating PayPal buttons.

IPN Requests
------------

```
<?php
$ipn = new PayPal\IpnRequest;
$ipn->set_timeout(5);
$ipn->allow_test_ipns(true);

$ipn->process(function($post_data) {
  // Save it to the database or something
});
```

PDT Requests
------------

```
<?php
$pdt = new PayPal\PdtRequest($paypal_pdt_token);
$pdt->set_timeout(5);
$pdt->allow_sandbox(true);

$pdt->process(function($transaction_data) {
  // Show the user a receipt
}, function() {
  // Validation failed, show the user an error message or pull their receipt
  // from your database. Validation will fail after 3-5 successful verification
  // attempts.
})
```

PayPal Buttons
--------------

Coming Soon!

License
-------

This code is licensed under the MIT license.
