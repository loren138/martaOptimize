<?php

for ($i = 1; $i < 32; $i++) {
echo "TRUNCATE TABLE `chunks`;
LOAD DATA INFILE '/home/you/data/chunks/train".$i.".csv'
INTO table chunks COLUMNS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' ESCAPED BY '\"' LINES TERMINATED BY '".'\n'."'
IGNORE 1 LINES;
DELETE FROM `chunks` WHERE use_type NOT IN (1, 9, 10, 11, 12) OR transit_mode <> 2;

INSERT INTO chunksAll2
SELECT
  o2.day as day,
  entry_type,
  entry_time,
  exit_time,
  serial_num,
  o2.use_type as exit_type,
  entry_station,
  o2.station_id as exit_station,
  TIME_TO_SEC(TIMEDIFF(exit_time,entry_time)) as diff  FROM (
SELECT i.use_type as entry_type, i.time as entry_time, min(o.time) as exit_time, i.station_id as entry_station, i.serial_number as serial_num
FROM `chunks` i
INNER JOIN
    (SELECT * FROM `chunks` WHERE use_type in (10,11)) o
ON (i.serial_number = o.serial_number AND o.time >= i.time)
 WHERE i.use_type in (1,9,12)
 GROUP BY i.use_type, i.time, i.station_id, i.serial_number
) i2 INNER JOIN `chunks`o2 ON (o2.time = exit_time AND o2.serial_number = serial_num AND o2.use_Type IN (10,11))
ORDER BY serial_num, entry_time;

";

}

