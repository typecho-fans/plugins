<?php
/** 
 * @author DEFE
 * @link http://defe.me
 */
class UploadPlugin_Action extends Typecho_Widget implements Widget_Interface_Do
{

    private $_file;
    /**
     * 删除插件
     * @param mixed $plugName 待删除的插件名称
     * @access public
     * @return void
     */
    public function del($plugName)
    {
        $this->widget('Widget_User')->pass('administrator');
        if($plugName){
            $dir='.'.__TYPECHO_PLUGIN_DIR__;
            if (is_writable($dir)){
                chdir($dir);
                if(is_dir($plugName)){
                    if(false==$this->delTree($plugName)){
                        $this->widget('Widget_Notice')->set(_t('插件：'.$plugName.'删除失败'), NULL, 'error');
                    }else{
                        $this->widget('Widget_Notice')->set(_t('成功删除插件：'.$plugName), NULL, 'success');
                    }
                }else{
                    $file=$plugName.'.php';
                    if(@unlink($file)){
                        $this->widget('Widget_Notice')->set(_t('成功删除插件：'.$plugName), NULL, 'success');
                    }else{
                        $this->widget('Widget_Notice')->set(_t('插件：'.$plugName.'删除失败'), NULL, 'error');
                    }
                 }
            }else{
                $this->widget('Widget_Notice')->set(_t('插件主目录没有写权限：'.$plugName.'删除失败'), NULL, 'error');
            }
        }else{
            $this->widget('Widget_Notice')->set(_t('插件不存在！'), NULL, 'notice');
        }
    }

    /**
     * 删除模板
     * @param mixed $plugName 待删除的插件名称
     * @access public
     * @return void
     */
    public function delTheme($themeName)
    {
        $this->widget('Widget_User')->pass('administrator'); 
        $dir='.'.__TYPECHO_THEME_DIR__;
        if (is_writable($dir)){
            chdir($dir);
            if(is_dir($themeName)){
                if(false==$this->delTree($themeName)){
                    $this->widget('Widget_Notice')->set(_t('模板：'.$themeName.'删除失败'), NULL, 'error');
                }else{
                    $this->widget('Widget_Notice')->set(_t('成功删除模板：'.$themeName), NULL, 'success');
                }
            }else{
                $this->widget('Widget_Notice')->set(_t('没发现'.$themeName.'模板！'), NULL, 'notice');
            }
        }else{
            $this->widget('Widget_Notice')->set(_t('themes主目录没有写权限：'.$themeName.'删除失败'), NULL, 'error');
        }
    }
    
