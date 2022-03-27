CREATE TABLE `shifts` (
  `shift_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11),
  `gatherer` text NOT NULL,
  `contact` text NOT NULL,
  `location_id` int(11) NOT NULL,
  `start_time` timestamp NOT NULL,
  `end_time` timestamp NOT NULL,
  `capacity` int(11) NOT NULL,
  `cancelled` tinyint(1) NOT NULL,
  `raw_signatures` int(11),
  `validated_signatures` int(11),
  `notes` text,
  PRIMARY KEY (`shift_id`),
  FOREIGN KEY (`location_id`) REFERENCES locations(`location_id`)
);

INSERT INTO `shifts`(`shift_id`, `parent_id`, `gatherer`, `contact`, `location_id`, `start_time`, `end_time`, `capacity`, `cancelled`, `raw_signatures`, `validated_signatures`, `notes`) VALUES (1,NULL,'test_name',`test_phone',1,'2022-04-10 18:30:00','2022-04-10 20:30:00',2,false,NULL,NULL,NULL);

CREATE TABLE `locations` (
  `location_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `type` text NOT NULL,
  `quality` int(11),
  `capacity` int(11),
  `notes` text,
  PRIMARY KEY (`location_id`)
);

INSERT INTO `locations`(`location_id`, `name`, `type`, `quality`, `capacity`, `notes`) VALUES (1,"Local Grocery","Grocery Store",3,2"Busy location");