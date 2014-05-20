CREATE TABLE `typecho_gallery` (
	`gid` int(10) unsigned NOT NULL auto_increment COMMENT 'gallery表主键',
	`thumb` varchar(200) default NULL COMMENT '缩略图',
	`image` varchar(200) default NULL COMMENT '原图',
	`sort` int(10) default '0' COMMENT '相册组',
	`name` varchar(200) default NULL COMMENT '图片名称',
	`description` varchar(200) default NULL COMMENT '图片描述',
	`order` int(10) unsigned default '0' COMMENT '图片排序',
	PRIMARY KEY	(`gid`)
) ENGINE=MYISAM	DEFAULT CHARSET=%charset%;
