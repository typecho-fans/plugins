<?php
require dirname(__FILE__) . '/PHPMailer/src/PHPMailer.php';
require dirname(__FILE__) . '/PHPMailer/src/SMTP.php';
require dirname(__FILE__) . '/PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer; 
class Send
{
	/**
	 * 插件实现方法
	 * 
	 * @access public
	 * @return void
	 */
	public static function sender($contents, $inst, $type=0)
	{
		$db = Typecho_Db::get();
		$options = Typecho_Widget::widget('Widget_Options');
		$configs = Helper::options()->plugin('AutoBackup');


		$current = Typecho_Date::gmtTime();		//当前时间

		$config_file = dirname(__FILE__).'/config.xml';
		$xml = simplexml_load_file($config_file);
		$lasttime = intval($xml->lasttime);
if($type==0){
		if ($lasttime < 0 || ($current - $lasttime) < $configs->circle * 24 * 60 * 60) {
			return $contents;
		}
}
		$file_path = self::create_sql();	//获取备份语句

		$xml->lasttime = time();
		$xml = $xml->asXML();			
		$fp = fopen($config_file, 'wb');
		fwrite($fp, $xml);
		fclose($fp);

		//将备份文件发送至设置的邮箱
		$smtp = array();
		$smtp['site'] = $options->title;
		$smtp['attach'] = $file_path;
	
		$smtp['attach_name'] = "AutoBackup".date("Ymd", $current).".zip";
		if (!function_exists('gzopen')) {
			$smtp['attach_name'] = "AutoBackup".date("Ymd", $current).".sql";
		}
		//获取SMTP设置
		$smtp['user'] = $configs->user;
		$smtp['pass'] = $configs->pass;
		$smtp['host'] = $configs->host;
		$smtp['port'] = $configs->port;

		$format = "format";


	
			
		if ($configs->subject != "") {
			$smtp['subject'] = date("Ymd").'-'.$configs->subject.'-数据库备份文件';
		}else {
			$smtp['subject'] = date("Ymd").'-'.$options->title.'-数据库备份文件';
		}			


		$smtp['AltBody'] = "";
		$smtp['body'] = '<div><div style="position: relative;color:#555;letter-spacing: 2px;font:12px/1.5 PingFangSC-Light,Microsoft YaHei,Tahoma,Helvetica,Arial,sans-serif;max-width:600px;margin:50px auto;border-top: 1px solid #d8d8d863;border-right:1px solid rgb(224 224 224);border-left:1px solid #d8d8d863;box-shadow: rgb(203, 208, 218) 0px 2px, rgba(48, 52, 63, 0.2) 0px 3px, rgba(48, 52, 63, 0.2) 0px 7px 7px, rgb(255, 255, 255) 0px 0px 0px 1px inset;border-radius: 5px;background: 0 0 repeat-x #FFF;background-image: -webkit-repeating-linear-gradient(135deg, #6c5b92, #4882CE 20px, #FFF 20px, #FFF 35px, #00769a 35px, #00769a 55px, #FFF 55px, #FFF 70px);background-image: repeating-linear-gradient(-45deg, #6c5b92, #6c5b92 20px, #FFF 20px, #FFF 35px, #00769a 35px, #00769a 55px, #FFF 55px, #FFF 70px);background-size: 100% 10px;"><div style="padding: 0 15px 8px;"><h2 style="border-bottom:1px solid #e9e9e9;font-size:18px;font-weight:normal;padding:10px 0 10px;"><span style="color: #12ADDB"><br>❀</span>&nbsp;'.date("Y年m月d日").'</h2><div class="content"><div style="font-size:14px;color:#777;padding:0 10px;margin-top:10px"><p style="background-color: #f5f5f5;border: 0px solid #DDD;padding: 10px 15px;margin:18px 0">这是从'.$smtp["site"].'由Typecho AutoBackup插件自动发送的数据库备份文件，备份文件详见邮件附件！</p></div></div><div align="center" style="text-align: center; font-size: 12px; line-height: 14px; color: rgb(163, 163, 163); padding: 5px 0px;"><div style="color:#888;padding:10px;"><p style="margin:0;padding:0;letter-spacing: 1px;line-height: 2;">该邮件由您的Typecho博客<a href="'.$options->siteUrl.'">'.$smtp["site"].'</a>使用的插件AutoBackup发出<br />如果你没有做相关设置，请联系邮件来源地址'.$smtp["user"].'</p></div></div></div></div></div>';


		if($configs->mail != "") {
			$email_to=$configs->mail;
		}else {
			$select = Typecho_Widget::widget('Widget_Abstract_Users')->select()->where('uid',1);
			$result = $db->query($select);
			$row = $db->fetchRow($result);
			$email_to = $row['mail'];
		}

		$smtp['to']=$email_to;
		$smtp['from']=$email_to;

		self::SendMail($smtp);

		unlink($file_path);

		return $contents;
	}

