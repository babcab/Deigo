<?php
$checkpoints = '
        [
            {
                "lat": 42.350950,
                "lng": -71.089155
            },
            {
                "lat": 42.340724,
                "lng": -71.099705
            },
            {
                "lat": 42.357169,
                "lng": -71.129075
            },
            {
                "lat": 42.342539,
                "lng": -71.144350
            },
            {
                "lat": 42.356250,
                "lng": -71.180775
            }
        ]';


$checkpoints_decoded = json_decode($checkpoints);

echo sprintf("This %f",  $checkpoints_decoded[0]->lat);
