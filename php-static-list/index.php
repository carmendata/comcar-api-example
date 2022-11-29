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

    // the message is made up of the order/filter etc params
    // params need to be put into alphabetical order
    $params = [
        'group_by' => 'model',
        'group_order_by' => 'otr asc',
        'order_by' => 'battery_range desc',
        'min_battery_range' => 1,
    ];

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
    $curl = curl_init("https://api.comcar.co.uk/v1/vehicles/search?" . http_build_query($params));
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
    $vehicles = $api_data->data;
?>

<style>
    * { font-family: sans-serif }

    table {
        border: thin solid #EEE;
        text-align: left;
        margin: 0.5em 0;
    }

    th, td {
        padding: 0.25em;
    }

    td img {
        width: 200px;
    }
</style>
<h1>Comcar API PHP static list demo</h1>
<p>
    Simple demonstration page to display a static list of data from
    <a href="https://api.comcar.co.uk">Comcar API</a>
</p>
<p>
    This list shows the cheapest vehicle per model, with the best battery range at the top of the list
</p>
<?php if(count($vehicles)) { ?>
<table class="vehicle-data">
    <thead>
        <tr>
            <th>Vehicle</th>
            <th>Bodystyle</th>
            <th>Fuel type</th>
            <th>WLTP CO2g/km</th>
            <th>Price from (£)</th>
            <th>Battery Capacity (kwh)</th>
            <th>Battery range (m)</th>
            <th>Efficiency</th>
            <th>BIK Band</th>
            <th>Rapid Charge</th>
            <th>Image</th>
        </tr>
    </thead>
    <tbody>
        <?php
            for($i = 0; $i < 10; $i++) {
                if($vehicles[$i]->fastest_charge_time_minutes > 0) {
                    $rapid_charge = $vehicles[$i]->fastest_charge_percent_from
                        . "% to "
                        . $vehicles[$i]->fastest_charge_percent_to
                        . "% in "
                        . $vehicles[$i]->fastest_charge_time_minutes . " minutes";
                } else {
                    $rapid_charge = '-';
                }
                echo "<tr>"
                        . "<td>"
                            . $vehicles[$i]->make
                            . " "
                            . $vehicles[$i]->model
                            . " "
                            . $vehicles[$i]->derivative . " " . $vehicles[$i]->derivative_extra 
                        . "</td>"
                        . "<td>" . $vehicles[$i]->bodystyle . "</td>"
                        . "<td>" . $vehicles[$i]->fueltype . "</td>"
                        . "<td>" . $vehicles[$i]->wltp_co2gpkm . "</td>"
                        . "<td>" . $vehicles[$i]->otr . "</td>"
                        . "<td>" . $vehicles[$i]->capacity_kwh . "</td>"
                        . "<td>" . $vehicles[$i]->battery_range . "</td>"
                        . "<td>" . $vehicles[$i]->electric_energy_consumption . "</td>"
                        . "<td>" . $vehicles[$i]->co2_percentage_yr_1 . "</td>"
                        . "<td>" . $rapid_charge . "</td>"
                        . "<td><img src=\"https://s3-eu-west-1.amazonaws.com/media.comcar.co.uk/vehicle/image/clear/350x219/" . $vehicles[$i]->image . ".png\" /></td>"
                    . "</tr>";
            }
        ?>
    </tbody>
</table>
<?php
    } else {
        var_dump($api_call_contents);
    }
?>

