CREATE TABLE `shifts` (
  `shift_id` int(11) NOT NULL AUTO_INCREMENT,
  `gatherer` text NOT NULL,
  `location` text NOT NULL,
  `start_time` timestamp NOT NULL,
  `end_time` timestamp NOT NULL,
  `is_bottomliner` tinyint(1) NOT NULL,
  `capacity` int(11) NOT NULL,
  `raw_signatures` int(11),
  `validated_signatures` int(11),
  `notes` text,
  PRIMARY KEY (`shift_id`)
)

INSERT INTO `wp_shifts_2022`(`shift_id`, `gatherer`, `location`, `start_time`, `end_time`, `is_bottomliner`, `capacity`, `raw_signatures`, `validated_signatures`, `notes`) VALUES (1,'test_name','test_location','2022-04-10 18:30:00','2022-04-10 20:30:00',true,2,NULL,NULL,NULL);
