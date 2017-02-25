TRUNCATE TABLE `chunks`;
LOAD DATA INFILE '/home/you/data/chunks/train2.csv'
INTO table chunks COLUMNS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' ESCAPED BY '"' LINES TERMINATED BY '\n'
IGNORE 1 LINES;
DELETE FROM `chunks` WHERE use_type NOT IN (1, 9, 10, 11, 12) OR transit_mode <> 2;

INSERT INTO chunksAll
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
WHERE TIME_TO_SEC(TIMEDIFF(exit_time,entry_time)) > 60 AND TIME_TO_SEC(TIMEDIFF(exit_time,entry_time)) < 7200
ORDER BY serial_num, entry_time;

CREATE TABLE `chunksAll2` (
  `day` date NOT NULL,
  `entry_type` tinyint(3) unsigned NOT NULL,
  `entry_time` time NOT NULL,
  `exit_time` time NOT NULL,
  `serial_number` varchar(32) NOT NULL,
  `exit_type` tinyint(3) unsigned NOT NULL,
  `entry_station` tinyint(3) unsigned NOT NULL,
  `exit_station` tinyint(3) unsigned NOT NULL,
  `seconds` smallint unsigned not null,
  KEY `entry_time` (`entry_time`),
  KEY `day` (`day`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

SELECT
    *
FROM
    chunksAll2
INTO OUTFILE '/home/you/data/efcProcessed2.csv'
FIELDS ENCLOSED BY '"'
TERMINATED BY ';'
ESCAPED BY '"'
LINES TERMINATED BY '\n';