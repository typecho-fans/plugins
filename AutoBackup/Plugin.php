<?php
/**
 * Typecho 自动备份插件，插件原作者zhoumiao(2012年停更的)，2021年被我重新维护
 * 
 * @package AutoBackup
 * @author 泽泽社长
 * @version 1.3.2
 * @link https://zezeshe.com/archives/autobackup-typecho-plugins.html
 */
class AutoBackup_Plugin implements Typecho_Plugin_Interface
{
	/**
	 * 激活插件方法,如果激活失败,直接抛出异常
	 * 
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function activate()
	{
		Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write_15 = array('AutoBackup_Plugin', 'render');
		Typecho_Plugin::factory('Widget_Feedback')->finishComment_15 = array('AutoBackup_Plugin', 'render');
        Helper::addRoute("route_autobackup","/autobackup","AutoBackup_Action",'action');
	}

	/**
	 * 禁用插件方法,如果禁用失败,直接抛出异常
	 * 
	 * @static
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function deactivate()
	{
        Helper::removeRoute("route_autobackup");
	}

	/**
	 * 获取插件配置面板
	 * 
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form 配置面板
	 * @return void
	 */
	public static function config(Typecho_Widget_Helper_Form $form)
	{
$rooturl=Helper::options()->rootUrl;if (Helper::options()->rewrite==0){$rooturl=$rooturl.'/index.php';}

      $tables = new Typecho_Widget_Helper_Form_Element_Checkbox('tables', self::listTables(), self::listTables(), _t('需要备份的数据表'), _t('选择你需要备份的数据表，插件首次启动时会默认全选'));
        $form->addInput($tables);

		$subject = new Typecho_Widget_Helper_Form_Element_Text('subject', null, null, _t('自定义邮件标题'), _t('格式：20100902-XXX-数据库备份文件（不填则XXX默认为博客标题）'));
		$form->addInput($subject);

		$host = new Typecho_Widget_Helper_Form_Element_Text('host', NULL, null,  _t('SMTP地址'), _t('如:smtp.163.com,smtp.gmail.com,smtp.qq.com,smtp.exmail.qq.com,smtp.sohu.com,smtp.sina.com'));
		$form->addInput($host);

		$port = new Typecho_Widget_Helper_Form_Element_Text('port', NULL, null, _t('SMTP端口'), _t('SMTP服务端口,一般为25;gmail和qq的465。'));
		$port->input->setAttribute('class', 'mini');
		$form->addInput($port->addRule('isInteger', _t('端口号必须是纯数字')));

		$user = new Typecho_Widget_Helper_Form_Element_Text('user', NULL, null, _t('SMTP用户'),_t('SMTP服务验证用户名,一般为邮箱名如：youname@domain.com'));
		$form->addInput($user);

		$pass = new Typecho_Widget_Helper_Form_Element_Password('pass', NULL, NULL, _t('SMTP密码'));
		$form->addInput($pass);

        // 服务器安全模式
        $SMTPSecure = new Typecho_Widget_Helper_Form_Element_Radio('SMTPSecure', array('' => _t('无安全加密'), 'ssl' => _t('SSL加密'), 'tls' => _t('TLS加密')), 'none', _t('SMTP加密模式'));
        $form->addInput($SMTPSecure);

		$mail = new Typecho_Widget_Helper_Form_Element_Text('mail', NULL, null, _t('接收邮箱'),_t('接收邮件用的信箱，此项必填！'));
		$form->addInput($mail->addRule('email', _t('请填写正确的邮箱！')));
		
		
		$circle = new Typecho_Widget_Helper_Form_Element_Text('circle', null, '1', _t('更新周期(天)'), _t('根据博客更新状况酌情填写，纯数字'));
		$form->addInput($circle->addRule('isInteger', _t('更新周期必须是纯数字')));
		
		$blogcron = new Typecho_Widget_Helper_Form_Element_Radio('blogcron', array('0' => _t('关闭'), '1' => _t('开启')), '0', _t('监听文章接口'), _t('监听文章发布于评论接口，然后判断是否达到设置的时间间隔，如果达到就发送数据库备份文件到邮箱，未达到就不发送，该功能在触发发送备份时可能会拖慢博客反应速度，所以默认关闭该项，推荐使用下方的定时任务功能'));
        $form->addInput($blogcron);
		
		$cronpass = new Typecho_Widget_Helper_Form_Element_Password('cronpass', NULL, NULL, _t('定时任务接口秘钥'),_t('定时任务接口地址：'.$rooturl.'/autobackup?taken=你设置的秘钥，该项不填写则不开启定时任务接口，将定时任务接口的链接填写到一些定时任务的网站，比如宝塔服务器的定时任务功能'));
		$form->addInput($cronpass);
		
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
	}
	public static function render($contents, $inst){
	    
		if(Helper::options()->plugin('AutoBackup')->blogcron=='0'){
		    return $contents;
		}else{
	    require_once 'send.php';
        $Send = new Send();
        return $Send->sender($contents, $inst);
		    
		}
	}

        /**
     * 获取数据表
     * @return array
     * @throws Typecho_Db_Exception
     */
    public static function listTables()
    {
        $db = Typecho_Db::get();
        $rows = $db->fetchAll($db->query("SHOW TABLES"));
        $tables = [];
        foreach ($rows as $row) {
            $tables[array_values($row)[0]] = array_values($row)[0];
        }
        return $tables;
    }
}
