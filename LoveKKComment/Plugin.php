<?php
/**
 * SendCloud评论邮件通知插件 for Typecho
 *
 * @package LoveKKComment
 * @author  康粑粑
 * @version 1.0.2
 * @link    https://www.lovekk.org
 */

if ( !defined('__TYPECHO_ROOT_DIR__') )
    exit;
// API请求地址
define('API', 'http://api.sendcloud.net/apiv2/');
// 当前版本号
define('VERSION', '1.0.2');

class LoveKKComment_Plugin implements Typecho_Plugin_Interface
{
    /**
     * Notes: 插件激活方法
     *
     * @static
     * @access public
     * @date   03/25/2018 13:48:59
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        // 检查curl
        if ( !function_exists('curl_init') ) {
            throw new Typecho_Plugin_Exception(_t('对不起，使用此插件必须支持curl'));
        }
        // 添加绑定
        Typecho_Plugin::factory('Widget_Feedback')->finishComment      = array(__CLASS__, 'sendMail');
        Typecho_Plugin::factory('Widget_Comments_Edit')->finishComment = array(__CLASS__, 'sendMail');
        Typecho_Plugin::factory('Widget_Comments_Edit')->mark          = array(__CLASS__, 'approvedMail');
        // 添加创建模板路由
        Helper::addRoute('lovekkcomment_create_template', '/lovekkcomment/create', 'LoveKKComment_Action', 'createTemplate');
    }

    /**
     * Notes: 插件禁用方法
     *
     * @static
     * @access public
     * @date   03/25/2018 21:22:59
     * @return void
     */
    public static function deactivate()
    {
        // 删除创建模板路由
        Helper::removeRoute('lovekkcomment_create_template');
    }

