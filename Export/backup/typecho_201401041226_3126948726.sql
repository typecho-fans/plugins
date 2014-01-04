-- Typecho Backup SQL
-- 程序版本: 0.9/13.12.20
--
-- 备份工具: Export
-- 插件作者: ShingChi
-- 主页链接: http://lcz.me
-- 生成日期: 2014 年 01 月 04 日

-- --------------------------------------------------------

--
-- 表的结构 `typecho_comments`
--

CREATE TABLE IF NOT EXISTS `typecho_comments` (
  `coid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(10) unsigned DEFAULT '0',
  `created` int(10) unsigned DEFAULT '0',
  `author` varchar(200) DEFAULT NULL,
  `authorId` int(10) unsigned DEFAULT '0',
  `ownerId` int(10) unsigned DEFAULT '0',
  `mail` varchar(200) DEFAULT NULL,
  `url` varchar(200) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `agent` varchar(200) DEFAULT NULL,
  `text` text,
  `type` varchar(16) DEFAULT 'comment',
  `status` varchar(16) DEFAULT 'approved',
  `parent` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`coid`),
  KEY `cid` (`cid`),
  KEY `created` (`created`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `typecho_contents`
--

CREATE TABLE IF NOT EXISTS `typecho_contents` (
  `cid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) DEFAULT NULL,
  `slug` varchar(200) DEFAULT NULL,
  `created` int(10) unsigned DEFAULT '0',
  `modified` int(10) unsigned DEFAULT '0',
  `text` text,
  `order` int(10) unsigned DEFAULT '0',
  `authorId` int(10) unsigned DEFAULT '0',
  `template` varchar(32) DEFAULT NULL,
  `type` varchar(16) DEFAULT 'post',
  `status` varchar(16) DEFAULT 'publish',
  `password` varchar(32) DEFAULT NULL,
  `commentsNum` int(10) unsigned DEFAULT '0',
  `allowComment` char(1) DEFAULT '0',
  `allowPing` char(1) DEFAULT '0',
  `allowFeed` char(1) DEFAULT '0',
  `parent` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`cid`),
  UNIQUE KEY `slug` (`slug`),
  KEY `created` (`created`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `typecho_contents`
--

INSERT INTO typecho_contents (`cid`, `title`, `slug`, `created`, `modified`, `text`, `order`, `authorId`, `template`, `type`, `status`, `password`, `commentsNum`, `allowComment`, `allowPing`, `allowFeed`, `parent`) VALUES
('1', '欢迎使用 Typecho', 'start', '1314205926', '1326016081', '<!--markdown-->如果您看到这篇文章,表示您的 blog 已经安装成功.', '0', '1', NULL, 'post', 'publish', NULL, '1', '0', '0', '1', '0'),
('2', '关于', 'about', '1314205920', '1385751306', '<!--markdown-->徒然草：排忧遣闷录。  \r\n博主：一个不学无术的人。\r\n\r\n## 岁月如歌 ##\r\n\r\n2012-12-18 迁到 BAE\r\n\r\n## 天涯若比邻 ##\r\n\r\n### 老朋友 ###\r\n\r\n - [王兄][2]\r\n - [清和][3]\r\n - [老四][4]\r\n - [追风][5]\r\n - [玲燕][6]\r\n - [菜包][7]\r\n\r\n### Typecho 基友 ###\r\n\r\n - [竹马][8]\r\n - [不烦恼][9]\r\n - [暮春][10]\r\n - [兜兜][11]\r\n - [CC妹][12]\r\n - [阳光妹][13]\r\n - [舞哥][14]\r\n - [飞老师][15]\r\n - [李智][16]\r\n - [Gryu][17]\r\n - [草屋茶座][18]\r\n - [我本奈何][19]\r\n - [Jason][20]\r\n - [冰剑][21]\r\n - [浮云阁][22]\r\n - [JR Blog][23]\r\n - [掌柜][24]\r\n - [熠想天开][25]\r\n - [True][26]\r\n - [羽飞][27]\r\n - [公子][31]\r\n\r\n### 其他博友 ###\r\n\r\n - [枕草子][28]\r\n - [湖心影][29]\r\n - [惊蛰][30]\r\n\r\n\r\n  [1]: http://lifesinger.github.io\r\n  [2]: http://yoxp.com\r\n  [3]: http://www.sumu.name\r\n  [4]: http://tdxymm.diandian.com\r\n  [5]: http://4jax.net\r\n  [6]: http://ilyn.me\r\n  [7]: http://www.imcaibao.com\r\n  [8]: http://lifecho.org\r\n  [9]: http://bufannao.com\r\n  [10]: http://www.luili.net\r\n  [11]: http://doudou.me\r\n  [12]: http://mui.me\r\n  [13]: http://ysido.com\r\n  [14]: http://weburls.net\r\n  [15]: http://defe.me\r\n  [16]: http://yijile.com\r\n  [17]: http://gryu.net\r\n  [18]: http://blog.immin.name\r\n  [19]: http://funme.net\r\n  [20]: http://www.i171.com\r\n  [21]: http://www.binjoo.net\r\n  [22]: http://deefhi.com\r\n  [23]: http://jrblog.org\r\n  [24]: http://eoo.hk\r\n  [25]: http://blog.fengyiyi.com\r\n  [26]: http://true-me.com\r\n  [27]: http://www.byends.com\r\n  [28]: http://www.sutog.com\r\n  [29]: http://www.xinhubian.com\r\n  [30]: http://www.jingzhe.org\r\n  [31]: http://imnerd.org', '2', '1', NULL, 'page', 'publish', NULL, '0', '0', '0', '1', '0'),
('3', '性灵所钟，泉石澈韵', 'pure-soul', '1326630180', '1384621688', '<!--markdown-->昨晚在 [老四](http://tdxymm.diandian.com \"长绵羊的翅膀\") 那觅得一书——《千江有水千江月》，为台湾省女作家萧丽红所著，曾获联合报长篇小说奖。此层无论，仅其书名用的是我喜欢的一句偈语，就决心看上一看。\r\n\r\n今天差不多花了一天的时间去看完这本书，看书的时候外面正下着绸缪细雨，中午边吃泡面边看书，耳边还响着音乐，所谓晴耕雨读，应亦不过如此情致了。\r\n\r\n![蓝天碧海][1]\r\n\r\n这本书给我颇多感触的是作者在其间营造出来的两个世界，一个是由主人公贞观的视角呈现出来的现实世界，另一个则是贞观的内心世界。这两者都是那么美好，那么清雅。\r\n\r\n布袋镇是一个经历过战乱的沧桑小镇，民风淳朴，生活平淡。不过一切在贞观眼中呈现出来却是另一番天地，那么诗意，让人眷恋。贞观带客人去鱼坳的时候，月光下畦畦相连，“沿岸走来，贞观倒是一颗心都在这水池里：这鱼塘月色；一水一月，千水即千月——世上原来有这等光景……再看远方、近处，各各渔家草寮挂出来的灯火隐约衔散在凉冽的夜空。”这池月渔火，点缀天地，如此壮观、如此令人动容。致使贞观想象自己是“虎尾溪女侠，鲲身海儿女，有如武侠天地里的大师妹，身后一口光灿好剑，背负它，披星戴月江湖行。”书中类似这样的描写比比皆是，难以俱言，当然都是通过贞观的视角呈现出来。\r\n\r\n除了故乡的风景外，在贞观眼中，故乡的人、事、物，甚至一切都是那么可爱，那么醉人心弦的。当然，这些美好的描述都与贞观那一颗细腻、明朗、质朴的赤子之心是分不开的。\r\n\r\n在贞观的心灵里，情是世间最高的准则。“这天地之间，真正能留存下来的，也只有精神一物”，无论景物再变迁，人世多浮荡，惟情是永恒的。这里的情非爱情，而是情感、感情。在贞观这里，所有的事情都能用情字来解释，无论好与坏。阿启伯偷瓜，被外公和贞观发现而不捅破，这是人情、世情；被日本军调往南洋作战的大舅停妻又娶，这是恩情，而大妗奉二老几十年默默无闻，后又入庵为姑，是爱情亦是亲情。在贞观看来，所有的人世浮沉，都只是情的层层叠叠而已，只要有情在，世间的折磨与困厄都“成了生身为人的另一种着迷”。这需要怎样的胸怀及心灵才能做到如此透彻啊。\r\n\r\n我想，“性灵所钟，泉石澈韵”是对贞观这个有爱的邻家女孩最完美的形容。而贞观的性灵正是受到中国的传统文化和美好的人伦社会的熏陶而形成。\r\n\r\n贞观自小就跟着外公习读《千字文》、《妇女家训》、《劝世文》、《三字经》等等，“贞观是每读一遍，便觉得自己再不同于前，是身与心，都在这浅显易解的文字里，一次又一次的被涤荡、洗洁……”。另则，其外婆等一些老一辈的人们，常给她们讲一些“鬼头飞刀苏宝同，移山倒海樊梨花”等故事。这倒让我想起小时候，那时常去听一些村里的老人讲故事，或者是听奶奶讲一些忠义、善恶啊什么的故事。我们这边的戏曲也常是编演一些这样的故事。\r\n\r\n布袋镇是一个非常重伦常、礼教的地方，叔伯姨婶分得很清楚，不可轻易逾越。里面有几个细节给我印象是很深刻的，比如大妗要入庵的时候，贞观外婆说了句：“你有什么事情，不与我说了！我知道你也是厌倦我老人！”“话未说完，她大妗早咚的一声，跪了下去”。单这跪字就可见这一家子的家教。其次，闾里中人很计较礼尚往来，中国旧俗是有来有往，送东西给别人，是绝对没有空盘子回来的。贞观每次去送东西，端着盘子回来，上头竟都盛有半盘面的白米。“小小的行事中，照样看出来我们是有礼、知礼的民族！礼无分巨细、大小、是民间、市井，识字、不识都知晓怎样叫做礼！”\r\n\r\n我想贞观正是在一个这样的环境下成长起来的，才能具有一个如此通透的性灵。使得她看这世间的人、事、物，都透着一股宽厚与明朗。当真惹人怜爱。\r\n\r\n这本书包含甚多，人情风物、生死离别、纯真爱情、民族血缘、妇女形象等等，都别具一格，加上作者以其精致的语言文字和深厚的古文功底，缓陈慢铺，让人读来如享受一般。但结局略显仓促，闭卷冥思稍存遗憾，当然作者另有深意的话，就不得而知了。\r\n\r\n\r\n  [1]: http://localhost/gite/usr/uploads/2013/11/695816605.jpg', '0', '1', NULL, 'post', 'publish', NULL, '0', '0', '0', '1', '0'),
('4', '夜空中最亮的星', 'the-brightest-star', '1383897008', '1383897008', '<!--markdown-->夜空中最亮的星，能否听清  \r\n那仰望的人心底的孤独和叹息\r\n\r\n夜空中最亮的星，能否记起  \r\n曾与我同行，消失在风里的身影\r\n\r\n我祈祷拥有一颗透明的心灵和会流泪的眼睛  \r\n给我再去相信的勇气，越过谎言去拥抱你\r\n\r\n每当我找不到存在的意义  \r\n每当我迷失在黑夜里  \r\n夜空中最亮的星，请指引我靠近你\r\n\r\n夜空中最亮的星，是否知道  \r\n曾与我同行的身影，如今在哪里\r\n\r\n夜空中最亮的星，是否在意  \r\n是等太阳升起，还是意外先来临\r\n\r\n我宁愿所有痛苦都留在心里，也不愿忘记你的眼睛  \r\n给我再去相信的勇气，越过谎言去拥抱你\r\n\r\n每当我找不到存在的意义  \r\n每当我迷失在黑夜里  \r\n夜空中最亮的星，请照亮我前行\r\n\r\n我祈祷拥有一颗透明的心灵和会流泪的眼睛  \r\n给我再去相信的勇气，越过谎言去拥抱你\r\n\r\n每当我找不到存在的意义  \r\n每当我迷失在黑夜里  \r\n夜空中最亮的星，请照亮我前行\r\n\r\n夜空中最亮的星，能否听清  \r\n那仰望的人心底的孤独和叹息\r\n\r\n<embed src=\"http://www.xiami.com/widget/0_1770201852/singlePlayer.swf\" type=\"application/x-shockwave-flash\" width=\"257\" height=\"33\" wmode=\"transparent\"></embed>', '0', '1', NULL, 'post', 'publish', NULL, '0', '0', '0', '1', '0'),
('5', 'test.jpg', 'test-jpg', '1384621660', '1384621660', 'a:5:{s:4:\"name\";s:8:\"test.jpg\";s:4:\"path\";s:34:\"/usr/uploads/2013/11/695816605.jpg\";s:4:\"size\";i:66816;s:4:\"type\";s:3:\"jpg\";s:4:\"mime\";s:10:\"image/jpeg\";}', '1', '1', NULL, 'attachment', 'publish', NULL, '0', '1', '0', '1', '3'),
('6', '生辰八字', '6', '1385751660', '1387199478', '<!--markdown-->   | (公历) | (农历) | 八字 | 五行 | 纳音   | 综述                                                               |\r\n-- | ------ | ------ | -------------------- | ------------------------------------------------------------------ |\r\n年 | 2013年 | 癸巳年 | 癸巳 | 水火 | 常流水 | 此命五行水旺缺土；日主天干为金，生于秋季；必须有水助，但忌木太多。<br />（取名时可根据以上情况进行相应纠偏补缺）\r\n月 | 12月   | 十一月 | 甲子 | 木水 | 海中金 |                                                                    |\r\n日 | 11日   | 初九   | 辛亥 | 金水 | 钗钏金 |                                                                    |\r\n时 | 6点    | 卯时   | 辛卯 | 金木 | 松柏木 |                                                                    |', '0', '1', NULL, 'post', 'publish', NULL, '0', '0', '0', '1', '0'),
('7', '自定义首页', 'home', '1385754000', '1385755202', '<!--markdown-->', '1', '1', NULL, 'page', 'hidden', NULL, '0', '0', '0', '1', '0'),
('8', '哈哈', 'the-brightest-star-1', '1386035726', '1386035726', '<!--markdown-->测试啊', '0', '1', NULL, 'post', 'publish', NULL, '0', '0', '0', '1', '0'),
('9', '你好', '9', '1386045060', '1386045060', '<!--markdown-->你好', '0', '1', NULL, 'post', 'publish', NULL, '0', '0', '0', '1', '0'),
('10', '明天', '10', '1386045109', '1386045109', '<!--markdown-->明天', '0', '1', NULL, 'post', 'publish', NULL, '0', '0', '0', '1', '0'),
('11', '今天去哪', '11', '1386046075', '1386046075', '<!--markdown-->今天去哪', '0', '1', NULL, 'post', 'publish', NULL, '0', '0', '0', '1', '0'),
('12', '天空很蓝', '12', '1386046191', '1386046191', '<!--markdown-->天空很蓝', '0', '1', NULL, 'post', 'publish', NULL, '0', '0', '0', '1', '0'),
('13', '我们走吧', '13', '1386046620', '1386046746', '<!--markdown-->我们走吧', '0', '1', NULL, 'post', 'publish', NULL, '0', '0', '0', '1', '0'),
('14', '下雨天', '15', '1386047640', '1386047762', '<!--markdown-->下雨天', '0', '1', NULL, 'post', 'publish', NULL, '0', '0', '0', '1', '0'),
('16', '天空', '16', '1386047760', '1386047860', '<!--markdown-->天空', '0', '1', NULL, 'post', 'publish', NULL, '0', '0', '0', '1', '0'),
('17', '当初不应该', '17', '1386050110', '1386050110', '<!--markdown-->当初不应该', '0', '1', NULL, 'post', 'publish', NULL, '0', '0', '0', '1', '0'),
('18', '坚强的理由', 'jianqiang-liyou', '1386057060', '1388633117', '<!--markdown--><audio type=\"audio/mp3\" src=\"http://m5.file.xiami.com/477/100477/1072327883/1771978165_10344374_l.mp3?auth_key=e458456e0dfebc68ecc4ebbdfffd54d7-1388661906-0-null\" preload=\"auto\" loop=\"loop\" /></audio>\r\n\r\n我想知道我们是不是醉了\r\n我想知道我们是不是老了\r\n我想知道天空为何是蓝色的\r\n我想知道理想是什么\r\n我想知道他们是不是笑我\r\n我想知道你脸上的哀愁\r\n我想知道明天是不是最后\r\n我想知道我不愿做小丑\r\n那些孤单的夜 路上简单的人们\r\n告诉我 我被抛弃的理由\r\n那些平凡的欲望 每日沉默悲伤\r\n告诉我 让我坚强的理由\r\n\r\n我想知道他们是不是笑我\r\n我想知道你脸上的哀愁\r\n我想知道明天是不是最后\r\n我想知道我不愿做小丑\r\n那些孤单的夜 路上简单的人们\r\n告诉我 我被抛弃的理由\r\n那些平凡的欲望 每日沉默悲伤\r\n告诉我 让我坚强的理由\r\n那些孤单的夜 路上简单的人们\r\n告诉我 我被抛弃的理由\r\n那些平凡的欲望 每日沉默悲伤\r\n告诉我 让我坚强的理由\r\n我想知道我们是不是醉了\r\n我想知道理想是什么', '0', '1', NULL, 'post', 'publish', NULL, '0', '0', '0', '1', '0'),
('19', 'The One - Aina Brown', 'the-one-aina-brown', '1386057120', '1388366858', '<!--markdown--><audio src=\"http://m5.file.xiami.com/204/63146204/1663146310/1771740906_4256341_l.mp3?auth_key=95a5964abd2c57d75e065c5aa84e2547-1388395639-0-null\" preload=\"auto\" /></audio>\r\n\r\n<audio src=\"http://localhost/gite/usr/plugins/AudioPlayer/The-One.mp3\" preload=\"auto\" /></audio>\r\n \r\nSometimes the world can cut you down\r\nAnd break your heart in two\r\nI think you need somebody now\r\nTo fix what’s hurting you\r\nLet’s start a show\r\nIf you agree\r\nI will be the one\r\nStanding aside with me\r\nI will be the one （And I would, and I would ）\r\nDon\'t stop the girl just want an empty end\r\nI will be the one （And I would, and I would ）\r\nSwitch my time the ever back within\r\nI will be the one\r\nI will be the one the one the one\r\nA million times again\r\nGive me your hands\r\nTell me your dreams\r\nI love to hear you speak\r\nAnd no secrets here\r\nNothing to hide\r\nDocu will make you weak\r\nLet’s start a show\r\nIf you agree\r\nI will be the one\r\nStanding aside with me\r\nI will be the one （And I would ,and I would ）\r\nDon\'t stop the girl just want an empty end\r\nI will be the one （And I would,and I would ）\r\nSwitch my time the ever back within\r\nI will be the one\r\nI will be the one the one the one\r\nA million times again\r\nDon\'t waste more time\r\nWhen we both know you fail the pool\r\nYour world can change into some kind of wonderful\r\n \r\nAnd I would ,and I would\r\n \r\nI will be the one\r\nAnd I would ,and I would\r\nSwitch my time the ever back within\r\nI will be the one\r\nI will be the one the one the one\r\nA million times again\r\n \r\nAnd I would,and I would\r\n \r\nA million times again', '0', '1', NULL, 'post', 'publish', NULL, '0', '0', '0', '1', '0'),
('20', 'Angel', 'angel', '1386058440', '1388197864', '<!--markdown--><audio type=\"audio/mp3\" src=\"http://localhost/gite/usr/plugins/AudioPlayer/Angel.mp3\" preload=\"auto\" /></audio>\r\n\r\nSpend all your time waiting for that second chance用全部的时间等待第二次机会\r\nFor a break that would make it OK 因为逃避能使一切更好\r\nThere’s always some reasons to feel not good enough 总是有理由说感觉不够好\r\nAnd it’s hard at the end of the day在一日将尽之时觉得难过\r\nI need some distraction or a beautiful release我需要散散心，或是一个美丽的解脱\r\nMemories seep from my veins回忆自我的血管渗出\r\nLet me be empty and weightless让我体内空无一物，了无牵挂\r\nAnd maybe I’ll find some peace tonight也许今晚我可以得到一些平静\r\n\r\nIn the arms of the angel在天使的怀里\r\nFly away from here飞离此地\r\nFrom this dark, cold hotel room远离黑暗、阴冷的旅馆房间\r\nAnd the endlessness that you fear和你无穷的惧怕\r\nYou are pulled from the wreckage of your silent reverie你在无声的幻梦残骸中被拉起\r\nYou are in the arms of the angel在天使的怀里\r\nMay you find some comfort here 愿你能得到安慰\r\n\r\nSo tired of the straight line厌倦了走直线\r\nAnd everywhere you turn你转弯的每一个地方\r\nThere’re vultures and thieves at your back 总有兀鹰和小偷跟在身后\r\nThe storm keeps on twisting暴风雨仍肆虐不止\r\nYou keep on building the lies你仍在建构谎言\r\nThat you make up for all that you lack以弥补你所欠缺的\r\nIt don’t make no difference, escape one last time但那于事无补，再逃避一次\r\nIt’s easier to believe会使人更容易相信\r\nIn this sweet madness, oh this glorious sadness在这甜蜜的疯狂、光荣的忧伤里\r\nThat brings me to my knees使我颔首屈膝\r\n\r\nIn the arms of the angel在天使的怀里\r\nFly away from here飞离此地\r\nFrom this dark, cold hotel room 远离黑暗、阴冷的旅馆房间\r\nAnd the endlessness that you fear和你无穷的惧怕\r\nYou are pulled from the wreckage of your silent reverie你在无声的幻梦残骸中被拉起\r\nYou are in the arms of the angel 在天使的怀里\r\nMay you find some comfort here愿你能得到安慰\r\nYou are in the arms of the angel在天使的怀里\r\nMay you find some comfort here愿你能得到安慰', '0', '1', NULL, 'post', 'publish', NULL, '0', '0', '0', '1', '0'),
('21', '五行八字', 'wuxing-bazi', '1386058500', '1387682588', '<!--markdown-->   | (公历) | (农历) | 八字 | 五行 | 纳音   | 综述                                                               |\r\n-- | ------ | ------ | -------------------- | ------------------------------------------------------------------ |\r\n年 | 2013年 | 癸巳年 | 癸巳 | 水火 | 常流水 | 此命五行水旺缺土；日主天干为金，生于秋季；必须有水助，但忌木太多。<br />（取名时可根据以上情况进行相应纠偏补缺）\r\n月 | 12月   | 十一月 | 甲子 | 木水 | 海中金 |                                                                    |\r\n日 | 11日   | 初九   | 辛亥 | 金水 | 钗钏金 |                                                                    |\r\n时 | 6点    | 卯时   | 辛卯 | 金木 | 松柏木 |                                                                    |\r\n\r\n<table>\r\n<thead>\r\n<tr>\r\n  <th></th>\r\n  <th>(公历)</th>\r\n  <th>(农历)</th>\r\n  <th>八字</th>\r\n  <th>五行</th>\r\n  <th>纳音</th>\r\n  <th>综述</th>\r\n</tr>\r\n</thead>\r\n<tbody>\r\n<tr>\r\n  <td>年</td>\r\n  <td>2013年</td>\r\n  <td>癸巳年</td>\r\n  <td>癸巳</td>\r\n  <td>水火</td>\r\n  <td>常流水</td>\r\n  <td rowspan=\"4\">此命五行水旺缺土；日主天干为金，生于秋季；必须有水助，但忌木太多。<br>（取名时可根据以上情况进行相应纠偏补缺）</td>\r\n</tr>\r\n<tr>\r\n  <td>月</td>\r\n  <td>12月</td>\r\n  <td>十一月</td>\r\n  <td>甲子</td>\r\n  <td>木水</td>\r\n  <td>海中金</td>\r\n</tr>\r\n<tr>\r\n  <td>日</td>\r\n  <td>11日</td>\r\n  <td>初九</td>\r\n  <td>辛亥</td>\r\n  <td>金水</td>\r\n  <td>钗钏金</td>\r\n</tr>\r\n<tr>\r\n  <td>时</td>\r\n  <td>6点</td>\r\n  <td>卯时</td>\r\n  <td>辛卯</td>\r\n  <td>金木</td>\r\n  <td>松柏木</td>\r\n</tr>\r\n</tbody>\r\n</table>\r\n\r\n林立彧 林立惇 林立蹊 林立峬 林立墉 林立韫 林立宴 林立晏 林立埔 林立迂 林立祐 林立硕 林立圻\r\n\r\n林立懿 林立羽 林立守 林立敦 林立畴 林立堤 林立巍 林立廷', '0', '1', NULL, 'post', 'publish', NULL, '0', '0', '0', '1', '0');

-- --------------------------------------------------------

--
-- 表的结构 `typecho_fields`
--

CREATE TABLE IF NOT EXISTS `typecho_fields` (
  `cid` int(10) unsigned NOT NULL,
  `name` varchar(200) NOT NULL,
  `type` varchar(8) DEFAULT 'str',
  `str_value` text,
  `int_value` int(10) DEFAULT '0',
  `float_value` float DEFAULT '0',
  PRIMARY KEY (`cid`,`name`),
  KEY `int_value` (`int_value`),
  KEY `float_value` (`float_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `typecho_fields`
--

INSERT INTO typecho_fields (`cid`, `name`, `type`, `str_value`, `int_value`, `float_value`) VALUES
('6', 'test', 'str', '这仅仅只是个测试', '0', '0'),
('20', 'imageUrl', 'str', 'https://2.gravatar.com/avatar/8a8304a40ec366197242d3ea3e31baf9?d=https%3A%2F%2Fidenticons.github.com%2F3b3dc4a2b4d939198ac835a8a593bd60.png&r=x&s=140', '0', '0'),
('19', 'imageUrl', 'str', '', '0', '0');

-- --------------------------------------------------------

--
-- 表的结构 `typecho_metas`
--

CREATE TABLE IF NOT EXISTS `typecho_metas` (
  `mid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) DEFAULT NULL,
  `slug` varchar(200) DEFAULT NULL,
  `type` varchar(32) NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `count` int(10) unsigned DEFAULT '0',
  `order` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`mid`),
  KEY `slug` (`slug`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `typecho_metas`
--

INSERT INTO typecho_metas (`mid`, `name`, `slug`, `type`, `description`, `count`, `order`) VALUES
('1', '默认分类', 'default', 'category', '只是一个默认分类', '17', '1'),
('2', '读书', '读书', 'tag', NULL, '1', '0'),
('3', '音乐', '音乐', 'tag', NULL, '1', '0');

-- --------------------------------------------------------

--
-- 表的结构 `typecho_options`
--

CREATE TABLE IF NOT EXISTS `typecho_options` (
  `name` varchar(32) NOT NULL,
  `user` int(10) unsigned NOT NULL DEFAULT '0',
  `value` text,
  PRIMARY KEY (`name`,`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `typecho_options`
--

INSERT INTO typecho_options (`name`, `user`, `value`) VALUES
('theme', '0', 'default'),
('timezone', '0', '28800'),
('charset', '0', 'UTF-8'),
('contentType', '0', 'text/html'),
('gzip', '0', '0'),
('generator', '0', 'Typecho 0.9/13.12.20'),
('title', '0', '清风门前过'),
('description', '0', '拥有野谷幽兰般的宁静，才能感受到，每一缕走过的风携带着的清凉'),
('keywords', '0', NULL),
('rewrite', '0', '1'),
('frontPage', '0', 'recent'),
('commentsRequireMail', '0', '1'),
('commentsWhitelist', '0', '1'),
('commentsRequireURL', '0', '0'),
('commentsRequireModeration', '0', '0'),
('plugins', '0', 'a:2:{s:9:\"activated\";a:2:{s:11:\"AudioPlayer\";a:1:{s:7:\"handles\";a:3:{s:34:\"admin/editor-js.php:markdownEditor\";a:1:{i:0;a:2:{i:0;s:18:\"AudioPlayer_Plugin\";i:1;s:9:\"addButton\";}}s:21:\"Widget_Archive:header\";a:1:{i:0;a:2:{i:0;s:18:\"AudioPlayer_Plugin\";i:1;s:6:\"header\";}}s:21:\"Widget_Archive:footer\";a:1:{i:0;a:2:{i:0;s:18:\"AudioPlayer_Plugin\";i:1;s:6:\"footer\";}}}}s:6:\"Export\";a:0:{}}s:7:\"handles\";a:3:{s:34:\"admin/editor-js.php:markdownEditor\";a:1:{i:0;a:2:{i:0;s:18:\"AudioPlayer_Plugin\";i:1;s:9:\"addButton\";}}s:21:\"Widget_Archive:header\";a:1:{i:0;a:2:{i:0;s:18:\"AudioPlayer_Plugin\";i:1;s:6:\"header\";}}s:21:\"Widget_Archive:footer\";a:1:{i:0;a:2:{i:0;s:18:\"AudioPlayer_Plugin\";i:1;s:6:\"footer\";}}}}'),
('commentDateFormat', '0', 'F jS, Y \\a\\t h:i a'),
('siteUrl', '0', 'http://localhost/gite'),
('defaultCategory', '0', '1'),
('allowRegister', '0', '0'),
('defaultAllowComment', '0', '1'),
('defaultAllowPing', '0', '1'),
('defaultAllowFeed', '0', '1'),
('pageSize', '0', '5'),
('postsListSize', '0', '10'),
('commentsListSize', '0', '10'),
('commentsHTMLTagAllowed', '0', NULL),
('postDateFormat', '0', 'Y-m-d'),
('feedFullText', '0', '1'),
('editorSize', '0', '350'),
('autoSave', '0', '0'),
('markdown', '0', '1'),
('commentsMaxNestingLevels', '0', '5'),
('commentsPostTimeout', '0', '2592000'),
('commentsUrlNofollow', '0', '1'),
('commentsShowUrl', '0', '1'),
('commentsPageBreak', '0', '0'),
('commentsThreaded', '0', '1'),
('commentsPageSize', '0', '20'),
('commentsPageDisplay', '0', 'last'),
('commentsOrder', '0', 'ASC'),
('commentsCheckReferer', '0', '1'),
('commentsAutoClose', '0', '0'),
('commentsPostIntervalEnable', '0', '1'),
('commentsPostInterval', '0', '60'),
('commentsShowCommentOnly', '0', '0'),
('commentsAvatar', '0', '1'),
('commentsAvatarRating', '0', 'G'),
('routingTable', '0', 'a:26:{i:0;a:25:{s:5:\"index\";a:6:{s:3:\"url\";s:1:\"/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:8:\"|^[/]?$|\";s:6:\"format\";s:1:\"/\";s:6:\"params\";a:0:{}}s:7:\"archive\";a:6:{s:3:\"url\";s:10:\"/archives/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:17:\"|^/archives[/]?$|\";s:6:\"format\";s:10:\"/archives/\";s:6:\"params\";a:0:{}}s:2:\"do\";a:6:{s:3:\"url\";s:22:\"/action/[action:alpha]\";s:6:\"widget\";s:9:\"Widget_Do\";s:6:\"action\";s:6:\"action\";s:4:\"regx\";s:32:\"|^/action/([_0-9a-zA-Z-]+)[/]?$|\";s:6:\"format\";s:10:\"/action/%s\";s:6:\"params\";a:1:{i:0;s:6:\"action\";}}s:4:\"post\";a:6:{s:3:\"url\";s:21:\"/archives/[slug].html\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:31:\"|^/archives/([^/]+)\\.html[/]?$|\";s:6:\"format\";s:17:\"/archives/%s.html\";s:6:\"params\";a:1:{i:0;s:4:\"slug\";}}s:10:\"attachment\";a:6:{s:3:\"url\";s:26:\"/attachment/[cid:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:28:\"|^/attachment/([0-9]+)[/]?$|\";s:6:\"format\";s:15:\"/attachment/%s/\";s:6:\"params\";a:1:{i:0;s:3:\"cid\";}}s:8:\"category\";a:6:{s:3:\"url\";s:17:\"/category/[slug]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:25:\"|^/category/([^/]+)[/]?$|\";s:6:\"format\";s:13:\"/category/%s/\";s:6:\"params\";a:1:{i:0;s:4:\"slug\";}}s:3:\"tag\";a:6:{s:3:\"url\";s:12:\"/tag/[slug]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:20:\"|^/tag/([^/]+)[/]?$|\";s:6:\"format\";s:8:\"/tag/%s/\";s:6:\"params\";a:1:{i:0;s:4:\"slug\";}}s:6:\"author\";a:6:{s:3:\"url\";s:22:\"/author/[uid:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:24:\"|^/author/([0-9]+)[/]?$|\";s:6:\"format\";s:11:\"/author/%s/\";s:6:\"params\";a:1:{i:0;s:3:\"uid\";}}s:6:\"search\";a:6:{s:3:\"url\";s:19:\"/search/[keywords]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:23:\"|^/search/([^/]+)[/]?$|\";s:6:\"format\";s:11:\"/search/%s/\";s:6:\"params\";a:1:{i:0;s:8:\"keywords\";}}s:10:\"index_page\";a:6:{s:3:\"url\";s:21:\"/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:22:\"|^/page/([0-9]+)[/]?$|\";s:6:\"format\";s:9:\"/page/%s/\";s:6:\"params\";a:1:{i:0;s:4:\"page\";}}s:12:\"archive_page\";a:6:{s:3:\"url\";s:30:\"/archives/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:31:\"|^/archives/page/([0-9]+)[/]?$|\";s:6:\"format\";s:18:\"/archives/page/%s/\";s:6:\"params\";a:1:{i:0;s:4:\"page\";}}s:13:\"category_page\";a:6:{s:3:\"url\";s:32:\"/category/[slug]/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:34:\"|^/category/([^/]+)/([0-9]+)[/]?$|\";s:6:\"format\";s:16:\"/category/%s/%s/\";s:6:\"params\";a:2:{i:0;s:4:\"slug\";i:1;s:4:\"page\";}}s:8:\"tag_page\";a:6:{s:3:\"url\";s:27:\"/tag/[slug]/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:29:\"|^/tag/([^/]+)/([0-9]+)[/]?$|\";s:6:\"format\";s:11:\"/tag/%s/%s/\";s:6:\"params\";a:2:{i:0;s:4:\"slug\";i:1;s:4:\"page\";}}s:11:\"author_page\";a:6:{s:3:\"url\";s:37:\"/author/[uid:digital]/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:33:\"|^/author/([0-9]+)/([0-9]+)[/]?$|\";s:6:\"format\";s:14:\"/author/%s/%s/\";s:6:\"params\";a:2:{i:0;s:3:\"uid\";i:1;s:4:\"page\";}}s:11:\"search_page\";a:6:{s:3:\"url\";s:34:\"/search/[keywords]/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:32:\"|^/search/([^/]+)/([0-9]+)[/]?$|\";s:6:\"format\";s:14:\"/search/%s/%s/\";s:6:\"params\";a:2:{i:0;s:8:\"keywords\";i:1;s:4:\"page\";}}s:12:\"archive_year\";a:6:{s:3:\"url\";s:18:\"/[year:digital:4]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:19:\"|^/([0-9]{4})[/]?$|\";s:6:\"format\";s:4:\"/%s/\";s:6:\"params\";a:1:{i:0;s:4:\"year\";}}s:13:\"archive_month\";a:6:{s:3:\"url\";s:36:\"/[year:digital:4]/[month:digital:2]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:30:\"|^/([0-9]{4})/([0-9]{2})[/]?$|\";s:6:\"format\";s:7:\"/%s/%s/\";s:6:\"params\";a:2:{i:0;s:4:\"year\";i:1;s:5:\"month\";}}s:11:\"archive_day\";a:6:{s:3:\"url\";s:52:\"/[year:digital:4]/[month:digital:2]/[day:digital:2]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:41:\"|^/([0-9]{4})/([0-9]{2})/([0-9]{2})[/]?$|\";s:6:\"format\";s:10:\"/%s/%s/%s/\";s:6:\"params\";a:3:{i:0;s:4:\"year\";i:1;s:5:\"month\";i:2;s:3:\"day\";}}s:17:\"archive_year_page\";a:6:{s:3:\"url\";s:38:\"/[year:digital:4]/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:33:\"|^/([0-9]{4})/page/([0-9]+)[/]?$|\";s:6:\"format\";s:12:\"/%s/page/%s/\";s:6:\"params\";a:2:{i:0;s:4:\"year\";i:1;s:4:\"page\";}}s:18:\"archive_month_page\";a:6:{s:3:\"url\";s:56:\"/[year:digital:4]/[month:digital:2]/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:44:\"|^/([0-9]{4})/([0-9]{2})/page/([0-9]+)[/]?$|\";s:6:\"format\";s:15:\"/%s/%s/page/%s/\";s:6:\"params\";a:3:{i:0;s:4:\"year\";i:1;s:5:\"month\";i:2;s:4:\"page\";}}s:16:\"archive_day_page\";a:6:{s:3:\"url\";s:72:\"/[year:digital:4]/[month:digital:2]/[day:digital:2]/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:55:\"|^/([0-9]{4})/([0-9]{2})/([0-9]{2})/page/([0-9]+)[/]?$|\";s:6:\"format\";s:18:\"/%s/%s/%s/page/%s/\";s:6:\"params\";a:4:{i:0;s:4:\"year\";i:1;s:5:\"month\";i:2;s:3:\"day\";i:3;s:4:\"page\";}}s:12:\"comment_page\";a:6:{s:3:\"url\";s:53:\"[permalink:string]/comment-page-[commentPage:digital]\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:36:\"|^(.+)/comment\\-page\\-([0-9]+)[/]?$|\";s:6:\"format\";s:18:\"%s/comment-page-%s\";s:6:\"params\";a:2:{i:0;s:9:\"permalink\";i:1;s:11:\"commentPage\";}}s:4:\"feed\";a:6:{s:3:\"url\";s:20:\"/feed[feed:string:0]\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:4:\"feed\";s:4:\"regx\";s:17:\"|^/feed(.*)[/]?$|\";s:6:\"format\";s:7:\"/feed%s\";s:6:\"params\";a:1:{i:0;s:4:\"feed\";}}s:8:\"feedback\";a:6:{s:3:\"url\";s:31:\"[permalink:string]/[type:alpha]\";s:6:\"widget\";s:15:\"Widget_Feedback\";s:6:\"action\";s:6:\"action\";s:4:\"regx\";s:29:\"|^(.+)/([_0-9a-zA-Z-]+)[/]?$|\";s:6:\"format\";s:5:\"%s/%s\";s:6:\"params\";a:2:{i:0;s:9:\"permalink\";i:1;s:4:\"type\";}}s:4:\"page\";a:6:{s:3:\"url\";s:12:\"/[slug].html\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";s:4:\"regx\";s:22:\"|^/([^/]+)\\.html[/]?$|\";s:6:\"format\";s:8:\"/%s.html\";s:6:\"params\";a:1:{i:0;s:4:\"slug\";}}}s:5:\"index\";a:3:{s:3:\"url\";s:1:\"/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:7:\"archive\";a:3:{s:3:\"url\";s:10:\"/archives/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:2:\"do\";a:3:{s:3:\"url\";s:22:\"/action/[action:alpha]\";s:6:\"widget\";s:9:\"Widget_Do\";s:6:\"action\";s:6:\"action\";}s:4:\"post\";a:3:{s:3:\"url\";s:21:\"/archives/[slug].html\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:10:\"attachment\";a:3:{s:3:\"url\";s:26:\"/attachment/[cid:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:8:\"category\";a:3:{s:3:\"url\";s:17:\"/category/[slug]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:3:\"tag\";a:3:{s:3:\"url\";s:12:\"/tag/[slug]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:6:\"author\";a:3:{s:3:\"url\";s:22:\"/author/[uid:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:6:\"search\";a:3:{s:3:\"url\";s:19:\"/search/[keywords]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:10:\"index_page\";a:3:{s:3:\"url\";s:21:\"/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:12:\"archive_page\";a:3:{s:3:\"url\";s:30:\"/archives/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:13:\"category_page\";a:3:{s:3:\"url\";s:32:\"/category/[slug]/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:8:\"tag_page\";a:3:{s:3:\"url\";s:27:\"/tag/[slug]/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:11:\"author_page\";a:3:{s:3:\"url\";s:37:\"/author/[uid:digital]/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:11:\"search_page\";a:3:{s:3:\"url\";s:34:\"/search/[keywords]/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:12:\"archive_year\";a:3:{s:3:\"url\";s:18:\"/[year:digital:4]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:13:\"archive_month\";a:3:{s:3:\"url\";s:36:\"/[year:digital:4]/[month:digital:2]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:11:\"archive_day\";a:3:{s:3:\"url\";s:52:\"/[year:digital:4]/[month:digital:2]/[day:digital:2]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:17:\"archive_year_page\";a:3:{s:3:\"url\";s:38:\"/[year:digital:4]/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:18:\"archive_month_page\";a:3:{s:3:\"url\";s:56:\"/[year:digital:4]/[month:digital:2]/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:16:\"archive_day_page\";a:3:{s:3:\"url\";s:72:\"/[year:digital:4]/[month:digital:2]/[day:digital:2]/page/[page:digital]/\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:12:\"comment_page\";a:3:{s:3:\"url\";s:53:\"[permalink:string]/comment-page-[commentPage:digital]\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}s:4:\"feed\";a:3:{s:3:\"url\";s:20:\"/feed[feed:string:0]\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:4:\"feed\";}s:8:\"feedback\";a:3:{s:3:\"url\";s:31:\"[permalink:string]/[type:alpha]\";s:6:\"widget\";s:15:\"Widget_Feedback\";s:6:\"action\";s:6:\"action\";}s:4:\"page\";a:3:{s:3:\"url\";s:12:\"/[slug].html\";s:6:\"widget\";s:14:\"Widget_Archive\";s:6:\"action\";s:6:\"render\";}}'),
('actionTable', '0', 'a:1:{s:6:\"export\";s:13:\"Export_Action\";}'),
('panelTable', '0', 'a:2:{s:5:\"child\";a:2:{i:4;a:0:{}i:1;a:1:{i:0;a:6:{i:0;s:12:\"数据备份\";i:1;s:12:\"数据备份\";i:2;s:38:\"extending.php?panel=Export%2Fpanel.php\";i:3;s:13:\"administrator\";i:4;b:0;i:5;s:0:\"\";}}}s:4:\"file\";a:1:{i:0;s:18:\"Export%2Fpanel.php\";}}'),
('attachmentTypes', '0', '@image@'),
('frontArchive', '0', '1'),
('autoSave', '1', '0'),
('markdown', '1', '1'),
('defaultAllowComment', '1', '0'),
('defaultAllowPing', '1', '0'),
('defaultAllowFeed', '1', '1'),
('commentsMarkdown', '0', '0'),
('editorSize', '1', '350'),
('theme:default', '0', 'a:2:{s:7:\"logoUrl\";N;s:12:\"sidebarBlock\";a:5:{i:0;s:15:\"ShowRecentPosts\";i:1;s:18:\"ShowRecentComments\";i:2;s:12:\"ShowCategory\";i:3;s:11:\"ShowArchive\";i:4;s:9:\"ShowOther\";}}'),
('plugin:AudioPlayer', '0', 'a:1:{s:5:\"theme\";s:6:\"simple\";}'),
('plugin:Export', '0', 'a:1:{s:4:\"path\";s:26:\"/usr/plugins/Export/backup\";}');

-- --------------------------------------------------------

--
-- 表的结构 `typecho_relationships`
--

CREATE TABLE IF NOT EXISTS `typecho_relationships` (
  `cid` int(10) unsigned NOT NULL,
  `mid` int(10) unsigned NOT NULL,
  PRIMARY KEY (`cid`,`mid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `typecho_relationships`
--

INSERT INTO typecho_relationships (`cid`, `mid`) VALUES
('1', '1'),
('3', '1'),
('3', '2'),
('4', '1'),
('4', '3'),
('6', '1'),
('8', '1'),
('9', '1'),
('10', '1'),
('11', '1'),
('12', '1'),
('13', '1'),
('14', '1'),
('16', '1'),
('17', '1'),
('18', '1'),
('19', '1'),
('20', '1'),
('21', '1');

-- --------------------------------------------------------

--
-- 表的结构 `typecho_users`
--

CREATE TABLE IF NOT EXISTS `typecho_users` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) DEFAULT NULL,
  `password` varchar(64) DEFAULT NULL,
  `mail` varchar(200) DEFAULT NULL,
  `url` varchar(200) DEFAULT NULL,
  `screenName` varchar(32) DEFAULT NULL,
  `created` int(10) unsigned DEFAULT '0',
  `activated` int(10) unsigned DEFAULT '0',
  `logged` int(10) unsigned DEFAULT '0',
  `group` varchar(16) DEFAULT 'visitor',
  `authCode` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `mail` (`mail`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- 转存表中的数据 `typecho_users`
--

INSERT INTO typecho_users (`uid`, `name`, `password`, `mail`, `url`, `screenName`, `created`, `activated`, `logged`, `group`, `authCode`) VALUES
('1', 'admin', '$T$asuBrMmjh965ecb06fe8f72ff9b1795c046489438', 'shingchi@sina.cn', 'http://lcz.me', 'ShingChi', '1314205926', '1388809608', '1388058816', 'administrator', '27732baa5dcd6207656fff2a022d6587abbc1f0b');