	/**
	 * 生成备份sql语句
	 *
	 * @param string $tables
	 */
	private static function create_sql(){
		$configs = Helper::options()->plugin('AutoBackup');
		
		$tables = $configs->tables;
        if (!is_array($tables)){echo "你没有选择任何表"; exit;}
        
        
		$db = Typecho_Db::get();
		
		$sql = "-- Typecho AutoBackup\r\n-- version 1.2.0\r\n-- 生成日期: ".date("Y年m月d日 H:i:s")."\r\n-- 使用说明：创建一个数据库，然后导入文件\r\n\r\n";

		foreach ($tables as $table) {		//循环获取数据库中数据
			$sql .= "\r\nDROP TABLE IF EXISTS ".$table.";\r\n";
			$create_sql = $db->fetchRow($db->query("SHOW CREATE TABLE `" . $table . "`"));
			$sql .= $create_sql['Create Table'].";\r\n";
			$result = $db->query($db->select()->from($table));
			while ($row = $db->fetchRow($result)) {
				foreach ($row as $key=>$value) {	//每次取一行数据
					$keys[] = "`".$key."`";		//字段存入数组
					$values[] = "'".addslashes($value)."'";		//值存入数组
				}
				$sql .= "insert into `".$table."` (".implode(",", $keys).") values (".implode(",", $values).");\r\n";	//生成插入语句

				//清空字段和值数组
				unset($keys);
				unset($values);
			}
		}
		
		$file_path = dirname(__FILE__)."/backupfiles/". md5($configs->pass . time()) . ".sql";

		file_put_contents($file_path, $sql);

    	if (!function_exists('gzopen')) {
			return $file_path;
		}

		require_once('pclzip.lib.php');

		$zip = new PclZip(dirname(__FILE__) . "/backupfiles/" . md5($configs->pass . time()) . ".zip");

		$zip->create($file_path, PCLZIP_OPT_REMOVE_PATH, dirname(__FILE__) . "/backupfiles/");
		
		unlink($file_path);

		return $zip->zipname;
	}





	/**
	 * 发送邮件
	 *
	 * @access public
	 * @param array $smtp 邮件信息
	 * @return void
	 */
	private static function SendMail($smtp) {
$options = Helper::options();
// 获取插件配置
$SMTPSecure = $options->plugin('AutoBackup')->SMTPSecure; // SMTP 加密类型 'ssl' or 'tls'.
try {		
            $STMPHost     = $smtp['host'];//SMTP服务器地址
            $SMTPPort     = $smtp['port'];//端口
            
            $SMTPUserName = $smtp['user'];//用户名
            $SMTPPassword = $smtp['pass'];//邮箱秘钥
            $SMTPSecure   = $SMTPSecure;//加密方式
            $fromMail     = $smtp['user'];//发件邮箱
            $fromName     = '备份小助手';//发件人名字
            $fromMailr     = $smtp['from'];//收件人邮箱

            // Server settings
            $mail = new PHPMailer(true);
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->Encoding = PHPMailer::ENCODING_BASE64;
            $mail->isSMTP();
            $mail->Host = $STMPHost; // SMTP 服务地址
            $mail->SMTPAuth = true; // 开启认证
            $mail->Username = $SMTPUserName; // SMTP 用户名
            $mail->Password = $SMTPPassword; // SMTP 密码
            $mail->SMTPSecure = $SMTPSecure; // SMTP 加密类型
            $mail->Port = $SMTPPort; // SMTP 端口
            $mail->setFrom($fromMail, $fromName);//发件人
            $mail->addAddress($fromMailr);


            $mail->Subject = $smtp['subject'];
            $mail->isHTML(); // 邮件为HTML格式
            // 邮件内容
            $mail->Body = $smtp['AltBody'].$smtp['body'];
            $mail->AddAttachment($smtp['attach'], $smtp['attach_name']);
            $mail->send();


  
} catch (Exception $e) {
            echo "网络故障，发送失败！";
            exit($e);
        }
	    

	}



}




?>