    /**
     * Notes: 插件配置面板
     *
     * @static
     * @access public
     * @date   03/25/2018 22:44:12
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        ?>
        <style>.message {
                padding: 10px;
                background-color: #fff;
                box-shadow: 2px 2px 5px #888;
                font-size: 1pc;
                line-height: 1.875rem
            }

            .message span {
                display: block;
                color: #1abc9c
            }

            .message span pre {
                margin: 0;
                padding: 0;
                color: #ee5c42
            }

            .message li, .message p {
                margin: 0;
                padding: 0;
                line-height: 1.5rem
            }</style>
        <div class="message">
            <div id="update_txt">当前版本: <?php _e(VERSION); ?>, 正在检测版本更新...</div>
            <span id="update_notice"></span>
            <span id="update_body"></span>
        </div>
        <script src="//cdn.bootcss.com/jquery/3.3.1/jquery.min.js"></script>
        <script src="//cdn.bootcss.com/marked/0.3.12/marked.min.js"></script>
        <script>
            $(function () {
                $.getJSON(
                    'https://git.wskehao.com/api/v1/repos/ylqjgm/LoveKKComment/releases',
                    function (data) {
                        if (checkUpdater('<?php _e(VERSION);?>', data[0].tag_name)) {
                            $('#update_notice').html('有新版本可用, <a href="' + data[0].zipball_url + '" target="_blank">点此下载 ' + data[0].tag_name + ' 版本</a>');
                            $('#update_body').html('版本说明: ' + marked(data[0].body));
                        } else {
                            $('#update_txt').html('当前版本: <?php _e(VERSION);?>, 当前没有新版本');
                        }
                    }
                );
            });

            // 版本比较
            function checkUpdater(currVer, remoteVer) {
                currVer = currVer || '0.0.0';
                remoteVer = remoteVer || '0.0.0';
                if (currVer == remoteVer) return false;
                var currVerAry = currVer.split('.');
                var remoteVerAry = remoteVer.split('.');
                var len = Math.max(currVerAry.length, remoteVerAry.length);
                for (var i = 0; i < len; i++) {
                    if (~~remoteVerAry[i] > ~~currVerAry[i]) return true;
                }

                return false;
            }
        </script>
        <?php
        // 初始化Typecho_Db
        $db = Typecho_Db::get();
        // 查询插件配置
        $registered = $db->fetchRow($db->select()->from('table.options')->where('name = ?', 'plugin:LoveKKComment'));
        // 获取插件配置
        $plugin = $registered ? Helper::options()->plugin('LoveKKComment') : null;
        // 如果已经配置了apiUser和apiKey则显示模板创建按钮
        if ( isset($plugin->api_user) && isset($plugin->api_key) ) {
            ?>
            <div class="message">
                <div>
                    <button class="btn primary" id="create">一键创建通知模板</button>
                </div>
                <div id="create_notice"></div>
            </div>
            <script>
                $(function () {
                    $('#create').click(function () {
                        $.getJSON('/lovekkcomment/create', function (data) {
                            if (data.errno == 0) {
                                $('#create_notice').html('通知模板创建成功！');
                            } else {
                                $('#create_notice').html(data.message);
                            }
                        });
                    });
                });
            </script>
            <?php
        }
        // API_USER
        $api_user = new Typecho_Widget_Helper_Form_Element_Text('api_user', null, null, _t('SendCloud发信API USER'), _t('请填入在SendCloud生成的API_USER'));
        $form->addInput($api_user);
        // API_KEY
        $api_key = new Typecho_Widget_Helper_Form_Element_Text('api_key', null, null, _t('SendCloud发信API KEY'), _t('请填入在SendCloud生成的API_KEY'));
        $form->addInput($api_key);
        // 发件人信箱
        $send_from = new Typecho_Widget_Helper_Form_Element_Text('send_from', null, null, _t('发件人邮件地址'), _t('请尽量保证与SendCloud中配置的发信域名一致'));
        $form->addInput($send_from);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * Notes: 发送回复通知
     *
     * @static
     * @access public
     * @date   03/25/2018 21:14:36
     * @param mixed $comment
     * @return void
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     */
    public static function sendMail($comment)
    {
        // 初始化系统配置对象
        $options = Helper::options();
        // 获取插件配置
        $plugin = $options->plugin('LoveKKComment');
        //  不是贴主
        if ( $comment->authorId != $comment->ownerId ) {
            // 获取作者信息
            $author = self::getWidget('Users', 'uid', $comment->ownerId);
            // 扩展字段
            $xsmtpapi = json_encode(
                array(
                    'to'  => array($author->mail), // 收件人
                    'sub' => array(
                        '%blogname%'  => array(trim($options->title)), // 博客名称
                        '%blogurl%'   => array(trim($options->siteUrl)), // 博客地址
                        '%author%'    => array(trim($comment->author)), // 收件人名称
                        '%permalink%' => array(trim($comment->permalink)), // 静态连接
                        '%title%'     => array(trim($comment->title)), // 文章标题
                        '%text%'      => array(trim($comment->text)) // 评论内容
                    )
                )
            );
            // 请求参数
            $param = array(
                'apiUser'            => $plugin->api_user, // apiUser
                'apiKey'             => $plugin->api_key, // apiKey
                'from'               => $plugin->send_from, // 发件邮件
                'fromName'           => $options->title, // 发件人名称
                'subject'            => '您在 [' . $options->title . ']  发表的文章有新评论！', // 邮件标题
                'xsmtpapi'           => $xsmtpapi, // 扩展字段
                'templateInvokeName' => 'LoveKKComment_Author_Template' // 审核通知模板
            );
            // 请求地址
            $uri = API . 'mail/sendtemplate';
            // 提交请求
            $result = self::send($uri, $param);
        }
        if ( 0 < $comment->parent ) { // 如果是评论回复则进行下面的操作
            // 获取上级评论对象
            $parentComment = self::getWidget('Comments', 'coid', $comment->parent);
            // 检测数据是否获取到且回复用户不是本用户
            if ( isset($parentComment->coid) && $comment->authorId != $parentComment->authorId ) {
                // 获取文章对象
                $post = self::getWidget('Contents', 'cid', $parentComment->cid);
                // SendCloud请求扩展字段
                $xsmtpapi = json_encode(
                    array(
                        'to'  => array($parentComment->mail), // 收件人
                        'sub' => array(
                            '%blogname%'   => array(trim($options->title)), // 博客名称
                            '%blogurl%'    => array(trim($options->siteUrl)), // 博客地址
                            '%author%'     => array(trim($parentComment->author)), // 收件人名称
                            '%permalink%'  => array(trim($post->permalink)), // 文章静态连接
                            '%title%'      => array(trim($post->title)), // 文章名称
                            '%text%'       => array(trim($parentComment->text)), // 评论内容
                            '%author2%'    => array(trim($comment->author)), // 回复者名称
                            '%text2%'      => array(trim($comment->text)), // 回复者内容
                            '%commenturl%' => array(trim($comment->permalink)) // 回复地址
                        )
                    )
                );
                // 请求参数
                $param = array(
                    'apiUser'            => $plugin->api_user, // apiUser
                    'apiKey'             => $plugin->api_key, // apiKey
                    'from'               => $plugin->send_from, // 发件邮件
                    'fromName'           => $options->title, // 发件人名称
                    'subject'            => '您在 [' . $options->title . '] 的评论有了新的回复！', // 邮件标题
                    'xsmtpapi'           => $xsmtpapi, // 扩展字段
                    'templateInvokeName' => 'LoveKKComment_Reply_Template' // 回复通知模板
                );
                // 请求地址
                $uri = API . 'mail/sendtemplate';
                // 提交请求
                $result = self::send($uri, $param);
            }
        }
    }

