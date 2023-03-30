<?php

/* 
I'm assuming the url is placed as an enviroment variable, so for this example it is hardcoded
*/
// $url = 'https://maps.googleapis.com/maps/api/directions/json';


/* 
This function collects all the info related to the route from the pick up point to the drop off point.
It has the steps of the route, the distance, the duration, the start and end location, etc.
*/
function getRoute($url, $p_lat, $p_long, $d_lat, $d_long, $api_key)
{
    /* 
    All the curls calls can be enclosed in a function, but for this example I'm just going to leave it like this.
    Maybe you have already implemented a function like this in your code.
    */
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

/* 
Once we have the route, we can get the duration of the route in seconds, the distance in meters, and the steps of the route.
*/
// $directions = getRoute($url, "-0.1383219181126606", "-78.47949502180954", "-0.13780693548696138", "-78.48749873306527", "test");
// $duration = $directions['routes'][0]['legs'][0]['duration']['value'];
// $distance = $directions['routes'][0]['legs'][0]['distance']['value'];
// $steps = $directions['routes'][0]['legs'][0]['steps'];


/* 
According to the documentation, the steps are sections of the route, we have initial and final points, and the distance and duration of each section.
However, the final point of one step is going to be the initial point of the next step.
*/
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

// $nodes = get_nodes($steps);

/* 
Once we get the nodes, we can format them to be used in the distance matrix.
Again, for this example the url for the api is hardcoded and for profuction it should be an enviroment variable.
*/
// $distance_matrix_url = 'https://maps.googleapis.com/maps/api/distancematrix/json';


/* 
In the documentations says a node should be lat,long, without any space between then.
And, to send a set of nodes, they have tp be separated by a pipe.
*/
function format_node_to_destination($node)
{
    return $node['lat'] . ',' . $node['lng'];
};

function format_destinations($nodes)
{
    $destinations = [];
    foreach ($nodes as $node) {
        $destinations[] = format_node_to_destination($node);
    }
    return implode('|', $destinations);
}


/* 
Here, the distance matrix between checkpoints and a route node is calculated.
*/
function get_distance_matrix($url, $origins, $destinations, $api_key)
{
    $origins_str = implode('|', $origins);
    $destinations_str = format_destinations($destinations);
    $request_url = $url . '?origins=' . $origins_str . '&destinations=' . $destinations_str . '&key=' . $api_key;

    $curl = curl_init($request_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

/* 
The distance matrix give us the duration in seconds and the distance in meters from the node of the route to the checkpoints.
Note: The documentation says that we should not use multiple origins, so I'm iterating over each checkpoint.
*/
// $distance_matrix = get_distance_matrix($distance_matrix_url, ["-0.13834337572182936,-78.47949502180954"], $nodes, "test");


/* 
checks if the duration of one checkpoint to the route is less than 6 minutes.
*/
function is_between_minutes($distance_matrix, $minutes)
{
    $times_from_route = $distance_matrix['rows'][0]['elements'];
    foreach ($times_from_route as $time) {
        if ($time['duration']['value'] > $minutes * 60) {
            return false;
        }
    };
    return true;
}

/* 
Check if there is a checkpoint between 6 minutes from the route and save them in an array.
*/
function checkpoints_between_minutes($checkpoints, $route, $minutes, $distance_matrix_url, $api_key)
{
    $checkpoints_between_six_minutes = [];
    foreach ($checkpoints as $checkpoint) {
        $distance_matrix = get_distance_matrix($distance_matrix_url, [$checkpoint], $route, $api_key);
        if (is_between_minutes($distance_matrix, $minutes)) {
            $checkpoints_between_six_minutes[] = $checkpoint;
        }
    }
    return $checkpoints_between_six_minutes;
}

// $checkpoints = ["-0.13957718821592419,-78.48562118730912", "-0.1428717384263475,-78.49239528170591"];
// checkpoints_between_six_minutes($checkpoints, $nodes, $distance_matrix_url);


/* I made everything inside functions so you can reuse them when you need */


function get_checkpoints_between_six_mins_to_route(
    $checkpointsList,
    $pLat,
    $pLng,
    $dLat,
    $dLng,
    $distance_matrix_api_url = 'https://maps.googleapis.com/maps/api/distancematrix/json',
    $maps_api_url = 'https://maps.googleapis.com/maps/api/directions/json',
    $api_key
) {
    $main_route = getRoute($maps_api_url, $pLat, $pLng, $dLat, $dLng, $api_key);
    $steps = $main_route['routes'][0]['legs'][0]['steps'];
    $nodes = get_nodes($steps);
    $checkpointsListWithTime = checkpoints_between_minutes($checkpointsList, $nodes, 6, $distance_matrix_api_url, $api_key);
    return $checkpointsListWithTime;
}
