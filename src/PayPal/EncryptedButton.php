<?php

namespace PayPal;

/**
 * Helper class to encrypt arguments using a private key for use with PayPal
 * encrypted buttons.
 *
 * @author Brandon Wamboldt <brandon.wamboldt@gmail.com>
 */
class EncryptedButton
{
  /**
   * @var resource
   */
  protected $private_key;

  /**
   * @var resource
   */
  protected $public_cert;

  /**
   * @var resource
   */
  protected $paypal_cert;

  /**
   * @var string
   */
  protected $certificate_id;

  /**
   * @var string
   */
  protected $tmp_dir = '/tmp';

  /**
   * Constructor.
   */
  public function __construct()
  {
    $this->tmp_dir = sys_get_temp_dir();
  }

  /**
   * Get the 'cmd' HTML variable value for use with encrypted buttons.
   *
   * @return string
   */
  public function get_cmd()
  {
    return '_s-xclick';
  }

  /**
   * Set our public certificate and private key.
   *
   * @param  string $public_cert
   * @param  string $private_key
   * @return self
   */
  public function set_certificate($public_cert, $private_key)
  {
    // Parse the certificate
    $this->public_cert = openssl_x509_read($public_cert);

    // Parse our private key
    $this->private_key = openssl_get_privatekey( $private_key );

    // Validate our certificate & private key
    if (!$this->public_cert || !$this->private_key) {
      throw new SecurityException('Invalid public certificate');
    }

    // Validate that our private key corresponds with our public certificate
    if (!openssl_x509_check_private_key($this->public_cert, $this->private_key)) {
      throw new SecurityException('Your private key does not correspond with your public certificate');
    }

    return $this;
  }

  /**
   * Set the ID assigned to your encryption certificates by PayPal.
   *
   * @param  string $id
   * @return self
   */
  public function set_certificate_id($id)
  {
    $this->certificate_id = $id;

    return $this;
  }

  /**
   * Set the public certificate for PayPal.
   *
   * @param  string $certificate
   * @return self
   */
  public function set_paypal_certificate($certificate)
  {
    // Parse the certificate
    $this->paypal_cert = openssl_x509_read($certificate);

    // Validate the certificate
    if (!$this->paypal_cert) {
      throw new SecurityException('The PayPal public certificate is invalid');
    }

    return $this;
  }

  /**
   * Set the directory into which our temporary files are written.
   *
   * @param  string $dir
   * @return self
   */
  public function set_tmp_dir($dir)
  {
    $this->tmp_dir = $dir;

    return $this;
  }

  /**
   * Use our encryption certificate to encrypt the given parameters.
   *
   * @param  array $params
   * @return string
   */
  public function encrypt(array $params)
  {
    // Make sure we have the data we need
    if (empty($this->certificate_id) || empty($this->public_cert) || empty($this->paypal_cert)) {
      throw new SecurityException('Please set your public certificate, PayPal certificate and certificate ID');
    }

    // Compose clear text data
    $encrypted_text = '';
    $clear_text     = 'cert_id=' . $this->certificate_id;

    foreach ($params as $key => $param) {
      $clear_text .= sprintf("\n%s=%s", $key, $param);
    }

    // Generate temporary file names for various certs
    $clear_file     = tempnam($this->tmp_dir, 'clear_');
    $signed_file    = str_replace('clear', 'signed', $clear_file);
    $encrypted_file = str_replace('clear', 'encrypted', $clear_file);

    // Write our clear text file
    $out = fopen($clear_file, 'wb');
    fwrite($out, $clear_text);
    fclose($out);

    // Sign our clear text file
    if (!openssl_pkcs7_sign($clear_file, $signed_file, $this->public_cert, $this->private_key, [], PKCS7_BINARY)) {
      throw new SecurityException('Unable to sign file');
    }

    // Get back our signed file contents
    $signed_data = explode("\n\n", file_get_contents($signed_file));

    // Write the signed file contents (part of them)
    $out = fopen($signed_file, 'wb');
    fwrite($out, base64_decode($signed_data[1]));
    fclose($out);

    // Encrypt our signed file
    if (!openssl_pkcs7_encrypt($signed_file, $encrypted_file, $this->paypal_cert, [], PKCS7_BINARY)) {
      throw new SecurityException('Unable to encrypt file');
    }

    // Get the encrypted data
    $encrypted_data = explode("\n\n", file_get_contents($encrypted_file));
    $encrypted_text = $encrypted_data[1];

    // Delete temporary files
    @unlink($clear_file);
    @unlink($signed_file);
    @unlink($encrypted_file);

    // Signature
    $encrypted_text = "-----BEGIN PKCS7-----\n" . $encrypted_text . "\n-----END PKCS7-----";

    return $encrypted_text;
  }
}
