public function createRide($driverId, $pickupLocation, $pLat, $pLng, $dropLocation, $dLat, $dLng, $date, $time, $numberOfSeats, $pricePerSeat, $apiKey){

//TODO - Instruction for Diego
//call google maps with pickup and drop location (using Lat, Lng) and get back ETA
//Calculate nearest checkpoints from that route (take a list of checkpoints using Lat, Lng given below - dummy only for now) checkpoints should be 6 mins detour max from the route)
//each checkpoints calculation should be done individually

//Part of code
$checkpointsList = "";
$checkpoints_between_six_minutes = get_checkpoints_between_six_mins_to_route($checkpointsList, $pLat, $pLng, $dLat, $dLng, $apiKey);


$timestamp = $this->getCurrentTimeStamp();
$stmt = $this->con->prepare("INSERT INTO rides (driverId, pickupLocation, pLat, pLng, dropLocation, dLat, dLng, date, time, numberOfSeats, pricePerSeat, apiKey, timestamp, ipAddress, checkpoints, numberOfSeatsAvailable) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssssssssssss", $driverId, $pickupLocation, $pLat, $pLng, $dropLocation, $dLat, $dLng, $date, $time, $numberOfSeats, $pricePerSeat, $apiKey, $timestamp, $this->getClientIP(), $checkpointsListWithTime, $numberOfSeats);
$result = $stmt->execute();
$rideId = $stmt->insert_id;
$stmt->close();
if(!$result){
return false;
} else {
return $rideId;
}
}


public function searchRide($userId, $pickupLocation, $pLat, $pLng, $dropLocation, $dLat, $dLng, $date, $time, $maxPricePerSeat, $timeDifference, $apiKey){
$this->addRideSearchLog($userId, $pickupLocation, $pLat, $pLng, $dropLocation, $dLat, $dLng, $date, $time, $maxPricePerSeat, $apiKey);

//TODO
//write the function for SQL that takes a lat and lng and then calculates the nearest checkpoints using the formula given below; nearest checkpoints should be upto 1 mile in distance
//Expecting a SQL function which I will execute on my server and save it

$stmt = $this->con->prepare("SELECT rides.rideId, rides.driverId, rides.pickupLocation, rides.pLat, rides.pLng, rides.dropLocation, rides.dLat, rides.dLng, rides.date, rides.time, rides.numberOfSeats, rides.pricePerSeat, rides.timestamp, users.userId, users.firstName, users.lastName, users.college, users.program, users.studentType, users.graduationYear, users.userPicture, users.carMake, users.carModel, users.plateNumber FROM rides, users INNER JOIN users on users.userId = rides.driverId WHERE rides.status = ? AND rides.numberOfSeats >= 1 AND rides.date = ? AND pricePerSeat <= ? AND myFunc (lat, lng of user given and we will calculate nearest checkpoints of max 1 mile distance)"); $stmt->bind_param("sssssss", $this->rideStatusCreated, $date, $maxPricePerSeat, $pLat, $pLng, $time, $timeDifference);
    $stmt->execute();
    $result = $stmt->get_result();
    $allRides = array();
    while($token = $result->fetch_assoc()){
    array_push($allRides, $token);
    }
    return $allRides;
    }


    public function calculateDistance($lat1, $long1, $lat2, $long2){
    $dLat = ($lat2 - $lat1) *
    M_PI / 180.0;
    $dLon = ($long2 - $long1) *
    M_PI / 180.0;

    // convert to radians
    $lat1 = ($lat1) * M_PI / 180.0;
    $lat2 = ($lat2) * M_PI / 180.0;

    // apply formulae
    $a = pow(sin($dLat / 2), 2) +
    pow(sin($dLon / 2), 2) *
    cos($lat1) * cos($lat2);
    $rad = 6371;
    $c = 2 * asin(sqrt($a));
    $dis = $rad * $c;
    return (1.45 * $dis);
    }