<?php
/**
 * 评论通知推送多服务
 *
 * @package CommentPush
 * @author 高彬展,奥秘Sir
 * @version 1.7.0
 * @link https://github.com/gaobinzhan/CommentPush
 * @blog https://blog.gaobinzhan.com
 */

require 'lib/QQService.php';
require 'lib/WeChatService.php';
require 'lib/AliYunEmailService.php';
require 'lib/SmtpService.php';
require 'lib/DingTalkBotService.php';
require 'lib/EnterpriseWeChatService.php';
require 'lib/OfficialAccountService.php';

class CommentPush_Plugin implements Typecho_Plugin_Interface
{
    protected static $comment;
    protected static $active;

    /**
     * @return string|void
     * @throws Typecho_Db_Exception
     */
    public static function activate()
    {
        self::addTable();
        Typecho_Plugin::factory('Widget_Feedback')->comment = [__CLASS__, 'pushServiceReady'];
        Typecho_Plugin::factory('Widget_Feedback')->finishComment = [__CLASS__, 'pushServiceGo'];

        Helper::addRoute('CommentPushAction','/CommentPush/officialAccount','CommentPush_Action','officialAccount');

        Helper::addPanel(1, 'CommentPush/Logs.php', 'CommentPush日志', 'CommentPush日志', 'administrator');
        return _t('CommentPush插件启用成功');
    }


    /**
     * @throws Typecho_Db_Exception
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removeRoute('CommentPushAction');
        Helper::removePanel(1, 'CommentPush/Logs.php');
        if (Helper::options()->plugin('CommentPush')->isDelete == 1) {
            self::removeTable();
        }
    }

    /**
     * @throws Typecho_Db_Exception
     */
    private static function addTable()
    {
        $db = Typecho_Db::get();

        $sql = self::getSql($db, 'install');

        $db->query($sql);
    }

    /**
     * @param $db
     * @param string $path
     * @return string|string[]
     */
    private static function getSql($db, $path = 'install')
    {
        $adapter = $db->getAdapterName();
        $prefix = $db->getPrefix();

        if ($adapter === 'Pdo_Mysql' || $adapter === 'Mysql' || $adapter === 'Mysqli') {
            $sqlTemplate = file_get_contents(__DIR__ . '/sql/' . $path . '/Mysql.sql');
        }

        if ($adapter === 'Pdo_SQLite') {
            $sqlTemplate = file_get_contents(__DIR__ . '/sql/' . $path . '/SQLite.sql');
        }

        if ($adapter === 'Pdo_Pgsql') {
            $sqlTemplate = file_get_contents(__DIR__ . '/sql/' . $path . '/Pgsql.sql');
        }

        if (empty($sqlTemplate)) throw new \Exception('暂不支持你的数据库');

        $sql = str_replace('{prefix}', $prefix, $sqlTemplate);
        return $sql;
    }

    /**
     * @return string
     * @throws Typecho_Db_Exception
     */
    private static function removeTable()
    {
        $db = Typecho_Db::get();
        $sql = self::getSql($db, 'uninstall');
        try {
            $db->query($sql, Typecho_Db::WRITE);
        } catch (Typecho_Exception $e) {
            return "删除CommentPush日志表失败！";
        }
        return "删除CommentPush日志表成功！";
    }

