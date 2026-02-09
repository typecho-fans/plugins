ALTER TABLE `typecho_links`
/*
ADD `sort` varchar(50) DEFAULT NULL COMMENT 'links分类' AFTER `url`, 
ADD `image` varchar(200) DEFAULT NULL COMMENT 'links图片' AFTER `sort`,
ADD `user` varchar(200) DEFAULT NULL COMMENT '自定义' AFTER `description`,
*/
ADD `email` varchar(50) DEFAULT NULL COMMENT 'links邮箱' AFTER `sort`,
ADD `state` int(10) DEFAULT '1' COMMENT 'links状态' AFTER `user`;