    /**
     * 删除目录
     * @param mixed $dir 待删除的插件目录
     * @access public
     * @return boolean
     */
    private function delTree($dir){
        chdir($dir);
        $dh=opendir('.');
        while(false !== ($et=readdir($dh))){
            if(is_file($et)){
                if(!@unlink($et)){
                    return false;
                    break;
                }
            }else{
                if(is_dir($et) && $et!='.' && $et!='..'){
                    $this->delTree('./'.$et);
                }
            }
        }
        closedir($dh);
        chdir('..');
        if(@rmdir($dir)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 上传处理功能
     */
    public function upload($filead){
        
        if (!empty($_FILES['file']['name']) || $filead) {            
            if (!$filead and !isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name']) || $_FILES['file']['error'] != 0) {
                    echo '<script>alert("上传失败！")</script>';
                    exit(0);
            }elseif ($filead) {
                $this->_file = basename($filead);
                $part = pathinfo($this->_file);
                if($part['extension'] !== "zip"){                    
                    $this->_file = "tmp".Typecho_Common::randString(6).".zip";
                }
                $ff = @file_get_contents($filead);
                if($ff){
                    file_put_contents($this->_file, $ff);
                    $up = False;
                    if(!$this->isZip($this->_file)) $this->showMsg('取得的文件不是zip类型' , FALSE, TRUE);
                }else{
                    $this->showMsg('获取远程文件失败。' , FALSE, TRUE);
                }
                
            }  else {
                $this->_file = $_FILES['file']['tmp_name'];
                $up = True;
            }
            
            $path = '';
            $upType = '插件';
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive;
            }else{                
                $this->showMsg("服务器不支持 php ZipArchive 类", $up);
            }
            
            if ($zip->open($this->_file) === TRUE) {
                //单文件插件
                 if($zip->numFiles === 1){
                      $contents = $zip->getFromIndex(0);
                      if($this->isPlugin($contents)) $path= '.' . __TYPECHO_PLUGIN_DIR__.'/' ;
                 }else{
                     //多文件插件，搜索Plugin.php文件路径，获取插件信息
                    $index=$zip->locateName('Plugin.php', ZIPARCHIVE::FL_NOCASE|ZIPARCHIVE::FL_NODIR);
                    if($index || 0===$index){
                        $dirs = count(explode('/', $zip->getNameIndex($index)));
                        if($dirs>2){                           
                            $this->showMsg('压缩路径太深，无法正常安装', $up, TRUE) ;
                        }
                        $contents = $zip->getFromIndex($index);
                        $name = $this->isPlugin($contents);                           
                        if($name){
                            if(2==$dirs){
                                $path='.' . __TYPECHO_PLUGIN_DIR__.'/';
                            }else{
                                $path='.' . __TYPECHO_PLUGIN_DIR__ .'/'. $name.'/';
                            } 
                        }
                    }else{
                        //如果不是插件，则按模板搜索判断
                        $index=$zip->locateName('index.php', ZIPARCHIVE::FL_NOCASE|ZIPARCHIVE::FL_NODIR);
                        if($index || 0===$index){
                            $upType = '外观';
                            $dirs = count(explode('/', $zip->getNameIndex($index)));
                            if($dirs>2){                               
                                $this->showMsg('压缩路径太深，无法正常安装', $up, TRUE) ;
                            }
                            $contents = $zip->getFromIndex($index);                                                     
                            if($this->isTheme($contents)){
                                if(2==$dirs){    
                                    $path='.' . __TYPECHO_THEME_DIR__.'/';
                                }else{
                                    $name = basename($_FILES['file']['name'],'.zip');
                                    $path='.' . __TYPECHO_THEME_DIR__ .'/'. $name.'/';
                                }
                            }
                        }
                    }
                }
                if($path!==''){
                    if($zip->extractTo($path)){
                        $zip->close();                        
                        $this->showMsg("安装成功，请到 控制台-->" . $upType . " 中激活使用。", $up, TRUE);
                    }else{
                        $this->showMsg("解压失败,请确认" . $upType . "目录是否有写权限。", $up) ;                       
                    }

                }  else {                    
                    $this->showMsg('上传的文件不是Typecho插件和模板', $up);
                }

            } else {
                $this->showMsg('无法打开压缩包，请检查压缩包是否损坏。', $up);            
            }
            @unlink($this->_file); 
        }else{
            $this->widget('Widget_Archive@404', 'type=404')->render();
            exit;
        }
    }
    
    public function showMsg($str, $up = True, $exit=FALSE){
        if($up && !$exit){
            //echo("<script>parent.fileUploadError('" . $this->request->_id . "','" . $str . "');</script>"); 
			$this->widget('Widget_Notice')->set(_t($str), NULL, 'error');
        }
        if($up && $exit){
            @unlink($this->_file);   
            //exit("<script>parent.fileUploadComplete('" . $this->request->_id . "','typecho','" . $str . "');</script>"); 
			$this->widget('Widget_Notice')->set(_t($str), NULL, 'success');
        }
        if(!$up && $exit){
            @unlink($this->_file);   
            exit($str); 
        }
        if(!$up && !$exit) echo($str);         
    }

        /**
     * 判断上传文件是否为插件
     * @param type $contents
     * @return boolean
     */
    public function isPlugin($contents){
         $info =  $this->parseInfo($contents);
         if( $info['description']!=="" && $info['name']!=="" && !empty($info['version']) && !empty($info['author'])){
            return $info['name'] ;
         }else{
            return FALSE;
         }
    }
    /**
     * 判断上传文件是否为模板
     * @param type $contents
     * @return boolean
     */
    public function isTheme($contents){
         $info =  $this->parseInfo($contents);
         if($info['title']!=="" && !empty($info['version']) && !empty($info['author'])){
            return TRUE;
         }else{
            return FALSE;
         }
    }
    
    /**
     * 
     * @param type $file
     * @return boolean
     */
    public function isZip($file){
        $file = @fopen($file,"rb");
        $bin = fread($file, 15);
        fclose($file);

        $blen=strlen(pack("H*","504B0304")); //得到文件头标记字节数
        $tbin=substr($bin,0,intval($blen)); ///需要比较文件头长度
        
        $upack = unpack("H*",$tbin);
        if(strtolower("504B0304")==strtolower(array_shift($upack))) 
        {
            return TRUE;
        }  else {
            return FALSE;
        }
    }

    //解析Plugin.php文件
    public  function parseInfo($pluginFile)
    {
        $tokens = token_get_all($pluginFile);
        $isDoc = false;
        $isClass = false;

        /** 初始信息 */
        $info = array(
            'name'              => '',
            'description'       => '',
            'title'             => '',
            'author'            => '',
            'homepage'          => '',
            'version'           => '',
            'dependence'        => '',
        );

        $map = array(
            'package'   =>  'title',
            'author'    =>  'author',
            'link'      =>  'homepage',
            'dependence'=>  'dependence',
            'version'   =>  'version'
        );

        foreach ($tokens as $token) {
            /** 获取doc comment */
            if (!$isDoc && is_array($token) && T_DOC_COMMENT == $token[0]) {

                /** 分行读取 */
                $described = false;
                $lines = preg_split("(\r|\n)", $token[1]);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line) && '*' == $line[0]) {
                        $line = trim(substr($line, 1));
                        if (!$described && !empty($line) && '@' == $line[0]) {
                            $described = true;
                        }

                        if (!$described && !empty($line)) {
                            $info['description'] .= $line . "\n";
                        } else if ($described && !empty($line) && '@' == $line[0]) {
                            $info['description'] = trim($info['description']);
                            $line = trim(substr($line, 1));
                            $args = explode(' ', $line);
                            $key = array_shift($args);

                            if (isset($map[$key])) {
                                $info[$map[$key]] = trim(implode(' ', $args));
                            }
                        }
                    }
                }

                $isDoc = true;
            }

            if (!$isClass && is_array($token) && T_CLASS == $token[0]) {
                $isClass = true;
            }
            if ($isClass && is_array($token) && T_STRING == $token[0]){
                $name=explode('_',$token[1]);
                $info['name']=$name[0];
                break;
            }
        }
        return $info;
    }

    public function action()
    {   
        $this->widget('Widget_User')->pass('administrator'); 
        $this->on($this->request->is('del'))->del($this->request->del);
        $this->on($this->request->is('delTheme'))->delTheme($this->request->delTheme); 
        $this->on($this->request->is('upload'))->upload($this->request->upload);
        $this->response->goBack();
    }

}
?>
