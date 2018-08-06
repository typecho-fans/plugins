<?php ! defined('__TYPECHO_ROOT_DIR__') and exit();

include_once 'pclzip.lib.php';

class TeStore_Action extends Typecho_Widget {

    private $options;
    private $server;
    private $security;

    //缓存时间(h)
    private $cacheTime;

    private $cacheDir;

    private $pluginRoot;

    private $pluginInfo;

    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);

        $this->options = Helper::options();
        $pluginOpts = $this->options->plugin('TeStore');
        $this->source = array_filter(preg_split("/(\r|\n|\r\n)/", strip_tags($pluginOpts->source)));
        $this->security = Helper::security();
        $this->cacheTime = $pluginOpts->cache_time;
        $this->pluginRoot = __TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__;
        $this->cacheDir = $this->pluginRoot . '/TeStore/data/';

        define('TYPEHO_ADMIN_PATH', __TYPECHO_ROOT_DIR__ . __TYPECHO_ADMIN_DIR__);
    }

    /**
     * 获取已启用插件名称
     *
     * @access private
     * @return array
     */
    private function getActivePlugins()
    {
        $activatedPlugins = Typecho_Plugin::export();
        return array_keys( $activatedPlugins['activated'] );
    }

    /**
     * 获取已安装插件名称
     *
     * @access private
     * @return array
     */
    private function getLocalPlugins()
    {
        $dirs = array();
        $files = scandir($this->pluginRoot);
        foreach($files as $file){
            if( is_dir($this->pluginRoot . '/' . $file) && !in_array($file, array('.', '..')) || strpos($file, '.php') ){
                $dirs[] = str_replace('.php', '', $file);
            }
        }
        return $dirs;
    }

    /**
     * 获取已安装插件信息
     *
     * @access private
     * @return string
     */
    private function getLocalInfos($name)
    {
        $pluginDir = $this->pluginRoot . '/' . $name;
        $plugin = is_dir($pluginDir) ? $pluginDir . '/Plugin.php' : $pluginDir . '.php';
        $parseInfo = Typecho_Plugin::parseInfo($plugin);
        return array( $parseInfo['author'], $parseInfo['version'] );
    }

    /**
     * 获取插件源数据
     *
     * @access private
     * @param string $name
     * @return array
     */
    public function getPluginData($name='')
    {
        $json = $this->cacheDir . 'list.json';
        //读取缓存文件
        if ( $this->cacheTime && is_file($json) && (time() - filemtime($json)) <= $this->cacheTime * 3600 ) {
            $data = file_get_contents($this->cacheDir . 'list.json');
            $this->pluginInfo = json_decode($data);
        }else{
            $html = '';
            foreach($this->source as $page){
                $page = trim($page);
                if ($page) {
                    $html .= @file_get_contents($page);
                }
            }
            //解析表格内容
            if ($html) {
                $dom = new DOMDocument();
                @$dom->loadHTML($html);
                $tr = $dom->getElementsByTagName("tr");
                $texts = array();
                $urls = array();
                foreach( $tr as $trKey => $text ){
                    if($text->parentNode->tagName=='tbody') {
                        //分割tr文本数据
                        $texts[] = array_filter(preg_split("/(\r|\n|\r\n)/", $text->nodeValue));
                        $td = $tr->item($trKey)->getElementsByTagName("td");
                        //获取td链接数据
                        foreach( $td as $tdKey => $val ){
                            if( $tdKey!==1 && $tdKey!==2 ) {
                                $a = $td->item($tdKey)->getElementsByTagName("a");
                                $href = $a->item(0)->getAttribute("href");
                                //处理多作者链接
                                if ( $tdKey==3 ) {
                            	       $href = '';
                            	       foreach( $a as $a ){
                            	           $href .= ', ' . $a->getAttribute('href');
                            		}
                                }
                                $urls[] = $href;
                            }
                        }
                    }
                }
                $urls = array_chunk($urls , 3);
                $datas = array();
                //合并关联键名
                $names = array();
                foreach( $texts as $key => $val ){
                    $keys = array('pluginName', 'desc', 'version', 'author', 'source', 'pluginUrl', 'site', 'zipFile');
                    $names[] = isset($val[0]) ? $val[0] : $val[1]; //fix for php 7.0+
                    $datas[] = (object)array_combine($keys, array_merge($val, $urls[$key]));
                }
                array_multisort($names, SORT_ASC, $datas);

                $this->pluginInfo = $datas;
            }else{
                $this->pluginInfo = NULL;
            }

            //生成缓存文件
            if($this->cacheTime) {
                $pluginInfo = json_encode($this->pluginInfo);
                if (!is_dir($this->cacheDir)) @mkdir($this->cacheDir);
                file_put_contents($this->cacheDir . 'list.json', $pluginInfo);
            }
        }

        //获取单一插件数据
        if( $name && $this->pluginInfo ) {
            foreach ($this->pluginInfo as $plugin) {
                if( $plugin->pluginName == $name )
                return $plugin;
            }
        }

        return $this->pluginInfo;
    }

    /**
     * 输出插件列表
     *
     * @access private
     * @return void
     */
    public function market()
    {
        $options = $this->options;
        $pluginPath = Typecho_Common::url('TeStore', $options->pluginUrl);
        $security = $this->security;
        $marketUrl = $options->index . __TYPECHO_ADMIN_DIR__ . 'te-store/market';

        include_once 'views/market.php';
    }

    /**
     * 执行安装插件
     *
     * @access private
     * @return string
     */
    public function install()
    {
        $this->security->protect();
        $plugin  = $this->request->get('plugin');

        $ret = array(
            'status' => false,
            'error' => '',
        );

        if ($plugin) {
            $pluginInfo = $this->getPluginData($plugin);
            $tempdir = $this->pluginRoot . '/TeStore/.tmp';
            $tempFile = $tempdir. '/' . $plugin . '.zip';
            $activated = $this->getActivePlugins();

            if( in_array($plugin, $activated) ){
                $ret['error'] = _t('请先禁用该插件');
            }elseif( false !== $pluginInfo ){
                if (!is_dir($tempdir)) @mkdir($tempdir);
                $zipFile = file_get_contents($pluginInfo->zipFile);
                file_put_contents( $tempFile, $zipFile );

                $unzip = new PclZip($tempFile);
                if( ! $unzip->extract(PCLZIP_OPT_PATH, $tempdir)===0 ){
                    $ret['error'] = $unzip->errorInfo(true);
                } else {
                    @unlink($tempFile);
                    //遍历解压文件层级
                    foreach( new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempdir)) as $filename ){
                        if (!is_dir($filename)) {
                            $scans[] = $filename;
                        }
                    }
                    //处理单文件型插件
                    if ( count($scans)==1 && !strpos($scans[0], 'Plugin.php') ) {
                        rename($scans[0], $this->pluginRoot. '/' . basename($scans[0]));
                        $ret['status'] = true;
                    } else {
                        //以Plugin.php确定目录
                        foreach($scans as $scan){
                            if(strpos($scan, 'Plugin.php')){
                                $truedir = dirname($scan);
                            }
                        }
                        if (isset($truedir)) {
                            foreach($scans as $scan){
                                //按插件名创建目录
                                $tar = str_replace(( strpos($scan, $truedir)===0 ? $truedir : $tempdir ), $this->pluginRoot. '/' . $plugin, $scan);
                                $tar_dir = dirname($tar);
                                if (!is_dir($tar_dir)) @mkdir($tar_dir, 0777, true);
                                rename($scan, $tar);
                            }
                            $ret['status'] = true;
                            @$this->delTree($tempdir, true);
                        }
                    }
                }
            }
        }

        echo json_encode($ret);
    }

    /**
     * 执行卸载插件
     *
     * @access private
     * @return string
     */
    public function uninstall()
    {
        $this->security->protect();
        $plugin  = $this->request->get('plugin');
        $installed = $this->getLocalPlugins();
        $ret = array(
            'status' => false
        );

        if( $plugin && in_array($plugin, $installed) ) {
            $activated = $this->getActivePlugins();
            //自动禁用处理
            if( in_array($plugin, $activated) ){
                Helper::removePlugin($plugin);
            }
            @$this->delTree($this->pluginRoot. '/' . $plugin);
            $ret['status'] = true;
        }

        echo json_encode($ret);
    }

    /**
     * 清空指定文件夹
     *
     * @access private
     * @return boolean
     */
    private function delTree($dir,$tmp=false) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) { 
            (is_dir("$dir/$file") || $tmp) ? $this->delTree("$dir/$file") : unlink("$dir/$file"); 
        }
        if ($tmp) return;
        return rmdir($dir); 
    }
}
