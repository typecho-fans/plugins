CREATE TABLE `typecho_gallery` (
	`gid` INTEGER NOT NULL PRIMARY KEY,
	`thumb` varchar(200) default NULL,
	`image` varchar(200) default NULL,
	`sort` int(10) default '0',
	`name` varchar(200) default NULL,
	`description` varchar(200) default NULL,
	`order` int(10) default '0'
);