    /**
     * Notes: 审核通过邮件通知
     *
     * @static
     * @access public
     * @date   03/25/2018 22:31:20
     * @param $comment
     * @param $edit
     * @param $status
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function approvedMail($comment, $edit, $status)
    {
        // 只有在标记为展现的时候才发送邮件
        if ( 'approved' === $status ) {
            // 初始化系统配置对象
            $options = Helper::options();
            // 获取插件配置
            $plugin = $options->plugin('LoveKKComment');
            // 扩展字段
            $xsmtpapi = json_encode(
                array(
                    'to'  => array($edit->mail), // 收件人
                    'sub' => array(
                        '%blogname%'  => array(trim($options->title)), // 博客名称
                        '%blogurl%'   => array(trim($options->siteUrl)), // 博客地址
                        '%author%'    => array(trim($edit->author)), // 收件人名称
                        '%permalink%' => array(trim($edit->permalink)), // 静态连接
                        '%title%'     => array(trim($edit->title)), // 文章标题
                        '%text%'      => array(trim($edit->text)) // 评论内容
                    )
                )
            );
            // 请求参数
            $param = array(
                'apiUser'            => $plugin->api_user, // apiUser
                'apiKey'             => $plugin->api_key, // apiKey
                'from'               => $plugin->send_from, // 发件邮件
                'fromName'           => $options->title, // 发件人名称
                'subject'            => '您在 [' . $options->title . '] 的评论已通过审核！', // 邮件标题
                'xsmtpapi'           => $xsmtpapi, // 扩展字段
                'templateInvokeName' => 'LoveKKComment_Approved_Template' // 审核通知模板
            );
            // 请求地址
            $uri = API . 'mail/sendtemplate';
            // 提交请求
            self::send($uri, $param);
        }
    }

    /**
     * Notes: 获取Widget对象
     *
     * @static
     * @access private
     * @date   03/28/2018 21:27:39
     * @param string $name
     * @param string $key
     * @param mixed $val
     * @return mixed
     * @throws Typecho_Db_Exception
     */
    private static function getWidget($name, $key, $val)
    {
        // Widget类名
        $className = 'Widget_Abstract_' . $name;
        // 初始化Widget
        $widget = new $className(new Typecho_Request(), new Typecho_Response(), null);
        // 初始化Typecho_Db
        $db = Typecho_Db::get();
        // 定义查询
        $select = $widget->select()->where($key . ' = ?', $val)->limit(1);
        // 查询并过滤
        $db->fetchRow($select, array($widget, 'push'));
        return $widget;
    }

    /**
     * Notes: 远程请求方法
     *
     * @static
     * @access private
     * @date   03/25/2018 21:12:28
     * @param string $uri
     * @param array $data
     * @return mixed
     */
    private static function send($uri, $data)
    {
        // 初始化curl
        $ch = curl_init();
        // 请求地址
        curl_setopt($ch, CURLOPT_URL, $uri);
        // 返回数据
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 提交方式
        curl_setopt($ch, CURLOPT_POST, 1);
        // 提交数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        // 执行提交
        $result = curl_exec($ch);
        // 关闭curl
        curl_close($ch);
        return $result;
    }
}