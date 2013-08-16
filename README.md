PayPal Library
==============

This is a PHP library for building applications that use PayPal. It contains classes to help deal with IPN requests, PDT requests and generating PayPal buttons.

IPN Requests
------------

```php
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

```php
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

```php
<?php
$button = new PayPal\EncryptedButton;
$button->set_certificate($your_public_cert, $your_private_key);
$button->set_certificate_id($paypal_cert_id);
$button->set_paypal_cert($paypal_public_cert);
$encrypted_text = $button->encrypt([
  'cmd'           => '_xclick',
  'business'      => 'brandon.wamboldt@gmail.com',
  'lc'            => 'CA',
  'currency_code' => 'CAD',
  'no_shipping'   => '1',
  'no_note'       => '1',
  'custom'        => 'some_custom_data',
  'item_name'     => 'An Awesome Item',
  'amount'        => '123.45',
  'quantity'      => '1',
  'item_number'   => 'AWESOME-ITM-01',
  'tax'           => '0.00'
]);
?>
<form method="post" action="https://www.paypal.com/cgi-bin/webscr">
  <input type="hidden" name="cmd" value="<?= $button->get_cmd() ?>">
  <input type="hidden" name="encrypted" value="<?= $encrypted_text ?>">
  <input type="submit" value="Checkout">
</form>
```

License
-------

This code is licensed under the MIT license.
