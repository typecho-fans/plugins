<?php
/**
 * Gravatar 头像缓存插件
 *
 * @package GravatarCache
 * @author Byends
 * @version 2.0.2
 * @link http://www.byends.com
 */
class GravatarCache implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Abstract_Comments')->gravatar = array('GravatarCache', 'getGravatar');
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
        self::deleteFile();
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
        $timeCache = new Typecho_Widget_Helper_Form_Element_Text('timeCache', NULL, '1209600', _t('缓存时间'),_t('缓存时间，默认 14天 = 1209600 秒'));
        $timeCache->input->setAttribute('class', 'mini');
        $form->addInput($timeCache->addRule('required', _t('必须填写缓存时间'))->addRule('isInteger', _t('缓存时间必须是整数')));

        $dir = new Typecho_Widget_Helper_Form_Element_Text('dir', null, '/usr/uploads/avatarCache/', _t('存放路径'), _t('缓存头像存放的路径，请确保第一个目录可写！'));
        $form->addInput($dir->addRule('required', _t('必须填写缓存目录')));

        $delCache= new Typecho_Widget_Helper_Form_Element_Radio( 'delCache', array( 'delY' => '是', 'delN' => '否' ), 'delY', '删除缓存',_t('禁用插件时是否删除缓存头像和目录') );
        $form->addInput($delCache);

        return _t('请到插件配置里设置相应选项');
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 插件实现方法
     *
     * @param $size
     * @param $rating
     * @param $default
     * @param $comments
     */
    public static function getGravatar($size, $rating, $default, $comments)
    {
        $imgUrl = self::getGravatarCache($comments->mail, $comments->request->isSecure(), $size, $rating, $default);
        echo '<img class="avatar" src="'.$imgUrl.' "alt="'.$comments->author.'" width="'.$size.'" height="'.$size.'" />';
    }

    /**
     * 外部调用方法
     *
     * @param $mail
     * @param bool $isSecure
     * @param int $size
     * @param string $rating
     * @param string $default
     * @return string
     * @throws exception
     */
    public static function getGravatarCache($mail, $isSecure = false, $size = 32, $rating = 'G', $default = 'mm')
    {
        $option = Typecho_Widget::widget('Widget_Options')->plugin('GravatarCache');
        $siteUrl = Helper::options()->siteUrl;
        $dir = __TYPECHO_ROOT_DIR__ . DIRECTORY_SEPARATOR;
        $referer = "http://www.gravatar.com";
        $path = $option->dir;
        $path = substr($path, 0, 1) == '/' ? substr($path, 1) : $path;
        $path = substr($path, -1, 1) != '/' ? $path.'/' : $path;
        $file = $dir.$path.'default.jpg';
        $default = empty($default) ? 'mm' : $default;
        $default = $default == 'mm' ? $default : urlencode($default);

        if(!self::mkdirs(dirname($file))){
            throw new exception('GravatarCache 目录创建失败，请检查指定的根目录是否可写' );
        }

        /** 如果默认的 default.jpg不存在，则下载 gravatar 默认的头像到本地*/
        if(!file_exists($file)){
            $avatar = 'http://www.gravatar.com/avatar/00000000000000000000000000000000?d='.$default.'&s='.$size.'&r='.$rating;
            if(!self::download($avatar, $referer, $file)) copy($avatar, $file);
        }

        $timeCache = $option->timeCache;
        $defaultMail = empty($mail) ? 'default' : md5( strtolower( $mail ) );
        $imgUrl = $siteUrl.$path.$defaultMail.'.jpg';
        $baseFile = $dir.$path.$defaultMail.'.jpg';

        if(!file_exists($baseFile) || (time() - filemtime($baseFile)) > $timeCache){
            $host = $isSecure ? 'https://secure.gravatar.com' : 'http://www.gravatar.com';
            $avatar = $host.'/avatar/'.$defaultMail.'?d='.$default.'&s='.$size.'&r='.$rating;
            if(!self::download($avatar, $referer, $baseFile)) copy($avatar, $baseFile);
            if(filesize($baseFile) == 911 && filesize($file) != 911) copy($file, $baseFile);
        }
        return $imgUrl;
    }

    /**
     * 生成多级目录
     *
     * @param $dir
     * @return bool
     */
    public static function mkdirs($dir)
    {
        return is_dir($dir) or (self::mkdirs(dirname($dir)) and mkdir($dir, 0777));
    }

    /**
     * 禁用插件时同时删除缓存头像
     *
     * @access public
     * @return void
     */
    public static function deleteFile()
    {

        $option = Typecho_Widget::widget('Widget_Options')->plugin('GravatarCache');
        $path = __TYPECHO_ROOT_DIR__ . DIRECTORY_SEPARATOR. $option->dir;
        if (substr($path,-1)!='/') {$path.='/';}
        if( $option->delCache == 'delY' ){
            foreach (glob( $path. '*.jpg') as $filename) {
                unlink($filename);
            }
            $sysDir =  array( 'usr', 'uploads', 'themes', 'plugins' );
            $dirArray = explode("/", $path);
            array_pop($dirArray);
            $currentDir = array_pop($dirArray);

            if(!in_array( $currentDir, $sysDir)) { rmdir($path); }
        }
    }

    /**
     * 下载头像到本地
     *
     * @param $url
     * @param $referer
     * @param $imagePath
     * @return bool
     */
    public static function download( $url, $referer, $imagePath  )
    {
        $fpLocal = @fopen( $imagePath, 'w' );
        if( !$fpLocal ) {
            return false;
        }

        if( is_callable('curl_init') ) {
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_REFERER, $referer );
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
            curl_setopt( $ch, CURLOPT_HEADER, 0 );
            curl_setopt( $ch, CURLOPT_FILE, $fpLocal );
            if( !curl_exec($ch) ) {
                fclose( $fpLocal );
                curl_close( $ch );
                return false;
            }
            curl_close( $ch );
        }else {
            $opts = array(
                'http' => array(
                    'method' => "GET",
                    'header' => "Referer: $referer\r\n"
                )
            );

            $context = stream_context_create( $opts );
            $fpRemote = @fopen( $url, 'r', false, $context );
            if( !$fpRemote ) {
                fclose( $fpLocal );
                return false;
            }

            while( !feof( $fpRemote ) ) {
                fwrite( $fpLocal, fread($fpRemote, 8192) );
            }
            fclose( $fpRemote );
        }

        fclose( $fpLocal );
        return true;
    }
}