    /**
     * @param Typecho_Widget_Helper_Form $form
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $serviceTitle = new Typecho_Widget_Helper_Layout('div', array('class=' => 'typecho-page-title'));
        $serviceTitle->html('<h2>推送服务配置</h2>');
        $form->addItem($serviceTitle);

        $services = new Typecho_Widget_Helper_Form_Element_Checkbox('services', [
            "QQService" => _t('Qmsg酱'),
            "WeChatService" => _t('Server酱'),
            "AliYunEmailService" => _t('阿里云邮件'),
            "SmtpService" => _t('SMTP'),
            "DingTalkBotService" => _t('钉钉机器人'),
            "EnterpriseWeChatService" => _t('企业微信机器人'),
            "OfficialAccountService" => _t('微信公众号')
        ], 'services', _t('推送服务 多选同时推送'), _t('插件作者：<a href="https://blog.gaobinzhan.com">高彬展</a>&nbsp;<a href="https://blog.say521.cn/">奥秘Sir</a>'));
        $form->addInput($services->addRule('required', _t('必须选择一项推送服务')));

        $isPushBlogger = new Typecho_Widget_Helper_Form_Element_Radio('isPushBlogger', [
            1 => '是',
            0 => '否'
        ], 1, _t('当评论者为博主本人不推送'), _t('如果选择“是”，博主本人写的评论将不推送'));
        $form->addInput($isPushBlogger);

        $isPushCommentReply = new Typecho_Widget_Helper_Form_Element_Radio('isPushCommentReply', [
            1 => '是',
            0 => '否'
        ], 1, _t('当作者回复评论向对方发送邮件'), _t('如果选择“否”，将不推送'));
        $form->addInput($isPushCommentReply);

        $isDelete = new Typecho_Widget_Helper_Form_Element_Radio('isDelete', [0 => '不删除', 1 => '删除'], 1, _t('卸载是否删除数据表'));
        $form->addInput($isDelete);

        self::qqService($form);
        self::weChatService($form);
        self::aliYunMailService($form);
        self::smtpService($form);
        self::DingTalkBotService($form);
        self::EnterpriseWeChatService($form);
        self::officialAccount($form);


    }

    /**
     * Qmsg酱配置面板
     * @param Typecho_Widget_Helper_Form $form
     */
    private static function qqService(Typecho_Widget_Helper_Form $form)
    {
        $qqServiceTitle = new Typecho_Widget_Helper_Layout('div', ['class=' => 'typecho-page-title']);
        $qqServiceTitle->html('<h2>Qmsg酱配置</h2>');
        $form->addItem($qqServiceTitle);

        $qqApiUrl = new Typecho_Widget_Helper_Form_Element_Text('qqApiUrl', NULL, NULL, _t('Qmsg酱接口'), _t("当选择Qmsg酱必须填写"));
        $form->addInput($qqApiUrl);

        $receiveQq = new Typecho_Widget_Helper_Form_Element_Text('receiveQq', NULL, NULL, _t('接收消息的QQ，可以添加多个，以英文逗号分割'), _t("当选择Qmsg酱必须填写（指定的QQ必须在您的QQ号列表中）"));
        $form->addInput($receiveQq);
    }

    /**
     * Server酱配置面板
     * @param Typecho_Widget_Helper_Form $form
     */
    private static function weChatService(Typecho_Widget_Helper_Form $form)
    {
        $weChatServiceTitle = new Typecho_Widget_Helper_Layout('div', ['class=' => 'typecho-page-title']);
        $weChatServiceTitle->html('<h2>Server酱配置</h2>');
        $form->addItem($weChatServiceTitle);

        $weChatScKey = new Typecho_Widget_Helper_Form_Element_Text('weChatScKey', NULL, NULL, _t('Server酱 SCKEY'), _t("当选择Server酱必须填写"));
        $form->addInput($weChatScKey);
    }

    /**
     * 阿里云邮件配置面板
     * @param Typecho_Widget_Helper_Form $form
     */
    private static function aliYunMailService(Typecho_Widget_Helper_Form $form)
    {
        $aliYunEmailServiceTitle = new Typecho_Widget_Helper_Layout('div', ['class=' => 'typecho-page-title']);
        $aliYunEmailServiceTitle->html('<h2>阿里云邮件配置</h2>');
        $form->addItem($aliYunEmailServiceTitle);

        $aliYunRegion = new Typecho_Widget_Helper_Form_Element_Select('regionId', [
            AliYunEmailService::HANGZHOU => _t('华东1(杭州)'),
            AliYunEmailService::SINGAPORE => _t('亚太东南1(新加坡)'),
            AliYunEmailService::SYDNEY => _t('亚太东南2(悉尼)')
        ], NULL, _t('服务地址'), _t('选择邮件推送所在服务器区域'));
        $form->addInput($aliYunRegion);

        $aliYunAccessKeyId = new Typecho_Widget_Helper_Form_Element_Text('accessKeyId', NULL, NULL, _t('AccessKey ID'), _t('请填入在阿里云生成的AccessKey ID'));
        $form->addInput($aliYunAccessKeyId);

        $aliYunAccessKeySecret = new Typecho_Widget_Helper_Form_Element_Text('accessKeySecret', NULL, NULL, _t('Access Key Secret'), _t('请填入在阿里云生成的Access Key Secret'));
        $form->addInput($aliYunAccessKeySecret);


        $aliYunFromAlias = new Typecho_Widget_Helper_Form_Element_Text('fromAlias', NULL, NULL, _t('发件人名称'), _t('邮件中显示的发信人名称，留空为博客名称'));
        $form->addInput($aliYunFromAlias);

        $aliYunAccountName = new Typecho_Widget_Helper_Form_Element_Text('accountName', NULL, NULL, _t('发件邮箱地址'), _t('邮件中显示的发信地址'));
        $form->addInput($aliYunAccountName->addRule('email', _t('请输入正确的邮箱地址')));
    }

