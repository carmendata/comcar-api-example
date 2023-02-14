<?php
    // only use these in development to display errors, don't have these on your production site
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // load ini
    $ini_array = parse_ini_file('../api.ini');

    $api_key = $ini_array['key'];
    $api_secret = $ini_array['secret'];

    // build the HMAC authentication request https://documenter.getpostman.com/view/3327718/SzS4SSvu#authentication
    $current_message = '';

    // make an ordered array of key names
    $arr_param_names = array_keys($params);
    sort($arr_param_names);

    // add the params in order to the message
    foreach($arr_param_names as $param){
        // get the value, blank if it's null
        $value = $params[ $param ];
        // if the value is not blank, add it to the message
        if( $value !== '' ) {
            $current_message .= $param . '=' . $value;
        }
    };

    // build the message to hash
    $nonce = rand();
    $timestamp = time();
    $combined_data = $api_key
                    . $current_message
                    . $api_secret
                    . $nonce
                    . $timestamp;

    // make lower case
    $combined_data = strtolower($combined_data);

    // build sha 256 hash
    $hash = hash(
        'SHA256',
        $combined_data
    );

    // create a curl object to the api search endpoint
    $curl = curl_init("https://api.comcar.co.uk" . $api_endpoint . "?" . http_build_query($params));
    // tell the curl method to return the data from the call
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    // set the headers for the call
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'hash: ' . $hash,
        'key: ' . $api_key,
        'nonce: ' . $nonce,
        'time: ' . $timestamp,
        'x-timestamp: ' . $timestamp
    ));
    $api_call_contents = curl_exec($curl) ?: 'Failed to load API';

    $api_data = json_decode($api_call_contents);
?>