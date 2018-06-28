CREATE TABLE `typecho_wx_share` (
  `wx_id` int(10) unsigned NOT NULL auto_increment COMMENT 'wx_share表主键',
  `cid`	int(10) unsigned default 0 COMMENT '页面的id',
  `wx_title` varchar(200) default NULL COMMENT '微信分享标题',
  `wx_url` varchar(200) default NULL COMMENT '微信分享链接',
  `wx_image` varchar(200) default NULL COMMENT '微信分享小图标',
  `wx_description` varchar(200) default NULL COMMENT '微信分享摘要',
  PRIMARY KEY  (`wx_id`)
) ENGINE=MYISAM  DEFAULT CHARSET=%charset%;