    /**
     * SMTP配置面板
     * @param Typecho_Widget_Helper_Form $form
     */
    private static function smtpService(Typecho_Widget_Helper_Form $form)
    {
        $smtpServiceTitle = new Typecho_Widget_Helper_Layout('div', ['class=' => 'typecho-page-title']);
        $smtpServiceTitle->html('<h2>SMTP配置</h2>');
        $form->addItem($smtpServiceTitle);

        $smtpHost = new Typecho_Widget_Helper_Form_Element_Text('smtpHost', NULL, NULL, _t('SMTP地址'), _t('SMTP服务器连接地址'));
        $form->addInput($smtpHost);

        $smtpPort = new Typecho_Widget_Helper_Form_Element_Text('smtpPort', NULL, NULL, _t('SMTP端口'), _t('SMTP服务器连接端口'));
        $form->addInput($smtpPort);

        $smtpFromAlias = new Typecho_Widget_Helper_Form_Element_Text('smtpFromAlias', NULL, NULL, _t('发件人名称'), _t('邮件中显示的发信人名称，留空为博客名称'));
        $form->addInput($smtpFromAlias);

        $smtpUser = new Typecho_Widget_Helper_Form_Element_Text('smtpUser', NULL, NULL, _t('SMTP登录用户'), _t('SMTP登录用户名，一般为邮箱地址'));
        $form->addInput($smtpUser);

        $smtpPass = new Typecho_Widget_Helper_Form_Element_Text('smtpPass', NULL, NULL, _t('SMTP登录密码'), _t('一般为邮箱密码，但某些服务商需要生成特定密码'));
        $form->addInput($smtpPass);

        $smtpAuth = new Typecho_Widget_Helper_Form_Element_Checkbox('smtpAuth', ['enable' => _t('服务器需要验证')], ['enable'], _t('SMTP验证模式'));
        $form->addInput($smtpAuth);

        $smtpSecure = new Typecho_Widget_Helper_Form_Element_Radio('smtpSecure', ['false' => _t('无安全加密'), 'ssl' => _t('SSL加密'), 'tls' => _t('TLS加密')], 'false', _t('SMTP加密模式'));
        $form->addInput($smtpSecure);


        $template = new Typecho_Widget_Helper_Layout('div', ['class=' => 'typecho-page-title']);
        $template->html('<h2>邮件自定义模版</h2>');
        $form->addItem($template);

        $authorTemplate = new Typecho_Widget_Helper_Form_Element_Textarea('authorTemplate', NULL, NULL, _t('向博主发信内容模板(为空即默认模版)'),
            _t("可选参数：
                '{blogUrl}',
                '{blogName}',
                '{author}',
                '{permalink}',
                '{title}',
                '{text}',
                '{ip}'
                <br>" . '写法：' . htmlspecialchars('<h1>{title}</h1>')));
        $form->addInput($authorTemplate);

        $replyTemplate = new Typecho_Widget_Helper_Form_Element_Textarea('replyTemplate', NULL, NULL, _t('向访客发信内容模板(为空即默认模版)'),
            _t("可选参数：
                '{blogUrl}',
                '{blogName}',
                '{author}',
                '{permalink}',
                '{title}',
                '{text}',
                '{replyAuthor}',
                '{replyText}',
                '{commentUrl}'
                <br>" . '写法：' . htmlspecialchars('<h1>{title}</h1>')));
        $form->addInput($replyTemplate);

    }

    /**
     * 钉钉机器人配置面板
     * @param Typecho_Widget_Helper_Form $form
     */
    private static function DingTalkBotService(Typecho_Widget_Helper_Form $form)
    {
        $DingTalkBotServiceTitle = new Typecho_Widget_Helper_Layout('div', ['class=' => 'typecho-page-title']);
        $DingTalkBotServiceTitle->html('<h2>钉钉机器人配置</h2>');
        $form->addItem($DingTalkBotServiceTitle);

        $DingTalkWebhook = new Typecho_Widget_Helper_Form_Element_Text('DingTalkWebhook', NULL, NULL, _t('钉钉 Webhook 地址'), _t("当选择钉钉机器人必须填写"));
        $form->addInput($DingTalkWebhook);

        $DingTalkSecret = new Typecho_Widget_Helper_Form_Element_Text('DingTalkSecret', NULL, NULL, _t('安全设置加签密钥'), _t("当选择钉钉机器人必须填写（安全设置:加签）"));
        $form->addInput($DingTalkSecret);
    }

    /**
     * 企业微信机器人配置面板
     * @param Typecho_Widget_Helper_Form $form
     */
    private static function EnterpriseWeChatService(Typecho_Widget_Helper_Form $form)
    {
        $EnterpriseWeChatServiceTitle = new Typecho_Widget_Helper_Layout('div', ['class=' => 'typecho-page-title']);
        $EnterpriseWeChatServiceTitle->html('<h2>企业微信机器人配置</h2>');
        $form->addItem($EnterpriseWeChatServiceTitle);

        $EnterpriseWeChatWebhook = new Typecho_Widget_Helper_Form_Element_Text('EnterpriseWeChatWebhook', NULL, NULL, _t('企业微信 Webhook 地址'), _t("当选择企业微信机器人必须填写"));
        $form->addInput($EnterpriseWeChatWebhook);
    }

    private static function officialAccount(Typecho_Widget_Helper_Form $form){
        $officialAccountTitle = new Typecho_Widget_Helper_Layout('div',['class' => 'typecho-page-title']);
        $officialAccountTitle->html('<h2>微信公众号</h2>');
        $form->addItem($officialAccountTitle);

        $token = new Typecho_Widget_Helper_Form_Element_Text('officialAccountToken', null, null, _t('接口配置信息 Token'), '接口配置信息 Token，Url为：博客地址/CommentPush/officialAccount');
        $form->addInput($token);

        $appId = new Typecho_Widget_Helper_Form_Element_Text('officialAccountAppId', null, null, _t('appId'), '微信公众号 appID');
        $form->addInput($appId);

        $appSecret = new Typecho_Widget_Helper_Form_Element_Text('officialAccountAppSecret', null, null, _t('appSecret'), '微信公众号 appSecret');
        $form->addInput($appSecret);

        $openId = new Typecho_Widget_Helper_Form_Element_Text('officialAccountOpenid', null, null, _t('openid'), '接收信息的微信号 openid');
        $form->addInput($openId);

        $templateId = new Typecho_Widget_Helper_Form_Element_Text('officialAccountTemplateId', null, null, _t('templateId'), "消息模版 templateId 可选参数：'{{title.DATA}}','{{user.DATA}}','{{ip.DATA}}','{{content.DATA}}'<br>示例（将例子复制粘贴到微信公众号模版内容即可）：
标题：{{title.DATA}}
评论人：{{user.DATA}}
IP：{{ip.DATA}}
评论内容：{{content.DATA}}");
        $form->addInput($templateId);
    }

    /**
     * @param Typecho_Widget_Helper_Form $form
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // TODO: Implement personalConfig() method.
    }


    public static function pushServiceReady($comment, $active)
    {
        self::$comment = $comment;
        self::$active = $active;

        return $comment;
    }

    public static function pushServiceGo($comment)
    {
        $options = Helper::options();
        $plugin = $options->plugin('CommentPush');

        $services = $plugin->services;

        if (!$services || $services == 'services') return false;


        self::$comment['coid'] = $comment->coid;

        /** @var QQService | WeChatService | AliYunEmailService | SmtpService | DingTalkBotService | EnterpriseWeChatService $service */
        foreach ($services as $service) call_user_func([$service, '__handler'], self::$active, self::$comment, $plugin);
    }
}