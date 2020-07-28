CREATE TABLE IF NOT EXISTS `{prefix}comment_push` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `service` text COMMENT '服务',
  `object` text COMMENT '对象',
  `context` text COMMENT '内容',
  `result` text COMMENT '结果',
  `error` text COMMENT '错误信息',
  `time` bigint(20) DEFAULT NULL COMMENT '时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8;