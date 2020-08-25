<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * CSDN博客导入插件
 * @author MDY
 * @package CSDN
 * @version 1.0.0
 * @license GNU General Public License 2.0
 */
class CSDN_Plugin extends Widget_Abstract_Contents implements Typecho_Plugin_Interface
{
    //爬虫的header
    protected static $context;
    //文章id的正则
    protected static $r_article_id = '/^https\:\/\/blog\.csdn\.net\/.+\/article\/details\//';
    //文章链接的正则
    protected static $r_article;
    //博客列表的地址
    protected static $source_url;
    //博客的地址
    protected static $article_url;

    /**
     * 启用插件方法,如果启用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     */
    public static function activate()
    {
        // TODO: Implement activate() method.

        Typecho_Plugin::factory('admin/menu.php')->navBar = array('CSDN_Plugin', 'render');
        Typecho_Plugin::factory('admin/menu.php')->navBar = array('CSDN_Plugin', 'header');
        Helper::addRoute('get-articles-id', '/get-articles-id', 'CSDN_Plugin', 'get_articles_id');
        Helper::addRoute('add-article', '/add-article', 'CSDN_Plugin', 'add_article');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     */
    public static function deactivate()
    {
        // TODO: Implement deactivate() method.
        Helper::removeRoute('get-articles-id');
        Helper::removeRoute('add-article');
    }

    /**
     * 获取插件配置面板
     *
     * @static
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        // TODO: Implement config() method.
        $UserName = new Typecho_Widget_Helper_Form_Element_Text('UserName', NULL, '', _t('UserName(在浏览器开发者模式下csdn的网站找到这个cookie，下同)'));
        $UserToken = new Typecho_Widget_Helper_Form_Element_Text('UserToken', NULL, '', _t('UserToken'));
        $UserInfo = new Typecho_Widget_Helper_Form_Element_Text('UserInfo', NULL, '', _t('UserInfo'));
        $form->addInput($UserName);
        $form->addInput($UserToken);
        $form->addInput($UserInfo);
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // TODO: Implement personalConfig() method.
    }

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function render()
    {

        echo '<a href="#" onclick="add_post();return false;" style="color: #ff0007">点击导入博文'
            . '</a>';
    }


    /**
     * 添加文章
     */
    public function add_article()
    {
        header('Content-Type:text/json;charset=utf-8');
        //得到config的输入框的值
        $csdn = Helper::options()->plugin('CSDN');
        $username = $csdn->UserName;
        $usertoken = $csdn->UserToken;
        $userinfo = $csdn->UserInfo;
        //初始化cookie
        CSDN_Plugin::$context = CSDN_Plugin::init_header($username, $userinfo, $usertoken);
        $aid = $_POST['aid'];
        $date = $_POST['date'];
        try {
            $result = CSDN_Plugin::get_post_info($aid, $date);
            if (gettype($result) == 'array') {
                //数据库获取
                $db = Typecho_Db::get();
                $user = Typecho_Widget::widget('Widget_User');
                $user->execute();
                $userId = $user->uid;
                $result['post']['allowComment'] = "1";
                $result['post']['allowPing'] = "1";
                $result['post']['allowFeed'] = "1";
                $result['post']['authorId'] = $userId;
                $result['post']['slug'] = $result['post']['cid'];

                //文章是否存在
                $temp = $db->fetchRow($db->select('cid')
                    ->from('table.contents')
                    ->where('table.contents.cid = ?', $result['post']['cid'])->limit(1));
                if ($temp) {
                    $update = $db->update('table.contents')->rows($result['post'])->where('table.contents.cid = ?', $result['post']['cid'])->limit(1);
                    $db->query($update);
                } else {
                    $insert = $db->insert('table.contents')
                        ->rows($result['post']);
                    $db->query($insert);
                }
                //插入tag
                Widget_Abstract_Comments::widget('Widget_Contents_Post_Edit')->setTags($result["post"]["cid"], $result['tags']);
                //插入分类，得到分类id
                foreach ($result['categories'] as &$category) {
                    $t = $db->fetchRow($db->select('table.metas.mid')
                        ->from('table.metas')->where('name <=> ? and type <=> ?', $category, 'category')->limit(1));
                    if (!$t) {
                        $options['name'] = $category;
                        $options['slug'] = $category;
                        $options['type'] = 'category';
                        $options['order'] = 0;
                        $category = $db->query($db->insert('table.metas')->rows($options));
                    } else {
                        $category = $t['mid'];
                    }
                }
                Widget_Abstract_Comments::widget('Widget_Contents_Post_Edit')->setCategories($result["post"]["cid"], $result['categories']);
                $str = array('msg' => "success", 'code' => 1, 'data' => $aid);
            } else {
                if ($result != null) {
                    $str = array('msg' => $result . "(如果是没有操作权限，请查看是否为cookie错误或者过期)", 'code' => 0, 'data' => $aid);
                } else {
                    $str = array('msg' => "result is null", 'code' => 0, 'data' => $aid);
                    @error_log(json_encode($result));
                }
            }
        } catch (Error $e) {
            $str = array('msg' => $e->getMessage(), 'code' => 0, 'data' => $aid);
        } catch (Typecho_Exception $e) {
            $str = array('msg' => $e->getMessage(), 'code' => 0, 'data' => $aid);
        } catch (Exception $e) {
            $str = array('msg' => $e->getMessage(), 'code' => 0, 'data' => $aid);
        }
        //返回json数据
        $json = json_encode($str);
        echo $json;
    }

    /**
     * 添加文章id列表
     * @throws Typecho_Exception
     * @throws Typecho_Plugin_Exception
     */
    public function get_articles_id()
    {
        header('Content-Type:text/json;charset=utf-8');

        $user = Typecho_Widget::widget('Widget_User');
        if (!$user->pass('administrator', false)) {
            return;
        }
        $user->execute();

        //得到config的输入框的值
        $csdn = Helper::options()->plugin('CSDN');
        $username = $csdn->UserName;
        $usertoken = $csdn->UserToken;
        $userinfo = $csdn->UserInfo;
        if ($username == null || $usertoken == null || $userinfo == null) {
            $str = array('msg' => '请在设置界面填写所需参数', 'code' => 0);
            //返回json数据
            $jsonencode = json_encode($str);
            echo $jsonencode;
            return;
        }

        //初始化cookie
        CSDN_Plugin::$context = CSDN_Plugin::init_header($username, $userinfo, $usertoken);
        //得到文章链接的正则
        CSDN_Plugin::$r_article = '/https\:\/\/blog\.csdn\.net\/' . $username . '\/article\/details\/[0-9]+/';
        //得到文章id的正则
        CSDN_Plugin::$source_url = "https://blog.csdn.net/$username/phoenix/article/list/";
        CSDN_Plugin::$article_url = "https://blog.csdn.net/$username/article/details/";

        //博客页面页数
        $i = 1;
        $str = null;
        $a_list = array();

        do {
            $content = file_get_contents(CSDN_Plugin::$source_url . $i);
//            $json = CSDN_Plugin::decodeUnicode($content);
            $json = json_decode($content, true);

            if ($json['status'] == 1) {
                $article_list = $json['data']['article_list'];
                //取得页数
                $page_num = ceil($json['data']['total'] / 20);

                foreach ($article_list as $article) {
                    $a_list = array_merge($a_list, array(array($article['ArticleId'], $article['PostTime'])));
                }
                $i += 1;
            } else {
                $str = array('msg' => '用户不存在或者网络不好', 'code' => 0);
                break;
            }
        } while ($page_num >= $i);
        //返回博客id的列表
        if (!$str) {
            $str = array('data' => $a_list, 'code' => 1);
        }
        //返回json数据
        $jsonencode = json_encode($str);
        echo $jsonencode;
    }


    /**
     * 得到要输入数据库中的文章信息
     * @param $aid
     * @param $date
     * @return array 文章的信息
     */
    function get_post_info($aid, $date)
    {
        //得到日期
        $create_date = strtotime($date);
        //获取markdown格式的字符串
        $content = file_get_contents("https://blog-console-api.csdn.net/v1/editor/getArticle?id=$aid", false, CSDN_Plugin::$context);
        $json = json_decode($content, true);
        //解析
        if (strcmp($json["msg"], "success") == 0) {
            $description = $json["data"]["description"];
            $markdown = '<!--markdown-->' . $description . '<!--more-->' . $json["data"]["markdowncontent"];
            $title = $json["data"]["title"];
            $categories = $json['data']['categories'];
            str_replace('，', ',', $categories);
            $categories = array_unique(array_map('trim', explode(',', $categories)));
            $tags = $json['data']['tags'];
            $post = array('title' => $title, 'type' => 'post', 'cid' => $aid, 'text' => $markdown, 'created' => $create_date, 'modified' => time());
            return array('post' => $post, "tags" => $tags, "categories" => $categories, "description" => $description);
        } else {
            return $json['msg'];
        }
    }


    /**
     * 用到的js和css
     *
     * @access public
     * @return void
     */
    public
    static function header()
    {
        $Path = Helper::options()->pluginUrl . '/CSDN/';
        echo '<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />';
        echo '<link rel="stylesheet" type="text/css" href="' . $Path . 'css/my_bar.css"/>' . "\n\r";;
        echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/nanobar/0.4.2/nanobar.min.js" integrity="sha512-1Al+dnfE+1gI7IBmpUZ8XnZ3l3Nv6cyA+XgdtlaptVNxJcWWRzHxOPzT+2pbp52qtXa2jkwk0MWYSmxslMsHCQ==" crossorigin="anonymous"></script>' . "\n\r";
        echo '<script type="text/javascript" src="' . $Path . 'js/add_post.js"></script>' . "\n\r";
        echo '<script type="text/javascript" src="https://cdn.bootcss.com/jquery/3.3.1/jquery.min.js"></script>' . "\n\r";
        echo '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>' . "\n\r";
    }


    /**
     * 初始化header
     * @param $username
     * @param $userinfo
     * @param $usertoken
     * @return resource
     */
    static function init_header($username, $userinfo, $usertoken)
    {
        $cookie = "UserName=$username;UserInfo=$userinfo;UserToken=$usertoken;";
        $opts = array(
            'http' => array(
                'method' => "GET",
                'header' => "Accept-language: en\r\n" .
                    "Cookie:" . $cookie . "\r\n"
            )
        );
        return stream_context_create($opts);
    }


    /**
     * unicode解码
     * @param $str
     * @return null|string|string[]
     */
    static function decodeUnicode($str)
    {
        $callbacks[$str] = function ($matches) use ($str) {
            return iconv("UCS-2BE", "UTF-8", pack("H*", $matches[1]));
        };
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', $callbacks[$str], $str);
    }



//          失败
//    function publish($result)
//    {
//        //填充文章的相关字段信息。
//        $contents =
//            array(
//                'title' => $result["post"]["title"],
//                'text' => $result["post"]["title"],
//                'fieldNames' => array(),
//                'fieldTypes' => array(),
//                'fieldValues' => array(),
//                'cid' => '',
//                'do' => 'publish',
//                'markdown' => '1',
//                'date' => $result["post"]["data"],
//                'category' => array($result["post"]["categories"]),
//                'tags' => $result["post"]["tags"],
//                'visibility' => 'publish',
//                'password' => '',
//                'allowComment' => '1',
//                'allowPing' => '1',
//                'allowFeed' => '1',
//                'trackback' => '',
//            );
//
//        $request = Typecho_Request::getInstance();
//        //设置token，绕过安全限制
//        $security = Typecho_Widget::widget('Widget_Security');
//        $request->setParam('_', $security->getToken($this->request->getReferer()));
//        $request->setParams($contents);
//        //设置时区，否则文章的发布时间会查8H
//        date_default_timezone_set('PRC');
//
//        //执行添加文章操作
//        $widgetName = 'Widget_Contents_Post_Edit';
//        $reflectionWidget = new ReflectionClass($widgetName);
//        if ($reflectionWidget->implementsInterface('Widget_Interface_Do')) {
//            Typecho_Widget::widget($widgetName)->action();
//            return true;
//        } else {
//            return false;
//        }
//
//    }

}


