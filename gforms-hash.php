<?php
/**
 * Created by PhpStorm.
 * User: Nabeel
 * Date: 18-Jun-17
 * Time: 12:28 PM
 */

$string = '';
$private_key = '';

$hash = hash_hmac('sha1', $string, $private_key, true);
$sig = rawurlencode(base64_encode($hash));

echo $sig;
