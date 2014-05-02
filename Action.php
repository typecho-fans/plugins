<?php ! defined('__TYPECHO_ROOT_DIR__') and exit();

class AppStore_Action extends Typecho_Widget {

    /**
     * 应用商店服务器
     *
     * @var string
     */
    private $server = '';

    /**
     * 构造函数
     *
     * @param mixed $request
     * @param mixed $response
     * @param null $params
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);

        //如果没有json库，加载兼容包
        ! extension_loaded('json') and include('libs/compat_json.php');

        //加载unzip包
        include('libs/unzip.php');

        //加载助手
        include('helpers/helpers.php');

        //加载异常类
        include('libs/exceptions.php');

        //从插件设置中读取应用商店服务器地址
        $this->server = Typecho_Widget::widget('Widget_Options')->plugin('AppStore')->server;

        define('TYPEHO_ADMIN_PATH', __TYPECHO_ROOT_DIR__.__TYPECHO_ADMIN_DIR__.'/');

    }

    /**
     * 应用商店主页
     *
     */
    public function market()
    {
        //获取插件列表
        $result = json_decode(http_get($this->server.'packages.json'));

        if ($result) {

            //导出已激活插件
            $activatedPlugins = Typecho_Plugin::export();

            foreach ($result->packages as &$_package) {

                $pluginPath = __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.'/'.$_package->name.'/';

                $pluginEntry = $pluginPath.'Plugin.php';

                $_package->existed = 0;

                if (file_exists($pluginEntry)) {

                    $_package->existed = 1;

                    $pluginMeta = Typecho_Plugin::parseInfo($pluginEntry);

                    foreach ($_package->versions as &$_version) {

                        $_version->activated = 0;

                        $_version->description = strip_tags($_version->description);
                        $_version->author = strip_tags($_version->author);

                        if ($_version->version == $pluginMeta['version'] and
                            isset($activatedPlugins['activated'][$_package->name])
                        ) {
                            $_version->activated = 1;
                        }

                    }

                } else {

                    foreach ($_package->versions as &$_version) {

                        $_version->description = strip_tags($_version->description);
                        $_version->author = strip_tags($_version->author);
                        $_version->activated = 0;

                    }

                }

            }

        }

        include 'views/market.php';

    }

    public function install()
    {
        $version = $this->request->get('version');
        $plugin  = $this->request->get('plugin');
        $require = $this->request->get('require');

        $require === '*' and $require = '';

        $pluginPath = __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.'/'.$plugin.'/';
        $pluginBackupPath = __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.'/_'.$plugin.'/';
        $activatedPlugins = Typecho_Plugin::export();
        $existed = false;
        $activated = false;
        $tempFile = __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.'/.app_store/'.$plugin.'-'.$version.'.zip';

         try {

             //检查版本
             list(, $buildVersion) = explode('/', Typecho_Common::VERSION);
             if (! Typecho_Plugin::checkDependence($buildVersion, $require)) {
                 throw new VersionNotMatchException('版本不匹配，无法安装.');
             }

             //查看插件是否已经存在
             //查看插件是否已经激活
             if (file_exists($pluginPath)) {
                 $existed = true;
                 if (file_exists($pluginPath.'Plugin.php') and isset($activatedPlugins['activated'][$plugin])) {
                    $activated = true;
                 }
             }

             //插件如果存在，则需要备份下，后面出错可以进行回滚
             if ($existed or $activated) {

                 file_exists($pluginBackupPath) and delete_files($pluginBackupPath) and @rmdir($pluginBackupPath);
                 @rename($pluginPath, $pluginBackupPath);

             }

             //下载新插件zip包
             $archive = http_get($this->server.'archive/'.$plugin.'/'.str_replace(' ', '%20', $version));

             if (! $archive) {
                 throw new DownloadErrorException('下载插件包出错!');
             }

             //保存文件
             $fp = fopen($tempFile, 'w');
             fwrite($fp, $archive);
             fclose($fp);

             //解压缩文件
             $unzip = new Unzip();

             //创建文件夹
             @mkdir($pluginPath);

             $extractedFiles = $unzip->extract($tempFile, $pluginPath);

             if ($extractedFiles === false) {
                 throw new UnzipErrorException('解压缩出错!');
             }

             //OK,解压缩成功了

             //删除备份文件
             file_exists($pluginBackupPath) and delete_files($pluginBackupPath) and @rmdir($pluginBackupPath);

             //删除临时文件
             @unlink($tempFile);

             //报告首长, 安装顺利完成

             echo json_encode(array(
                 'status' => true,
                 'activated' => $activated
             ));


         } catch (VersionNotMatchException $e) {
             $e->responseJson();
         } catch (DownloadErrorException $e) {
             //如果存在备份包，则进行回滚
             file_exists($pluginBackupPath) and @rename($pluginBackupPath, $pluginPath);
             $e->responseJson();
         } catch (UnzipErrorException $e) {
             //清理解锁压缩的废弃文件
             file_exists($pluginPath) and delete_files($pluginPath) and @rmdir($pluginPath);
             //如果存在备份包，则进行回滚
             file_exists($pluginBackupPath) and @rename($pluginBackupPath, $pluginPath);
             //删除临时文件
             @unlink($tempFile);
             $e->responseJson();
         } catch(Exception $e) {
             $error = new JsonableException($e->getMessage());
             $error->responseJson();
         }
    }

}