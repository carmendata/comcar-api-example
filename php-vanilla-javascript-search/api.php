<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// load ini
$ini_array = parse_ini_file('../api.ini');

$api_key = $ini_array['key'];
$api_secret = $ini_array['secret'];

// get the current unix timestamp
$timestamp = time();

// create a unique nonce
$nonce = uniqid();

// message body to include in hash, made of request params
$message = '';

// make an ordered array of key names
$arr_param_names = array_keys($_GET);
sort($arr_param_names);

// add the params in order to the message
foreach($arr_param_names as $param){
    // get the value, blank if it's null
    $value = $_GET[ $param ];
    // if the value is not blank, add it to the message
    if( $value !== '' ) {
        $message .= $param . '=' . $value;
    }
};

// concatenate data together
$combined_data = $api_key
                . $message
                . $api_secret
                . $nonce
                . $timestamp;

// make lower case
$combined_data = strToLower($combined_data);
$hash = hash( 'sha256', $combined_data );

$call_data = array();
$call_data['key'] = $api_key;
$call_data['hash'] = $hash;
$call_data['nonce'] = $nonce;
$call_data['time'] = $timestamp;

echo(json_encode($call_data));
?>