ALTER TABLE  `typecho_comments` ADD  `post_id` BIGINT( 64 ) NOT NULL DEFAULT  '0' AFTER  `cid`;

CREATE TABLE `typecho_duoshuo` (
  `name` varchar(32) NOT NULL,
  `value` varchar(200) NOT NULL DEFAULT '',
  `info` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`name`,`value`)
) ENGINE=MyISAM DEFAULT CHARSET=%charset%;

INSERT INTO `typecho_duoshuo` (`name`, `value`, `info`) VALUES
('short_name', '', '多说 short_name'),
('secret', '', '多说密匙'),
('synchronized', '0', '是否已同步'),
('sync_lock', '0', '同步锁定'),
('last_log_id', '0', '最后同步log_id'),
('user_id', '0', '管理员对应多说ID');

INSERT INTO `typecho_options` (`name`, `user`, `value`) VALUES ('duoshuo_theme', 0, 'default');