<?php
    $fields = [
        'bodystyle',
        'fueltype',
        'wltp_co2gpkm',
        'otr',
        'capacity_kwh',
        'battery_range',
        'electric_energy_consumption',
        'co2_percentage_yr_1',
        'rapid_charge'
    ];

    echo '<div class="vehicle_details vehicle_item">'
        . '<strong>'
            . $vehicle->make
            . ' '
            . $vehicle->model
            . ' '
            . $vehicle->derivative . ' ' . $vehicle->derivative_extra
        . '</strong>'
        . '<table><tbody>';

        // output the fields, title and value per row
        foreach($fields as $field) {
            // for rapid_charge, we need to build the string
            if($field == 'rapid_charge') {
                if($vehicle->fastest_charge_time_minutes > 0) {
                    $field_value = $vehicle->fastest_charge_percent_from
                        . "% to "
                        . $vehicle->fastest_charge_percent_to
                        . "% in "
                        . $vehicle->fastest_charge_time_minutes . " minutes";
                } else {
                    $field_value = '-';
                }
            } else {
                $field_value = $vehicle->$field;
            }

            echo '<tr><th>' . $field . '</th><td>' . $field_value . '</td></tr>';
        }

        echo '</tbody></table>'
    . '</div>'
    . '<div class="vehicle_item">'
        . '<img class="vehicle_image" src="https://s3-eu-west-1.amazonaws.com/media.comcar.co.uk/vehicle/image/clear/350x219/' . $vehicle->image . '.png" />'
    . '</div>'
?>