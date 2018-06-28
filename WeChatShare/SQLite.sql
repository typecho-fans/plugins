CREATE TABLE `typecho_wx_share` (
  `wx_id` INTEGER NOT NULL PRIMARY KEY,
  `cid` int(10) default '0',
  `wx_title` varchar(200) default NULL,
  `wx_url` varchar(200) default NULL,
  `wx_image` varchar(200) default NULL,
  `wx_description` varchar(200) default NULL,
);
