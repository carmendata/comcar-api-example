<?php
    $page_title = 'Comcar API PHP single vehicle from search demo';

    // default params to send to the search endpoint
    $params = [
        'make' => $_GET['make'],
        'model' => $_GET['model'],
        'derivative' => $_GET['derivative']
    ];

    $api_endpoint = '/v1/vehicles/search';

    // common will build the HMAC authentication needed for the API call
    include_once('common.php');
?>

<!DOCTYPE html>
<html lang="en">
    <?php include 'head.php' ?>
    <body>
        <h1><?php echo $page_title ?></h1>
        <p>
            Simple demonstration page to display a single vehicle from
            <a href="https://api.comcar.co.uk">Comcar API</a>
        </p>
        <p>
            This vehicle is taken from the search endpoint which is limited to a single vehicle using the make, model and derivative GET params.
        </p>
        <a href="./">< Back to list</a>
        <?php
            // if we have vehicles, display it
            if(count($api_data->data)) {
                $vehicle = $api_data->data[0];
                echo '<div class="vehicles">';
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

                // display single vehicle
                echo '<div class="vehicle">';
                include 'vehicle.php';
                echo '</div>';
            } else {
                // if we have no vehicles, dump the call contents out so we can debug. DON'T DO THIS ON A PRODUCTION SITE
                var_dump($api_call_contents);
            }
        ?>
    </body>
</html>
