<?php
/**
 * Typecho 将附件转存至新浪云储存(SCS)
 * 
 * @package     SCS
 * @author 		vfhky
 * @version 	1.1.1
 * @update: 	2014.09.20
 * @link 		http://www.typecodes.com/web/scsfortypechov111.html
 */

class SCS_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle = array('SCS_Plugin', 'uploadHandle');
        Typecho_Plugin::factory('Widget_Upload')->modifyHandle = array('SCS_Plugin', 'modifyHandle');
        Typecho_Plugin::factory('Widget_Upload')->deleteHandle = array('SCS_Plugin', 'deleteHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentHandle = array('SCS_Plugin', 'attachmentHandle');
        return _t('请设置SCS的信息，以使插件正常使用！');
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
        return _t('插件已禁用成功！');
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
        $bucket = new Typecho_Widget_Helper_Form_Element_Text('bucket', null, null, _t('Bucket 名称(*)：'));
        $form->addInput($bucket->addRule('required', _t('Bucket名称不能为空！')));
		
        $accesskey = new Typecho_Widget_Helper_Form_Element_Text('accesskey', null, null, _t('AccessKey(*)：'));
        $form->addInput($accesskey->addRule('required', _t('AccessKey不能为空！')));
		
        $secretkey = new Typecho_Widget_Helper_Form_Element_Text('secretkey', null, null, _t('SecretKey(*)：'));
        $form->addInput($secretkey->addRule('required', _t('SecretKey不能为空！')));
		
        $scsbind = new Typecho_Widget_Helper_Form_Element_Text('scsbind', null, null, _t('SCS绑定的域名: '),  _t('非必填，有则填写SCS上绑定的域名。'));
        $form->addInput($scsbind->addRule('xssCheck', _t('SCS域名格式不正确！')));
		
        $format = new Typecho_Widget_Helper_Form_Element_Text('format', null, null, _t('自定义SCS路径: '),  _t('非必填，SCS存储路径。可用参数: {year}年份，{month}月份，{day}天数。默认为 "年份/月份/" 即 {year}/{month} 格式，也可自行输入类似 "{year}/a目录/b目录/{month}/" 等样式风格。'));
        $form->addInput($format->addRule('xssCheck', _t('SCS路径格式不正确！')));
		//必填则$form->addInput($format->addRule('required', _t('SCS路径不能为空！'))->addRule('xssCheck', _t('SCS路径格式不正确！')));
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
	
	
    /**
     * 获取SCS的配置
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function getSCSconfig()
    {
        return Typecho_Widget::widget('Widget_Options')->plugin('SCS');
    }
	
	
    /**
     * 获取SCS的SDK类
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function getSCSsdk()
    {
        if( !class_exists('SCS') )
			require 'SCS.php';
    }
	
	
	/**
     * 自定义SCS路径的检验 
     * 
     * @param string $filepath 
     * @static
     * @access private
     * @return string
     */
    private static function getSCSFilepath($filepath)
    {
        $filepath = str_replace( array("\\", "//" ), '/', htmlspecialchars($filepath) );
		if( substr( $filepath, 0, 1 ) === '/' )
			$filepath = substr($filepath, 1);
		if( substr( $filepath, -1, 1 ) !== '/' )
			$filepath .= '/';
		return $filepath;
    }
	
	
	/**
     * 获取安全的文件名 
     * 
     * @param string $name 
     * @static
     * @access private
     * @return string
     */
    private static function getSafeName(&$name)
    {
        $name = str_replace(array('"', '<', '>'), '', $name);
        $name = str_replace('\\', '/', $name);
        $name = false === strpos($name, '/') ? ('a' . $name) : str_replace('/', '/a', $name);
        $info = pathinfo($name);
        $name = substr($info['basename'], 1);
    
        return isset($info['extension']) ? $info['extension'] : '';
    }
	
    /**
     * 上传文件处理函数,如果需要实现自己的文件哈希或者特殊的文件系统,请在options表里把uploadHandle改成自己的函数
     *
     * @access public
     * @param array $file 上传的文件
     * @return mixed
     */
    public static function uploadHandle($file)
    {
        return self::ScsUpload($file);
    }
	
	
    /**
     * 上传附件
     * 
     * @static
     * @access public
     * @return bool
     * @throws Typecho_Plugin_Exception
     */
    public static function ScsUpload( $file, $content = null )
    {
        if( empty($file['name']) )
			return false;
		
		//获取安全的文件名后缀
		$ext = self::getSafeName($file['name']);
        if( !Widget_Upload::checkFileType($ext) )
			return false;
		
        $option = self::getSCSconfig();
        $date = new Typecho_Date(Typecho_Widget::widget('Widget_Options')->gmtTime);
		$path = ($option->format == null)?($date->year .'/'. $date->month . '/'):(preg_replace(array('/\{year\}/', '/\{month\}/', '/\{day\}/'), array( $date->year, $date->month, $date->day ), self::getSCSFilepath($option->format)));
		
		
		/*非必须(在本地附件目录/usr/uploads/下创建新目录)
        if (!is_dir($path)) {
            if (!self::makeUploadDir($path)) {
                return false;
            }
        }
		*/
        //以Unix time stamp形式储存：$path .= sprintf('%u', crc32(uniqid())) . '.' . $ext;
		$path .= $file['name'];
		
        if( isset($content) )
        {
            $path = $content['attachment']->path;
            self::ScsDelete($path);
        }
		
        $clienttmp = $file['tmp_name'];
        if( !isset($clienttmp) )
			return false;
		
        self::getSCSsdk();
		$scs = new SCS( $option->accesskey, $option->secretkey );
		
		if( $scs->putObjectFile($clienttmp, $option->bucket, $path, SCS::ACL_PUBLIC_READ) )
        {
            return array
            (
                'name'  =>  $file['name'],
                'path'  =>  $path,
                'size'  =>  $file['size'],
                'type'  =>  $ext,
                'mime'  =>  Typecho_Common::mimeContentType($path)
            );
        }
        else
			return false;
    }
	
	
    /**
     * 修改文件处理函数,如果需要实现自己的文件哈希或者特殊的文件系统,请在options表里把modifyHandle改成自己的函数
     *
     * @access public
     * @param array $content 老文件
     * @param array $file 新上传的文件
     * @return mixed
     */
    public static function modifyHandle($content, $file)
    {
        return self::ScsUpload($file, $content);
    }
	
	
    /**
     * 删除文件
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function deleteHandle(array $content)
    {
        self::ScsDelete( $content['attachment']->path );
    }
	
	
	/**
     * 删除附件
     * 
     * @static
     * @access public
     * @return boolean
     * @throws Typecho_Plugin_Exception
     */
    public static function ScsDelete($filepath)
    {
        $option = self::getSCSconfig();
        self::getSCSsdk();
        $scs = new SCS( $option->accesskey, $option->secretkey );
		if( $scs->deleteObject( $option->bucket, $filepath ) )
			return true;
        else
			return false;
    }
	
	
	/**
     * 获取实际文件绝对访问路径
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function attachmentHandle(array $content)
    {
        $option = self::getSCSconfig();
		if( $option->scsbind == null )
		{
			self::getSCSsdk();
			return Typecho_Common::url($content['attachment']->path, SCS::$endpoint.'/'.$option->bucket.'/' );
		}
		else
			return Typecho_Common::url($content['attachment']->path, $option->scsbind );
    }
}
