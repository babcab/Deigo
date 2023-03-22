<?php

$url = 'https://maps.googleapis.com/maps/api/directions/json';

function getRoute($url, $p_lat, $p_long, $d_lat, $d_long, $api_key)
{
    $request_url = $url . '?origin=' . $p_lat . ',' . $p_long . '&destination=' . $d_lat . ',' . $d_long . '&key=' . $api_key;
    $curl = curl_init($request_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

$directions = getRoute($url, "-0.1383219181126606", "-78.47949502180954", "-0.13780693548696138", "-78.48749873306527", "test");
$duration = $directions['routes'][0]['legs'][0]['duration']['value'];
// echo $duration;

$steps = $directions['routes'][0]['legs'][0]['steps'];
// print_r($steps);


function get_nodes($steps)
{
    $nodes = [];
    $prev_end_location = [];
    foreach ($steps as $step) {
        $start_location = $step['start_location'];
        $end_location = $step['end_location'];
        if ($prev_end_location == $start_location) {
            $nodes[] = $end_location;
        } else {
            $nodes[] = $start_location;
            $nodes[] = $end_location;
        }
        $prev_end_location = $end_location;
    }
    return $nodes;
}

$nodes = get_nodes($steps);
