CREATE TABLE `typecho_links` (
  `lid` INTEGER NOT NULL PRIMARY KEY,
  `name` varchar(50) DEFAULT NULL,
  `url` varchar(200) DEFAULT NULL,
  `sort` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `image` varchar(200) DEFAULT NULL,
  `description` varchar(200) DEFAULT NULL,
  `user` varchar(200) DEFAULT NULL,
  `state` int(10) DEFAULT '1',
  `order` int(10) DEFAULT '0'
);
