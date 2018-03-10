<?php
require(__DIR__ . '/../vendor/autoload.php');

$alice_kp = sodium_crypto_sign_keypair();
$alice_sk = sodium_crypto_sign_secretkey($alice_kp);
var_dump(base64_encode($alice_sk));

$alice_pk = sodium_crypto_sign_publickey($alice_kp);
var_dump(base64_encode($alice_pk));

$message = 'This is a test message.';
$signature = sodium_crypto_sign_detached($message, $alice_sk);
var_dump(base64_encode($signature));

if (sodium_crypto_sign_verify_detached($signature, $message, $alice_pk)) {
    echo 'OK', PHP_EOL;
} else {
    throw new Exception('Invalid signature');
}
