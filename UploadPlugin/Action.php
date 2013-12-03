<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class UploadPlugin_Action extends Typecho_Widget implements Widget_Interface_Do
{

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
    public function delTree($dir){
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
    public function upload(){
		
        if (!empty($_FILES['file']['name'])) {
            
            if (!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name']) || $_FILES['file']['error'] != 0) {
                    echo '<script>alert("上传失败！")</script>';
                    exit(0);
            }

            //判断上传的是否是typecho插件
            $isPlugin= false;
            $path = '';
            $upType = '插件';
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive;
            }else{
                echo '<script>alert("服务器不支持 php ZipArchive 类")</script>';
                exit(0);
            }
            //$zip = new ZipArchive;
            if ($zip->open($_FILES['file']['tmp_name']) === TRUE) {
                //单文件插件
                 if($zip->numFiles ==1){
                      $contents = $zip->getFromIndex(0);
                      if($this->isPlugin($contents)) $path= '.' . __TYPECHO_PLUGIN_DIR__.'/' ;
                 }else{
                     //多文件插件，搜索Plugin.php文件路径，获取插件信息
                    $index=$zip->locateName('Plugin.php', ZIPARCHIVE::FL_NOCASE|ZIPARCHIVE::FL_NODIR);
                    if($index || 0===$index){
                            $dirs = count ( split('/', $zip->getNameIndex($index)));
                            if($dirs>2) exit('<script>alert("压缩路径太深，无法正常安装！")</script>');
                            $contents = $zip->getFromIndex($index);
                            $name = $this->isPlugin($contents);                           
                            if($name && 2==$dirs){
                                $path='.' . __TYPECHO_PLUGIN_DIR__.'/';
                            }else{
                                $path='.' . __TYPECHO_PLUGIN_DIR__ .'/'. $name.'/';
                            }
                        }else{
							//如果不是插件，则按模板搜索判断
							$index=$zip->locateName('index.php', ZIPARCHIVE::FL_NOCASE|ZIPARCHIVE::FL_NODIR);
							if($index || 0===$index){
								$upType = '外观';
								$dirs = count ( split('/', $zip->getNameIndex($index)));
								if($dirs>2) exit('<script>alert("压缩路径太深，无法正常安装！")</script>');
								$contents = $zip->getFromIndex($index);														
								if($this->isTheme($contents) && 2==$dirs){									
									$path='.' . __TYPECHO_THEME_DIR__.'/';
								}else{
									$name = basename($_FILES['file']['name'],'.zip');
									$path='.' . __TYPECHO_THEME_DIR__ .'/'. $name.'/';
								}
							}else{
								echo '<script>alert("上传的文件不是Typecho插件和模板")</script>';
								exit(0);
							}
                        }
                }
                if($path!==''){
                    if($zip->extractTo($path)){
                        $zip->close();
                        echo '<script>alert("安装成功，请到 控制台-->' . $upType . ' 中激活使用。")</script>';
                    }else{
                        exit('<script>alert('. $upType . "解压失败,请确认" . $upType . '目录是否有写权限。")</script>');
                    }

                }  else {
                    echo '<script>alert("法获取正确的解压路径，解压失败。")</script>';
                }

            } else {
                exit( 'FAILURE:无法打开压缩包，请重试。');
            }
        }else{
            $this->widget('Widget_Archive@404', 'type=404')->render();
            exit;
        }
    }
    /**
     * 判断上传文件是否为插件
     * @param type $contents
     * @return boolean
     */
    public  function isPlugin($contents){
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
    public  function isTheme($contents){
         $info =  $this->parseInfo($contents);
         if($info['title']!=="" && !empty($info['version']) && !empty($info['author'])){
            return TRUE;
         }else{
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
                $name=split('_',$token[1]);
                $info['name']=$name[0];
                break;
            }
        }
        return $info;
    }

    public function action()
    {        
        $this->on($this->request->is('del'))->del($this->request->del);
        $this->on($this->request->is('delTheme'))->delTheme($this->request->delTheme); 
        $this->on($this->request->is('upload'))->upload($this->request->upload);
        $this->response->goBack();
    }

}
?>
