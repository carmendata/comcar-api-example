<?php
    $page_title = 'Comcar API PHP static list demo';

    // default params to send to the search endpoint
    $params = [
        'group_by' => 'model',
        'group_order_by' => 'otr asc',
        'order_by' => 'battery_range desc',
        'min_battery_range' => 1,
    ];

    $api_endpoint = '/v1/vehicles/search';

    // common will build the HMAC authentication needed for the API call
    include_once('common.php');

    $vehicles = $api_data->data;
?>

<!DOCTYPE html>
<html lang="en">
	<?php include 'head.php' ?>
    <body>
        <h1><?php echo $page_title ?></h1>
        <p>
            Simple demonstration page to display a static list of data from
            <a href="https://api.comcar.co.uk">Comcar API</a>
        </p>
        <p>
            This list shows the cheapest vehicle per model, with the best battery range at the top of the list
        </p>
        <?php
            // if we have vehicles, display them
            if(count($vehicles)) {
                echo '<div class="vehicles">';
                for($i = 0; $i < 10; $i++) {
                    $vehicle = $vehicles[$i];
                    // build the rapid charge string, e.g. "20% to 80% in 30 minutes"
                    if($vehicle->fastest_charge_time_minutes > 0) {
                        $rapid_charge = $vehicle->fastest_charge_percent_from
                            . "% to "
                            . $vehicle->fastest_charge_percent_to
                            . "% in "
                            . $vehicle->fastest_charge_time_minutes . " minutes";
                    } else {
                        $rapid_charge = '-';
                    }

                    // build the href for the detail page
                    $detail_href = 'detail.php'
                        . '?make=' . $vehicle->make
                        . '&model=' . $vehicle->model
                        . '&derivative=' . $vehicle->derivative;

                    // loop over vehicles and build a big link for each one
                    echo '<a class="vehicle" href="' . $detail_href . '">';
                    include 'vehicle.php';
                    echo '</a>';
                }
                echo '</div>';
            } else {
                // if we have no vehicles, dump the call contents out so we can debug. DON'T DO THIS ON A PRODUCTION SITE
                var_dump($api_call_contents);
            }
        ?>
    </body>
</html>
