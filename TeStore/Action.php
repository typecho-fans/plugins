<?php ! defined('__TYPECHO_ROOT_DIR__') and exit();

include_once 'unzip.php';

class TeStore_Action extends Typecho_Widget {

    private $server;

    //缓存时间(h)
    private $cacheTime;

    private $cacheDir;

    private $pluginRoot;

    private $pluginInfo;

    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);

        $pluginOpts = Typecho_Widget::widget('Widget_Options')->plugin('TeStore');
        $this->server = $pluginOpts->server;
        $this->cacheTime = $pluginOpts->cache_time;
        $this->cacheDir = dirname(__FILE__) . '/data/';
        $this->pluginRoot = __TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__ . '/';

        $this->getAppData();

        define('TYPEHO_ADMIN_PATH', __TYPECHO_ROOT_DIR__ . __TYPECHO_ADMIN_DIR__.'/');
    }

    /**
     * 获取应用的数据
     */
    private function getAppData()
    {
        //数据文件在缓存期内
        if( $this->cacheTime && $this->getLastTime() + $this->cacheTime * 3600 >= time() ){
            $this->getCacheInfo();
        }else{
            $this->parseRemoteData();
        }
        return $this->pluginInfo;
    }

    /**
     * 获取最后的缓存文件的时间
     *
     */
    private function getLastTime()
    {
        $cacheTime = 0;
        $files = scandir($this->cacheDir);
        foreach ($files as $fileName) {
            if( (int)$fileName > $cacheTime)
                $cacheTime = (int)$fileName;
        }

        return $cacheTime;
    }

    /**
     * 获取缓存数据
     */
    private function getCacheInfo()
    {
        $lastTime = $this->getLastTime();
        $files = scandir($this->cacheDir);
        foreach ($files as $fileName) {
            if(  $lastTime > (int)$fileName && $fileName != '.' && $fileName != '..' ){
                unlink($this->cacheDir . $fileName);
            }
        }
        $data = file_get_contents($this->cacheDir . $lastTime . '.json');
        $this->pluginInfo = json_decode($data);
    }

    /**
     * 解析数据
     *
     */
    private function parseRemoteData()
    {
        @$html = file_get_contents($this->server);
        preg_match_all("/<a href=\"(.+zip).+\">(.+?)<\/a>.+\s*<td.*>(.+?)<\/td>\s*<td>(.+?)<\/td>\s*<td><a href=\"(.+)\">(.+)<\/a><\/td>\s*<td><a href=\"(.+)\">(.+)<\/a>/", $html, $this->pluginInfo);
        array_shift($this->pluginInfo);

        if ( count($this->pluginInfo) && !empty($this->pluginInfo[0]) ){
            $this->formatePluginInfo();
        }else{
            $this->pluginInfo = NULL;
        }
        
        $this->cachePluginInfo();

        return $this->pluginInfo;
    }

    /**
     * 格式化缓存数据，把正则解析后的数据对应起来
     */
    private function formatePluginInfo()
    {
        $pluginData = array();
        $fieldNum = count($this->pluginInfo);
        $fieldName = array('zipFile', 'pluginName', 'desc', 'version', 'site', 'author', 'pluginUrl', 'source');
        foreach( range(0, count($this->pluginInfo[0]) - 1 ) as $plugIdx ){
            foreach ( range(0, $fieldNum - 1 ) as $fieldIdx) {
                $pluginData[$plugIdx]->$fieldName[$fieldIdx] = $this->pluginInfo[$fieldIdx][$plugIdx];
            }
        }

        $this->pluginInfo = $pluginData;
    }

    /**
     * 缓存数据
     */
    private function cachePluginInfo()
    {
        $pluginInfo = json_encode($this->pluginInfo);
        file_put_contents($this->cacheDir . time() . '.json', $pluginInfo);
    }

    private function getActivePlugins()
    {
        $activatedPlugins = Typecho_Plugin::export();
        return array_keys( $activatedPlugins['activated'] );
    }

    /**
     * 获取目录下的文件夹
     *
     * @access private
     * @return array
     */
    private static function getDir($targetDir)  
    {
        $dirs = array();
        $files = scandir($targetDir);
        foreach($files as $file){
            if( is_dir($targetDir . '/' . $file) && !in_array($file, array('.', '..')) ){
                $dirs[] = $file;
            }
        }
        return $dirs;
    }

    public function market()
    {
        $options = Helper::options();
        $pluginPath = Typecho_Common::url('TeStore', $options->pluginUrl);
        $pluginInfo = $this->pluginInfo;
        $activatedPlugins = $this->getActivePlugins();
        $installPlugins = self::getDir($this->pluginRoot);

        include_once 'views/market.php';
    }

    public function install()
    {
        $plugin  = $this->request->get('plugin');
        $pluginPath = $this->pluginRoot . '/' . $plugin . '/';
        $pluginInfo = $this->getPluginByName($plugin);
        $tempFile = dirname(__FILE__) . "/.tmp/" . $pluginInfo->pluginName . '.zip';
        $activatedPlugins = $this->getActivePlugins();

        $ret = array(
            'status' => false,
            'error' => '',
        );

        if( in_array($plugin, $activatedPlugins) ){
            $ret['error'] = '无法安装已经安装的插件';
        }else{
            if( false !== $pluginInfo ){
                $zipFile = file_get_contents($pluginInfo->zipFile);
                file_put_contents( $tempFile, $zipFile );

                @mkdir($pluginPath);
                $unzip = new Unzip();
                if( ! $unzip->extract($tempFile, $this->pluginRoot) ){
                    $ret['error'] = $unzip->error_string();
                }else{
                    $ret['status'] = true;
                }

                chmod($tempFile, 0755);
                @unlink($tempFile);
                echo json_encode($ret);
            }
        }
    }

    public function uninstall()
    {
        $plugin  = $this->request->get('plugin');
        $activatedPlugins = $this->getActivePlugins();
        $ret = array(
            'status' => false
        );

        if( ! in_array($plugin, $activatedPlugins) ){
            $pluginPath = $this->pluginRoot . $plugin . '/';
            @self::delTree($pluginPath);
            $ret['status'] = true;
        }

        echo json_encode($ret);
    }

    public function getPluginByName($name)
    {
        foreach ($this->pluginInfo as $plugin) {
            if( $plugin->pluginName == $name )
                return $plugin;
        }
        return false;
    }

    public static function delTree($dir) { 
        $files = array_diff(scandir($dir), array('.','..')); 
        foreach ($files as $file) { 
            (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file"); 
        } 
        return rmdir($dir); 
    }
}
