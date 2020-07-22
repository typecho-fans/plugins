<?php

class AMP_Action extends Typecho_Widget implements Widget_Interface_Do
{

    public function action()
    {

    }

    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->LOGO = Helper::options()->plugin('AMP')->LOGO;//同时为默认图片
        $this->db = Typecho_Db::get();
        $this->tablename = $this->db->getPrefix() . 'PageCache';
        $this->baseurl = Helper::options()->index;
        $this->baseurl = str_replace("https://", "//", $this->baseurl);
        $this->baseurl = str_replace("http://", "//", $this->baseurl);
    }

    /**
     * @throws Typecho_Exception
     * @throws Typecho_Plugin_Exception
     * author:Holmesian
     * date: 2020/3/13 11:45
     * 添加头部信息
     */
    public static function headlink()
    {
        $widget = Typecho_Widget::widget('Widget_Archive');
        $headurl = '';//初始化输出内容

        if ($widget->is('index') and !isset($widget->request->page)) {//输出AMP首页
            if (Helper::options()->plugin('AMP')->ampIndex == 1) {
                $fullURL = Typecho_Common::url("ampindex", Helper::options()->index);
                $headurl = "\n<link rel=\"amphtml\" href=\"{$fullURL}\">\n";
            }
        }

        if ($widget->is('post')) {//文章页
            $targetTemp = Typecho_Widget::widget('AMP_Action')->getUrlRule();//静态函数调用动态函数
            if (isset($widget->request->cid)) {
                $cid = $widget->request->cid;
                $target = str_replace('[cid:digital]', $cid, $targetTemp);
            }
            if (isset($widget->request->slug)) {
                $slug = $widget->request->slug;
                $target = str_replace('[slug]', $slug, $targetTemp);
            }

            if (isset($target)) {//输出文章页对应的AMP/MIP页面
                $ampurl = Typecho_Common::url("amp/{$target}", Helper::options()->index);
                $mipurl = Typecho_Common::url("mip/{$target}", Helper::options()->index);
                $headurl = "\n<link rel=\"amphtml\" href=\"{$ampurl}\">\n";
                $headurl .= "<link rel=\"miphtml\" href=\"{$mipurl}\">\n";
            }
        }

        echo $headurl;
    }


    /**
     * @throws Typecho_Plugin_Exception
     * @throws Typecho_Widget_Exception
     * author:Holmesian
     * date: 2020/3/13 11:45
     * 生成 AMP sitemap
     */
    public function AMPsitemap()
    {

        if (Helper::options()->plugin('AMP')->AMPsitemap == 0) {
            throw new Typecho_Widget_Exception('未开启AMPsitemap功能！');
        }

        $this->MakeSiteMap('amp');

    }

    /**
     * @throws Typecho_Plugin_Exception
     * @throws Typecho_Widget_Exception
     * author:Holmesian
     * date: 2020/3/13 11:45
     * 生成 MIP sitemap
     */
    public function MIPsitemap()
    {

        if (Helper::options()->plugin('AMP')->MIPsitemap == 0) {
            throw new Typecho_Widget_Exception('未开启MIPsitemap功能！');
        }

        $this->MakeSiteMap('mip');

    }

    /**
     * @throws Typecho_Plugin_Exception
     * @throws Typecho_Widget_Exception
     * author:Holmesian
     * date: 2020/3/13 11:45
     * 渲染MIP页面
     */
    public function MIPpage()
    {
        $requestHash = $this->request->getPathinfo();
        $context = $this->get($requestHash); //查找是否已经缓存
        $this->article = $this->getArticle($this->request->target);

        if (isset($this->article['isblank'])) {
            throw new Typecho_Widget_Exception("不存在或已删除。<a href='{$this->baseurl}'>返回首页</a>");
        }
        if (Helper::options()->plugin('AMP')->OnlyForSpiders == 1) {//判断是否是对应的爬虫来访
            $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
            $spider = strtolower('Baiduspider');
            if (strpos($userAgent, $spider) == false) {//不是百度的蜘蛛
                header("Location: {$this->article['permalink']}");
            }
        }

        if (!is_null($context)) {//有缓存的情况直接输出
            print($context);
        } else {//没缓存的生成页面再进行缓存
            $MIPpage = array(
                'title' => $this->article['title'],
                'permalink' => $this->article['permalink'],
                'mipurl' => $this->article['mipurl'],
                'modified' => date('Y-m-d\TH:i:s', $this->article['modified']),
                'date' => $this->article['date']->format('Y-m-d\TH:i:s'),
                'isMarkdown' => $this->article['isMarkdown'],
                'imgData' => $this->GetPostImg(),//MIP页面的结果化数据可以没有图片
                'APPID' => Helper::options()->plugin('AMP')->baiduAPPID,//熊掌号的APPID
                'mip_stats_token' => trim(Helper::options()->plugin('AMP')->mip_stats_token),
                'desc' => self::cleanUp($this->article['text']),
                'publisher' => Helper::options()->title,
                'MIPtext' => $this->MIPInit($this->article['text']),
                'version' => $this->version
            );
            ob_start();
            require_once('templates/MIPpage.php');
            $cache = ob_get_contents();
            $this->set($requestHash, $cache);
        }
    }


    /**
     * @throws Typecho_Plugin_Exception
     * @throws Typecho_Widget_Exception
     * author:Holmesian
     * date: 2020/3/13 11:44
     * 返回AMP需要的JSON信息
     */
    public function AMPlist()
    {
        if (Helper::options()->plugin('AMP')->ampIndex == 0) {
            throw new Typecho_Widget_Exception('未开启AMP版首页！');
        }
        $currentPage = $this->request->list_id;
        $articles = $this->MakeArticleList('amp', $currentPage, 5);
        $article_data = array(
            'pageCount' => ceil($this->_total / 5),
            'currentPage' => $currentPage,
        );
        $article_data['article'] = array();
        foreach ($articles as $article) {
            if (isset($article['text'])) {
                $article['isMarkdown'] = (0 === strpos($article['text'], '<!--markdown-->'));
                if ($article['isMarkdown']) {
                    $article['text'] = substr($article['text'], 15);
                }
            }
            if ($article['isMarkdown']) {
                $article['text'] = $html = Markdown::convert($article['text']);
            }
            $article_data['article'][] = array(
                'title' => $article['title'],
                'url' => $article['permalink'],
                'content' => $this->substrFormat(strip_tags($article['text']), 200),
            );
        }
        $arr = array('items' => $article_data);
        header("Access-Control-Allow-Origin: *");
        print(json_encode($arr));
    }

    public function AMPindex()
    {

        if (Helper::options()->plugin('AMP')->ampIndex == 0) {
            header("Location: {$this->baseurl}");
        }
        require_once('templates/AMPindex.php');
    }

    /**
     * @throws Typecho_Plugin_Exception
     * @throws Typecho_Widget_Exception
     * author:Holmesian
     * date: 2020/3/13 11:44
     * 渲染AMP页面
     */
    public function AMPpage()
    {
        $requestHash = $this->request->getPathinfo();
        $context = $this->get($requestHash); //查找是否已经缓存

        $this->article = $this->getArticle($this->request->target);
        if (isset($this->article['isblank'])) {
            throw new Typecho_Widget_Exception('不存在或已删除');
        }
        if (Helper::options()->plugin('AMP')->OnlyForSpiders == 1) {//判断是否是对应的爬虫来访
            $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
            $spider = strtolower('Googlebot');
            $spider2 = strtolower('google-amphtml');
            if (strpos($userAgent, $spider) == false or strpos($userAgent, $spider2) == false) {//不是Google的蜘蛛
                header("Location: {$this->article['permalink']}");
            }
        }

        if (!is_null($context)) {//有缓存的情况直接输出
            print($context);
        } else {
            $AMPpage = array(
                'title' => $this->article['title'],
                'permalink' => $this->article['permalink'],
                'mipurl' => $this->article['mipurl'],
                'modified' => date('F j, Y', $this->article['modified']),
                'date' => $this->article['date']->format('F j, Y'),
                'author' => $this->article['author'],
                'LOGO' => $this->LOGO,
                'isMarkdown' => $this->article['isMarkdown'],
                'imgData' => $this->GetPostImg(),
                'desc' => self::cleanUp($this->article['text']),
                'publisher' => Helper::options()->title,
                'AMPtext' => $this->AMPInit($this->article['text']),
                'version' => $this->version
            );
            //AMP页面的结果化数据必须有图片
            if (!is_array($AMPpage['imgData'])) {
                $AMPpage['imgData'] = self::getSizeArr($AMPpage['LOGO'], '1200', '1200');//如果找不到图片就用LOGO
            }
            ob_start();
            require_once('templates/AMPpage.php');
            $cache = ob_get_contents();
            $this->set($requestHash, $cache);
        }
    }

    /**
     * @throws Typecho_Exception
     * author:Holmesian
     * date: 2020/3/13 11:44
     * 清理缓存
     */
    public function cleancache()
    {
        $user = Typecho_Widget::widget('Widget_User');
        if (!$user->pass('administrator')) {
            die('未登录用户!');
        }
        $this->del('*');
        print('Clean all cache!');
    }

    /**
     * @param $contents
     * @param $class
     * @throws Typecho_Exception
     * @throws Typecho_Plugin_Exception
     * author:Holmesian
     * date: 2020/3/13 11:43
     * 实时推送文章
     */
    public static function sendRealtime($contents, $class)
    {
        //获取系统配置
        $options = Helper::options();

        //如果文章属性为 隐藏 或 定时发布 或 非首次发布(编辑) 发布则不推送
        if ('publish' != $contents['visibility'] || $contents['created'] > time() || !is_null($contents['created'])) {
            return;
        }

        //如果没有开启自动提交功能 则不推送
        $mipAutoSubmit = $options->plugin('AMP')->mipAutoSubmit;
        if ($mipAutoSubmit == 0) {
            return;
        }

        //判断是否配置相关信息
//        if (is_null($options->plugin('AMP')->baiduAPPID) or is_null($options->plugin('AMP')->baiduTOKEN)) {
        if (is_null($options->plugin('AMP')->baiduAPI)) {
            throw new Typecho_Plugin_Exception(_t('未配置 快速收录接口地址，自动提交失败'));
        } else {
//            $appid = trim($options->plugin('AMP')->baiduAPPID);//过滤空格
//            $token = trim($options->plugin('AMP')->baiduTOKEN);//过滤空格
            //熊掌号下线后，修改未快速收录接口   2020.06.07

            $api = trim($options->plugin('AMP')->baiduAPI);//默认填写的是快速收录地址
            if ($mipAutoSubmit == 2) {//如果选择了普通收录地址，则进行替换
                $api = preg_replace("/&type=[a-z]+/", "", $api);//将快速收录接口换成普通收录接口
            }
        }

        $article = Typecho_Widget::widget('AMP_Action')->getArticleByCid($class->cid);//根据cid获取文章内容

        if ((int)$article['created'] + 86400 < (int)$article['modified']) {//之前判断忽略了自动保存草稿的问题
            return;//草稿在一天之内的文章推送，否则不推送。
        }



        //获取文章链接
        $url = $article['mipurl'];


        $hash = array(//发布之前清除对应的MIP/AMP缓存
            'mip' => str_replace(Helper::options()->index, "", $article['mipurl']),
            'amp' => str_replace(Helper::options()->index, "", $article['ampurl'])
        );

        Typecho_Widget::widget('AMP_Action')->del($hash);



        //发送自动提交
        try {


            $http = Typecho_Http_Client::get();
            $http->setData($url);//改为Post发送模式
            $http->setHeader('Content-Type', 'text/plain');

            $result = $http->send($api);
            $json = json_decode($result);


            if (isset($json->error)) {//提交出错时返回错误信息
                throw new Typecho_Plugin_Exception(_t("错误代码：" . $json->error . "<br> 出错原因：" . $json->message), $json->error);
            }

        } catch (Exception $e) {
            throw new Typecho_Plugin_Exception(_t('抱歉，自动提交失败。<br>请关闭自动提交功能！<br><hr>' . $e->getMessage()));
        }

    }

    public function getArticle($target)
    {
        $tempTarget = explode('.', $target)[0];
        $article = $this->getArticleBySlug($tempTarget);//先尝试别名slug
        if (isset($article['isblank'])) {//别名获取不到则认为是序号cid
            $article = $this->getArticleByCid($tempTarget);
        }
        return $article;
    }

    private function getArticleBySlug($slug)
    {
        $select = $this->db->select()->from('table.contents')
            ->where('slug = ?', $slug);
        $article = $this->ArticleBase($select);
        return $article;
    }

    private function getArticleByCid($cid)
    {
        $select = $this->db->select()->from('table.contents')
            ->where('cid = ?', $cid);
        $article = $this->ArticleBase($select);
        return $article;
    }

    private function ArticleBase($select)
    {
        $article_src = $this->db->fetchRow($select);

        if (count($article_src) > 0) {
            $article = Typecho_Widget::widget("Widget_Abstract_Contents")->push($article_src);
            $select = $this->db->select('table.users.screenName')
                ->from('table.users')
                ->where('uid = ?', $article['authorId']);
            $author = $this->db->fetchRow($select);
            $article['author'] = $author['screenName'];
            if ($article['isMarkdown'] == True) {
                $article['text'] = Markdown::convert($article['text']);
            } else {
                $article['text'] = Typecho_Widget::widget("Widget_Abstract_Contents")->autoP($article['text']);
            }
            $targetTemp = $this->getUrlRule();

            $target = str_replace('[slug]', $article['slug'], $targetTemp);
            $target = str_replace('[cid:digital]', $article['cid'], $target);

            $article['mipurl'] = Typecho_Common::url("mip/{$target}", Helper::options()->index);
            $article['ampurl'] = Typecho_Common::url("amp/{$target}", Helper::options()->index);
        } else {
            $article = array(
                'isMarkdown' => false,
                'isblank' => true,
            );
        }
        return $article;
    }


    //生成文章列表
    public function MakeArticleList($linkType = 'amp', $page = 0, $pageSize = 0)
    {
        $db = Typecho_Db::get();
        $thismoment = time();//Fix sqlite不支持生成时间戳
        $sql = $db->select()->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', 'post')
            ->where("table.contents.created <= {$thismoment}", 'post') //Fix 避免未达到时间的文章提前曝光
            ->where('table.contents.password IS NULL') //Fix 避免加密文章泄露
            ->order('table.contents.created', Typecho_Db::SORT_DESC);
        if ($page > 0 and $pageSize > 0) {
            $countSql = clone $sql;
            $this->_total = Typecho_Widget::widget('Widget_Abstract_Contents')->size($countSql);
            $sql = $sql->page($page, $pageSize);
        }
        $articles = $db->fetchAll($sql);

        $articleList = array();

        $targetTemp = $this->getUrlRule();

        foreach ($articles as $article) {
            $article['categories'] = $db->fetchAll($db->select()->from('table.metas')
                ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                ->where('table.relationships.cid = ?', $article['cid'])
                ->where('table.metas.type = ?', 'category')
                ->order('table.metas.order', Typecho_Db::SORT_ASC));
            $article['category'] = urlencode(current(Typecho_Common::arrayFlatten($article['categories'], 'slug')));


            $article['slug'] = urlencode($article['slug']);
            $article['date'] = new Typecho_Date($article['created']);
            $article['year'] = $article['date']->year;
            $article['month'] = $article['date']->month;
            $article['day'] = $article['date']->day;


            $target = str_replace('[slug]', $article['slug'], $targetTemp);
            $target = str_replace('[cid:digital]', $article['cid'], $target);

            if ($linkType == 'mip') {
                $article['permalink'] = Typecho_Common::url("mip/{$target}", Helper::options()->index);
            } else {
                $article['permalink'] = Typecho_Common::url("amp/{$target}", Helper::options()->index);
            }
            $articleList[] = $article;
        }
        return $articleList;
    }


    //获取文章内图片
    private function GetPostImg()
    {

        $text = $this->article['text'];

        $pattern = '/\<img.*?src\=\"(.*?)\"[^>]*>/i';
        $patternMD = '/\!\[.*?\]\((http(s)?:\/\/.*?(jpg|png))/i';
        $patternMDfoot = '/\[.*?\]:\s*(http(s)?:\/\/.*?(jpg|png))/i';
        if (preg_match($patternMDfoot, $text, $img)) {
            $img_url = $img[1];
        } else if (preg_match($patternMD, $text, $img)) {
            $img_url = $img[1];
        } else if (preg_match($pattern, $text, $img)) {
            preg_match("/(?:\()(.*)(?:\))/i", $img[0], $result);
            $img_url = $img[1];
        } else {
            //正文里没找到图片就去附件里找
            $attsrc = Typecho_Widget::widget('Widget_Contents_Attachment_Related', 'parentId=' . $this->article['cid'])->stack;
            $att = '';
            foreach ($attsrc as $attimg) {
                $att = $att . $attimg['text'];
            }
            if (preg_match($pattern, $att, $img)) {//附件里只需要匹配img标签的内容
                preg_match("/(?:\()(.*)(?:\))/i", $img[0], $result);
                $img_url = $img[1];
            } else {//附件里再找不到就调LOGO了
//                $img_url = $this->defaultPIC;
                $img_url = null;//熊掌号修改了规则，如果图不对文的话会被惩罚，所以没有图片则不出图
            }
        }

        if (is_null($img_url)) {//如果没有找到图片则返回空
            return null;
        } else {//如果找到文章图片则返回图片数组
            return self::getSizeArr($img_url);
        }
    }

    //获取图片尺寸
    private static function getSizeArr($img_url, $width = '700', $height = '400')
    {
        try {//尝试获取图片尺寸
            list($width, $height, $type, $attr) = @getimagesize($img_url);
            $imgData = array(
                'url' => $img_url,
                'width' => $width,
                'height' => $height,
            );
            return $imgData;
        } catch (Exception $e) {//出问题 或 获取不到则使用默认尺寸
            $imgData = array(
                'url' => $img_url,
                'width' => $width,
                'height' => $height,
            );
            return $imgData;
        }
    }


    //初始化MIP信息
    private function MIPInit($text)
    {
        $text = $this->IMGsize($text);
        $text = $this->closeTags($text);

        $text = str_replace('<img', '<mip-img  layout="responsive" popup ', $text);
        $text = str_replace('img>', 'mip-img>', $text);
        $text = str_replace('<!- toc end ->', '', $text);
        $text = str_replace('<style', '<style mip-custom" ', $text);
        $text = str_replace('javascript:content_index_toggleToc()', '#', $text);
        $text = $this->stripHtmlTags(array('font', 'color', 'input', 'size'), $text, true);//清理指定HTML标签
        return $text;
    }

    //初始化AMP信息
    private function AMPInit($text)
    {
        $text = $this->IMGsize($text);
        $text = $this->closeTags($text);

        $text = str_replace('<img', '<amp-img  layout="responsive" ', $text);
        $text = str_replace('img>', 'amp-img>', $text);
        $text = str_replace('<style', '<style amp-custom" ', $text);
        $text = str_replace('<!- toc end ->', '', $text);
        $text = str_replace('javascript:content_index_toggleToc()', '#', $text);
        $text = $this->stripHtmlTags(array('font', 'color', 'input', 'size'), $text, true);//清理指定HTML标签
        return $text;
    }

    //修正img标签的尺寸数据
    private function IMGsize($html)
    {
        $html = preg_replace_callback(
            '(<img src="(.*?)")',
            function ($m) {
                if (isset(parse_url($m[1])['host'])) {//Fix 相对路径与绝对路径附件的问题
                    if (parse_url($m[1])['host'] == parse_url(Helper::options()->siteUrl)['host']) {
                        $url = $_SERVER['DOCUMENT_ROOT'] . parse_url($m[1])['path'];
                    } else {
                        $url = $m[1];
                    }
                } else {
                    $url = $_SERVER['DOCUMENT_ROOT'] . $m[1];
                }
                list($width, $height, $type, $attr) = @getimagesize($url);
                if (!isset($width)) {
                    $width = '1200';
                }
                if (!isset($height)) {
                    $height = '800';
                }
                return "<img width=\"{$width}\" height=\"{$height}\" src=\"{$m[1]}\"";
            },
            $html
        );

        return $html;
    }

    /**
     * @param $text
     * @return string|string[]
     * author:Holmesian
     * date: 2020/3/13 11:39
     * 闭合img标签  修复标签数组可能不存在的问题
     */
    private function closeTags($text)
    {
        preg_match_all('/<img ([\s\S]*?)>/', $text, $mat);
        $src = array_unique($mat[0]);
        for ($i = 0; $i < count($src); $i++) {
            if (isset($src[$i])) {
                $plus = $src[$i] . '</img>';
                $text = str_replace($mat[0][$i], $plus, $text);
            }
        }
        return $text;
    }


    /**
     * @param string $maptype
     * author:Holmesian
     * date: 2020/3/13 11:40
     * 生成SiteMap
     */
    private function MakeSiteMap($maptype = 'amp')
    {
        //changefreq -> always、hourly、daily、weekly、monthly、yearly、never
        //priority -> 0.0优先级最低、1.0最高
        $root_url = Helper::options()->rootUrl;
        if (isset($_GET['page'])) {//Sitemap分页

            $page = $_GET['page'];
        } else {
            $page = 1;
        }
        if (isset($_GET['txt'])) {//增加纯文本地址列表
            $articles = $this->MakeArticleList($maptype, $page, 1000);
            foreach ($articles as $article) {
                echo $article['permalink'] . "\n\r<br>";
            }
        } else {
            header("Content-Type: application/xml");
            echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            echo "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";
            echo "\t<url>\n";
            echo "\t\t<loc>{$root_url}</loc>\n";
            echo "\t\t<lastmod>" . date('Y-m-d') . "</lastmod>\n";
            echo "\t\t<changefreq>daily</changefreq>\n";
            echo "\t\t<priority>1</priority>\n";
            echo "\t</url>\n";
            $articles = $this->MakeArticleList($maptype, $page, 1000);
            foreach ($articles as $article) {
                echo "\t<url>\n";
                echo "\t\t<loc>" . $article['permalink'] . "</loc>\n";
                echo "\t\t<lastmod>" . date('Y-m-d', $article['modified']) . "</lastmod>\n";
                echo "\t\t<changefreq>monthly</changefreq>\n";
                echo "\t\t<priority>0.5</priority>\n";
                echo "\t</url>\n";
            }
            echo "</urlset>";
        }

    }


    /**
     * 截取功能函数
     * @param string $text 截取的对象
     * @param string $length 保留长度
     * @param string $replace 替换结尾表示
     * @param string $encoding 编码类型
     * @return mixed
     */

    private function substrFormat($text, $length, $replace = '...', $encoding = 'UTF-8')
    {
        if ($text && mb_strlen($text, $encoding) > $length) {
            return mb_substr($text, 0, $length, $encoding) . $replace;
        }
        return $text;
    }

    /**
     * 清理指定HTML标签函数
     * @param array $tags 删除的标签 array('font','color','input','size')
     * @param string $str html字符串
     * @param bool $type 是否保留标签的内容
     * @return mixed
     */
    private function stripHtmlTags($tags, $str, $content = false)
    {
        $html = [];
        if ($content) {
            foreach ($tags as $tag) {
                $html[] = "/(<(?:\/" . $tag . "|" . $tag . ")[^>]*>)/is";
            }
        } else {
            foreach ($tags as $tag) {
                $html[] = '/<' . $tag . '.*?>[\s|\S]*?<\/' . $tag . '>/is';
                $html[] = '/<' . $tag . '.*?>/is';
            }
        }
        $data = preg_replace($html, '', $str);
        return $data;
    }


    /**
     * 根据自定义文章路径生成amp/mip的地址规则
     * @return mixed
     */
    private function getUrlRule()
    {
        //获取自定义文章路径的最后一层
        $router = explode('/', Helper::options()->routingTable['post']['url']);
        $slugtemp = $router[count($router) - 1];
        if (empty($slugtemp)) {
            $slugtemp = $router[count($router) - 2];
        }

        //清理自定义格式
        $URLarr = explode('.', $slugtemp);
        $target = '';
        foreach ($URLarr as $x) {//寻找别名标记
            if (strstr($x, '[slug]')) {
                $target = '[slug]';
            }
        }
        if ($target !== '[slug]') {//没找到别名标记则用文章序号
            $target = '[cid:digital]';
        }

        //根据后缀名情况拼接文章路径
        if (count($URLarr) > 1) {
            $slugtemp = $target . '.' . $URLarr[count($URLarr) - 1];
        } else {
            $slugtemp = $target;
        }

        return $slugtemp;
    }


    /**
     * 清理文章摘要内容
     * @param array $desc 文章内容
     * @return mixed
     */
    private static function cleanUp($desc)
    {
        $desc = str_replace(array("\r\n", "\r", "\n"), "", strip_tags($desc));//获取纯内容后去除换行
        $desc = mb_substr($desc, 0, 150) . '...';//截取前150个字符
        $desc = str_replace('"', '\"', $desc);//转义传递给json的 "
        return $desc;
    }

    //------------页面缓存功能函数start------------

    /**
     * @param $key
     * @param $cache
     * @return |null
     * @throws Typecho_Plugin_Exception
     * author:Holmesian
     * date: 2020/3/13 11:40
     */
    private function set($key, $cache)
    {
        if (Helper::options()->plugin('AMP')->cacheTime > 0) {
            $installDb = $this->db;
            $time = (int)Helper::options()->plugin('AMP')->cacheTime;
            $expire = $time * 60 * 60;
            if (is_array($cache)) $cache = json_encode($cache);
//            $table = $this->tablename;
            $time = time();

//            $cache = addslashes($cache);
//            $sql = "REPLACE INTO $table  (`hash`,`cache`,`dateline`,`expire`) VALUES ('$key','$cache','$time','$expire')";
//            $installDb->query($sql);

            $installDb->query($installDb->insert($this->tablename)->rows(array("hash" => $key, "cache" => $cache, "dateline" => $time, "expire" => $expire)));//更换写入方法

        } else {
            return null;
        }
    }

    private function del($key)
    {
        if (Helper::options()->plugin('AMP')->cacheTime > 0) {
            $installDb = $this->db;
            if (is_array($key)) {
                foreach ($key as $k => $v) {
                    $this->del($v);
                }
            } else {
                if ($key == '*') {
                    $installDb->query($installDb->delete($this->tablename)->where("1=1"));
                } else {
                    $installDb->query($installDb->delete($this->tablename)->where('hash = ?', $key)->limit(1));
                }
            }
        } else {
            return null;
        }
    }

    private function get($key)
    {
        if (Helper::options()->plugin('AMP')->cacheTime > 0) {
            $installDb = $this->db;

            $condition = $installDb->select('cache', 'dateline', 'expire')->from($this->tablename)->where('hash = ?', $key);
            $row = $installDb->fetchRow($condition);
            if (!$row) return;
            if (time() - $row['dateline'] > $row['expire']) $this->del($key);
            $cache = $row['cache'];
            $arr = json_decode($cache, true);
            return is_array($arr) ? $arr : $cache;
        } else {
            return null;
        }
    }

    //------------页面缓存功能函数end------------


}

?>