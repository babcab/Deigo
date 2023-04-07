DELIMITER $$
CREATE FUNCTION get_distance(pLat FLOAT, pLog FLOAT, dLat FLOAT, dLog FLOAT) 
RETURNS FLOAT 
DETERMINISTIC 
BEGIN 

DECLARE distance FLOAT;
SET
  distance = (
    1.45 * 6371 * 2 * ASIN(
      SQRT(
        POWER(SIN(RADIANS(dLat - pLat) / 2), 2) + COS(RADIANS(pLat)) * COS(RADIANS(dLat)) * POWER(SIN(RADIANS(dLog - pLog) / 2), 2)
      )
    )
  );

RETURN (distance);

END;
$$

DELIMITER $$
CREATE FUNCTION get_nearest_checkpoints(user_lat FLOAT, user_log FLOAT, checkpoints_list JSON, distance FLOAT) 
RETURNS BOOLEAN
DETERMINISTIC
BEGIN 
  DECLARE nearest_checkpoints JSON;
	SELECT JSON_ARRAYAGG(JSON_OBJECT('lat', distance_table.lat, 'log', distance_table.log)) INTO nearest_checkpoints FROM (
		SELECT
			lat,
			log,
			get_distance(user_lat, user_log, lat, log) as distance
		FROM
			JSON_TABLE(
			checkpoints_list,
			'$[*]' COLUMNS (lat FLOAT PATH '$.lat', log FLOAT PATH '$.log')
			) jt
		WHERE 
			get_distance(user_lat, user_log, lat, log) <= distance
		) distance_table;

	IF nearest_checkpoints IS NOT NULL THEN
    	RETURN TRUE;
 	ELSE
    	RETURN FALSE;
  	END IF;
END;
$$


/* 

You can test this code usign this call 

SELECT get_nearest_checkpoints(-0.13819999092199575, 
    -78.47963368378458,
    checkpoints, 1.0)as nearest_checkpoints 
FROM  rides;

I left an example of the data for checkpoints should look in test.json file
 */