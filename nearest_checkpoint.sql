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


-- Version of nearest_checkpoint for MySQL 5.7

DELIMITER $$
CREATE FUNCTION get_nearest_checkpoints(user_lat FLOAT, user_log FLOAT, checkpoints_list TEXT, distance FLOAT) 
RETURNS BOOLEAN
BEGIN 
  DECLARE nearest_checkpoints TEXT;
	SELECT CONCAT('[', GROUP_CONCAT(CONCAT('{"lat":', lat, ',"log":', log, '}')), ']') INTO nearest_checkpoints FROM (
		SELECT
			lat,
			log,
			get_distance(user_lat, user_log, lat, log) as distance
		FROM
			(SELECT 
			  CONVERT(SUBSTRING_INDEX(SUBSTRING_INDEX(t.t.col,',',n.n),',',-1),DECIMAL(10,6)) AS lat,
			  CONVERT(SUBSTRING_INDEX(SUBSTRING_INDEX(t.t.col,',',n.n+1),',',-1),DECIMAL(10,6)) AS log
			FROM 
			  (SELECT @row:=@row+1 as id, JSON_EXTRACT(checkpoints_list, CONCAT('$[', @row-1, ']')) as col
			   FROM (SELECT @row:=0) r
			   WHERE JSON_EXTRACT(checkpoints_list, CONCAT('$[', @row, ']')) IS NOT NULL) t
			CROSS JOIN 
			  (SELECT a.N + b.N * 10 + 1 n FROM 
			    (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a, 
			    (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b 
			   ORDER BY n
			  ) n
		) distance_table
		WHERE 
			get_distance(user_lat, user_log, lat, log) <= distance
		) distance_table;

	IF nearest_checkpoints IS NOT NULL AND nearest_checkpoints != '[]' THEN
    	RETURN TRUE;
 	ELSE
    	RETURN FALSE;
  	END IF;
END$$
DELIMITER ;
