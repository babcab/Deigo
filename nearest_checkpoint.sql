CREATE FUNCTION find_nearest_checkpoints(lat FLOAT, lng FLOAT) RETURNS TABLE (
    chk_id INT,
    chk_lat FLOAT,
    chk_lng FLOAT,
    distance FLOAT
) AS $$ BEGIN RETURN QUERY
SELECT
    checkpoint_id,
    checkpoint_lat,
    checkpoint_lng,
    (
        1.45 * 6371 * 2 * ASIN(
            SQRT(
                POWER(SIN(RADIANS(checkpoint_lat - lat) / 2), 2) + COS(RADIANS(lat)) * COS(RADIANS(checkpoint_lat)) * POWER(SIN(RADIANS(checkpoint_lng - lng) / 2), 2)
            )
        )
    ) AS distance
FROM
    checkpoints
WHERE
    (
        1.45 * 6371 * 2 * ASIN(
            SQRT(
                POWER(SIN(RADIANS(checkpoint_lat - lat) / 2), 2) + COS(RADIANS(lat)) * COS(RADIANS(checkpoint_lat)) * POWER(SIN(RADIANS(checkpoint_lng - lng) / 2), 2)
            )
        )
    ) <= 1.0
ORDER BY
    distance ASC;

END;

$$ LANGUAGE plpgsql