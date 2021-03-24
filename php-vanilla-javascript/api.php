<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// load ini
$ini_array = parse_ini_file('./api.ini');

$api_key = $ini_array['key'];
$api_secret = $ini_array['secret'];

/**
 * build a hash an return as the response
 */

$timestamp = time();

// create a unique nonce
$nonce = uniqid();

// the message is made up of the order/filter etc params
// params need to be put into alphabetical order
$current_message = '';
// $query_params = $_GET;
// $struct_params = {};

// make a simple struct of key/value pairs
// query_params.each(function(param){
    // check if the key is not disabled
    // if( !param.disabled ) {
        // struct_params[ param.key ] = param.value;
    // }
// });

// make an ordered array of key names
// $arr_param_names = Object.keys( struct_params ).sort();

// add the params in order to the message
// arr_param_names.each(function(param){
    // get the value, blank if it's null
    // $val = struct_params[ param ]||'';
    // if the value is not blank, add it to the message
    // if( val !== '' ) {
        // current_message+=param+'='+val;
    // }
// });

// concatenate data together
$combined_data = $api_key
                . $current_message
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