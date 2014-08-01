-- --------------------------------------------------------

--
-- 创建 contribute 表
--

CREATE TABLE `typecho_contribute` (
  `cid` int(10) unsigned NOT NULL auto_increment,
  `title` varchar(200) default NULL,
  `slug` varchar(200) default NULL,
  `created` int(10) unsigned default '0',
  `modified` int(10) unsigned default '0',
  `text` text,
  `order` int(10) unsigned default '0',
  `authorId` int(10) unsigned default '0',
  `template` varchar(32) default NULL,
  `type` varchar(16) default 'post',
  `status` varchar(16) default 'publish',
  `password` varchar(32) default NULL,
  `commentsNum` int(10) unsigned default '0',
  `allowComment` char(1) default '0',
  `allowPing` char(1) default '0',
  `allowFeed` char(1) default '0',
  `parent` int(10) unsigned default '0',
  `author` varchar(200) default NULL,
  `category` varchar(200) default NULL,
  `tags` varchar(200) default NULL,
  PRIMARY KEY  (`cid`),
  KEY `created` (`created`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
