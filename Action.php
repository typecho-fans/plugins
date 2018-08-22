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
        $this->tablename = $this->db->getPrefix().'PageCache';
        $this->baseurl = Helper::options()->index;
        $this->baseurl = str_replace("https://", "//", $this->baseurl);
        $this->baseurl = str_replace("http://", "//", $this->baseurl);
    }


    public static function headlink()
    {
        $widget = Typecho_Widget::widget('Widget_Archive');

        $ampurl = $mipurl = '';

        if ($widget->is('index') and !isset($widget->request->page)) {
            if (Helper::options()->plugin('AMP')->ampIndex == 1) {
                $fullURL = Typecho_Common::url("ampindex", Helper::options()->index);
                $ampurl = "\n<link rel=\"amphtml\" href=\"{$fullURL}\">\n";
            }
        }

        if ($widget->is('post')) {
            if(isset($widget->request->cid)){
                $target=$widget->request->cid;
            }
            if(isset($widget->request->slug)){
                $target=$widget->request->slug;
            }

            if(isset($target)){
                $fullURL = Typecho_Common::url("amp/{$target}", Helper::options()->index);
                $ampurl = "\n<link rel=\"amphtml\" href=\"{$fullURL}\">\n";
                $fullURL = Typecho_Common::url("mip/{$target}", Helper::options()->index);
                $mipurl = "<link rel=\"miphtml\" href=\"{$fullURL}\">\n";
            }
        }
        $headurl = $ampurl . $mipurl;

        echo $headurl;
    }


    public function ampsitemap()
    {

        if (Helper::options()->plugin('AMP')->ampSiteMap == 0) {
            throw new Typecho_Widget_Exception('未开启ampSiteMap功能！');
        }

        $this->MakeSiteMap('amp');

    }

    public function mipsitemap()
    {

        if (Helper::options()->plugin('AMP')->mipSiteMap == 0) {
            throw new Typecho_Widget_Exception('未开启mipSiteMap功能！');
        }

        $this->MakeSiteMap('mip');

    }

    public function MIPpage()
    {
        $requestHash = $this->request->getPathinfo();
        $context=$this->get($requestHash); //查找是否已经缓存
        $this->article = $this->getArticle($this->request->target);

        if (isset($this->article['isblank'])) {
            throw new Typecho_Widget_Exception('不存在或已删除');
        }
        if (Helper::options()->plugin('AMP')->OnlyForSpiders == 1){//判断是否是对应的爬虫来访
            $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
            $spider = strtolower('Baiduspider');
            if (strpos($userAgent, $spider) == false) {//不是百度的蜘蛛
                header("Location: {$this->article['permalink']}");
            }
        }


        if(!is_null($context)){//有缓存的情况直接输出
            print($context);
        }else{//没缓存的生成页面再进行缓存
            $MIPpage=array(
                'title'=>$this->article['title'],
                'permalink'=>$this->article['permalink'],
                'mipurl'=>$this->article['mipurl'],
                'modified'=>date('Y-m-d\TH:i:s',$this->article['modified']),
                'date'=>$this->article['date']->format('Y-m-d\TH:i:s'),
                'isMarkdown'=>$this->article['isMarkdown'],
                'imgData'=>$this->GetPostImg(),//MIP页面的结果化数据可以没有图片
                'APPID'=>Helper::options()->plugin('AMP')->baiduAPPID,
                'mip_stats_token'=>Helper::options()->plugin('AMP')->mip_stats_token,
                'desc'=>self::cleanUp($this->article['text']),
                'publisher'=>Helper::options()->title,
                'MIPtext'=>$this->MIPInit($this->article['text']),
                'version'=>$this->version
            );
            ob_start();
            require_once('templates/MIPpage.php');
            $cache = ob_get_contents();
            $this->set($requestHash,$cache);
        }
    }


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

    public function AMPindex(){

        if (Helper::options()->plugin('AMP')->ampIndex == 0) {
            header("Location: {$this->baseurl}");
        }
        require_once ('templates/AMPindex.php');
    }

    public function AMPpage()
    {

        $requestHash = $this->request->getPathinfo();
        $context=$this->get($requestHash); //查找是否已经缓存

        $this->article = $this->getArticle($this->request->target);
        if (isset($this->article['isblank'])) {
            throw new Typecho_Widget_Exception('不存在或已删除');
        }
        if (Helper::options()->plugin('AMP')->OnlyForSpiders == 1){//判断是否是对应的爬虫来访
            $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
            $spider = strtolower('Googlebot');
            $spider2 = strtolower('google-amphtml');
            if (strpos($userAgent, $spider) == false  or strpos($userAgent, $spider2) == false) {//不是Google的蜘蛛
                header("Location: {$this->article['permalink']}");
            }
        }

        if(!is_null($context)){//有缓存的情况直接输出
            print($context);
        }
        else{
            $AMPpage=array(
                'title'=>$this->article['title'],
                'permalink'=>$this->article['permalink'],
                'mipurl'=>$this->article['mipurl'],
                'modified'=>date('F j, Y',$this->article['modified']),
                'date'=>$this->article['date']->format('F j, Y'),
                'author'=>$this->article['author'],
                'LOGO'=>$this->LOGO,
                'isMarkdown'=>$this->article['isMarkdown'],
                'imgData'=>$this->GetPostImg(),
                'APPID'=>Helper::options()->plugin('AMP')->baiduAPPID,
                'desc'=>self::cleanUp($this->article['text']),
                'publisher'=>Helper::options()->title,
                'AMPtext'=>$this->AMPInit($this->article['text']),
                'version'=>$this->version
            );
            //MIP页面的结果化数据必须有图片
            if(!is_array($AMPpage['imgData'])){
                $AMPpage['imgData']=self::getSizeArr($AMPpage['LOGO'],'200','200');//如果找不到图片就用LOGO
            }
            ob_start();
            require_once ('templates/AMPpage.php');
            $cache = ob_get_contents();
            $this->set($requestHash,$cache);
        }
    }

    public function cleancache(){
        $user = Typecho_Widget::widget('Widget_User');
        if(!$user->pass('administrator')){
            die('未登录用户!');
        }
        $this->del('*');
        print('Clean all cache!');
    }

    public static function sendRealtime($contents, $class)
    {
        //获取系统配置
        $options = Helper::options();



        //如果文章属性为隐藏或滞后发布
        if ('publish' != $contents['visibility'] || $contents['created'] > time()) {
            return;
        }

        //如果没有开启自动提交功能
        if ($options->plugin('AMP')->mipAutoSubmit == 0) {
            return;
        }


        //判断是否配置相关信息
        if (is_null($options->plugin('AMP')->baiduAPPID) or is_null($options->plugin('AMP')->baiduTOKEN)) {
            throw new Typecho_Plugin_Exception(_t('参数未正确配置，自动提交失败'));
        }else{
            $appid = $options->plugin('AMP')->baiduAPPID;
            $token = $options->plugin('AMP')->baiduTOKEN;
            $api = "http://data.zz.baidu.com/urls?appid={$appid}&token={$token}&type=realtime";//构建实时提交的地址
        }

        $article = Typecho_Widget::widget('AMP_Action')->getArticleByCid($class->cid);//根据cid获取文章内容



        if($article['created'] !== $article['modified'] ){//修改时间 与 创建时间 不同 为修改文章
            return;//修改文章不发送
        }


//        $urls = array($article['mipurl'],);//改为仅提交一次
        $url=$article['mipurl'];


        $hash = array(//发布之前清除对应的MIP/AMP缓存
            'mip' => str_replace(Helper::options()->index, "", $article['mipurl']),
            'amp' => str_replace(Helper::options()->index, "", $article['ampurl'])
        );
        Typecho_Widget::widget('AMP_Action')->del($hash);

        //发送自动提交请求
        $http = Typecho_Http_Client::get();
//        $http->setData(implode("\n", $urls));//改为仅提交一次
        $http->setData($url);
        $http->setHeader('Content-Type', 'text/plain');



        try {

            $result = $http->send($api);

//            error_log($result);

            $json = json_decode($result);

            if(isset($json->error)){//提交出错时返回错误信息
                throw new Typecho_Plugin_Exception(_t("错误代码：".$json->error."<br> 出错原因：".$json->message),$json->error);
            }

        } catch (Exception $e) {
            throw new Typecho_Plugin_Exception(_t('抱歉，自动提交失败。<br>请关闭自动提交功能！<br><hr>' . $e->getMessage()));
        }

    }

    public function getArticle($target)
    {
        $tempTarget = explode('.', $target)[0];
        $article = $this->getArticleBySlug($tempTarget);
        if (isset($article['isblank'])) {
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
            if($article['isMarkdown']==True){
                $article['text'] = Markdown::convert($article['text']);
            }else{
                $article['text'] = Typecho_Widget::widget("Widget_Abstract_Contents")->autoP($article['text']);
            }
            $targetTemp = $this->getSlugRule();
            $target = str_replace('[slug]', $article['slug'], $targetTemp);
            $target = str_replace('[cid:digital]', $article['cid'], $target);

            $article['mipurl'] = Typecho_Common::url("mip/{$target}", Helper::options()->index);;
            $article['ampurl'] = Typecho_Common::url("amp/{$target}", Helper::options()->index);;
        } else {
            $article = array(
                'isMarkdown' => false,
                'isblank' => true,
            );
        }
        return $article;
    }


    public function MakeArticleList($linkType = 'amp', $page = 0, $pageSize = 0)
    {
        $db = Typecho_Db::get();
        $sql = $db->select()->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', 'post')
            ->where('table.contents.created <= unix_timestamp(now())', 'post') //Fix 避免未达到时间的文章提前曝光
            ->where('table.contents.password IS NULL') //Fix 避免加密文章泄露
            ->order('table.contents.created', Typecho_Db::SORT_DESC);
        if ($page > 0 and $pageSize > 0) {
            $countSql = clone $sql;
            $this->_total = Typecho_Widget::widget('Widget_Abstract_Contents')->size($countSql);
            $sql = $sql->page($page, $pageSize);
        }
        $articles = $db->fetchAll($sql);
        $targetTemp = $this->getSlugRule();
        $articleList = array();

        foreach ($articles AS $article) {
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
            $attsrc=Typecho_Widget::widget('Widget_Contents_Attachment_Related', 'parentId=' . $this->article['cid'])->stack;
            $att='';
            foreach ($attsrc as $attimg){
                $att=$att.$attimg['text'];
            }
            if (preg_match($pattern, $att, $img)) {//附件里只需要匹配img标签的内容
                preg_match("/(?:\()(.*)(?:\))/i", $img[0], $result);
                $img_url = $img[1];
            }else{//附件里再找不到就调LOGO了
//                $img_url = $this->defaultPIC;
                $img_url=null;//熊掌号修改了规则，如果图不对文的话会被惩罚，所以没有图片则不出图
            }
        }

        if(is_null($img_url)){//如果没有找到图片则返回空
            return null;
        } else {//如果找到文章图片则返回图片数组
            return self::getSizeArr($img_url);
        }
    }

    private static function getSizeArr($img_url,$width='700',$height='400'){
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

    private function MIPInit($text)
    {
        $text = $this->IMGsize($text);
        $text = $this->closeTags($text);

        $text = str_replace('<img', '<mip-img  layout="responsive" popup ', $text);
        $text = str_replace('img>', 'mip-img>', $text);
        $text = str_replace('<!- toc end ->', '', $text);
        $text = str_replace('<style', '<style mip-custom" ', $text);
        $text = str_replace('javascript:content_index_toggleToc()', '#', $text);
        return $text;
    }

    private function AMPInit($text)
    {
        $text = $this->IMGsize($text);
        $text = $this->closeTags($text);

        $text = str_replace('<img', '<amp-img  layout="responsive" ', $text);
        $text = str_replace('img>', 'amp-img>', $text);
        $text = str_replace('<style', '<style amp-custom" ', $text);
        $text = str_replace('<!- toc end ->', '', $text);
        $text = str_replace('javascript:content_index_toggleToc()', '#', $text);
        return $text;
    }

    private function closeTags($text)
    {
        preg_match_all('/<img ([\s\S]*?)>/', $text, $mat);
        $src=array_unique($mat[0]);
        for ($i = 0; $i < count($src); $i++)
        {
            $plus =  $src[$i].'</img>';
            $text = str_replace( $mat[0][$i],$plus, $text);
        }
        return $text;
    }

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
                    $width = '500';
                }
                if (!isset($height)) {
                    $height = '700';
                }
                return "<img width=\"{$width}\" height=\"{$height}\" src=\"{$m[1]}\"";
            },
            $html
        );
        return $html;
    }

    private function MakeSiteMap($maptype = 'amp')
    {
        //changefreq -> always、hourly、daily、weekly、monthly、yearly、never
        //priority -> 0.0优先级最低、1.0最高
        $root_url = Helper::options()->rootUrl;
        if (isset($_GET['txt'])) {//增加纯文本地址列表
            $articles = $this->MakeArticleList($maptype);
            foreach ($articles AS $article) {
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
            $articles = $this->MakeArticleList($maptype);
            foreach ($articles AS $article) {
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


    private function substrFormat($text, $length, $replace = '...', $encoding = 'UTF-8')
    {
        if ($text && mb_strlen($text, $encoding) > $length) {
            return mb_substr($text, 0, $length, $encoding) . $replace;
        }
        return $text;
    }


    private function getSlugRule()
    {
        $router = explode('/', Helper::options()->routingTable['post']['url']);
        $slugtemp = $router[count($router) - 1];
        if (empty($slugtemp)) {
            $slugtemp = $router[count($router) - 2];
        }
        return $slugtemp;
    }

    //For page_cacher

    private function set($key, $cache){
        if(Helper::options()->plugin('AMP')->cacheTime>0) {
            $installDb = $this->db;
            $time = (int)Helper::options()->plugin('AMP')->cacheTime;
            $expire = $time * 60 * 60;
            if (is_array($cache)) $cache = json_encode($cache);
            $table = $this->tablename;
            $time = time();

            $cache = addslashes($cache);
            $sql = "REPLACE INTO $table  (`hash`,`cache`,`dateline`,`expire`) VALUES ('$key','$cache','$time','$expire')";
            $installDb->query($sql);
        }else{
            return null;
        }
    }

    private function del($key){
        if(Helper::options()->plugin('AMP')->cacheTime>0) {
            $installDb = $this->db;
            $tablename = $this->tablename;
            if (is_array($key)) {
                foreach ($key as $k => $v) {
                    $this->del($v);
                }
            } else {
                if ($key == '*') {
                    $installDb->query("DELETE FROM $tablename WHERE 1=1 ");
                } else {
                    $delete = $installDb->delete($tablename)->where('hash = ?', $key)->limit(1);
                    $installDb->query($delete);
                }
            }
        }else{
            return null;
        }
    }

    private function get($key){
        if(Helper::options()->plugin('AMP')->cacheTime>0) {
            $installDb = $this->db;
            $tablename = $this->tablename;

            $condition = $installDb->select('cache', 'dateline', 'expire')->from($tablename)->where('hash = ?', $key);
            $row = $installDb->fetchRow($condition);
            if (!$row) return;
            if (time() - $row['dateline'] > $row['expire']) $this->del($key);
            $cache = $row['cache'];
            $arr = json_decode($cache, true);
            return is_array($arr) ? $arr : $cache;
        }else{
            return null;
        }
    }

    private static function cleanUp($desc){
        $desc= str_replace(array("/r/n", "/r", "/n"), "", strip_tags($desc));
        $desc = str_replace(PHP_EOL, '', $desc);
        $desc=mb_substr($desc, 0, 150).'...';
        return $desc;
    }


}

?>