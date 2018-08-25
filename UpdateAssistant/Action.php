<?php
/**
 * Typecho update assistant.
 *
 * @package UpdateAssistant
 * @author  mrgeneral
 * @version 1.0.1
 * @link    https://www.chengxiaobai.cn
 */

class UpdateAssistant_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $pluginRootPath;

    private $isDevelop;

    /**
     * @var array realPath => realRootPath
     */
    private $updateList = [
        // admin
        __TYPECHO_ROOT_DIR__ . __TYPECHO_ADMIN_DIR__             => __TYPECHO_ROOT_DIR__,
        // var
        __TYPECHO_ROOT_DIR__ . DIRECTORY_SEPARATOR . 'var/'      => __TYPECHO_ROOT_DIR__,
        //index.php
        __TYPECHO_ROOT_DIR__ . DIRECTORY_SEPARATOR . 'index.php' => __TYPECHO_ROOT_DIR__,
    ];

    /**
     * @var array realPath => rawName
     */
    private $updateNameMap = [
        __TYPECHO_ROOT_DIR__ . __TYPECHO_ADMIN_DIR__             => 'admin',
        __TYPECHO_ROOT_DIR__ . DIRECTORY_SEPARATOR . 'var/'      => 'var',
        __TYPECHO_ROOT_DIR__ . DIRECTORY_SEPARATOR . 'index.php' => 'index.php',
    ];

    public function __construct($request, $response, $params = null)
    {
        parent::__construct($request, $response, $params);

        if (!Typecho_Widget::widget('Widget_User')->pass('administrator', true)) {
            throw new Typecho_Exception(_t('Forbidden'), 403);
        }

        $this->pluginRootPath = dirname(realpath(__FILE__));
        $this->isDevelop      = (bool)Helper::options()->plugin('UpdateAssistant')->isDevelop;

        $this->autoLoad();
    }

    public function action()
    {
        if (Version::compare(Typecho_Common::VERSION, Version::getVersion($this->isDevelop), '=')) {
            $this->response('Already up-to-date!');
        }

        // download
        $archiveName = ($this->isDevelop ? 'develop_' : 'release_') . Version::toString(Version::getVersion($this->isDevelop));
        Downloader::down($this->isDevelop, $archiveName, $this->pluginRootPath);

        // backup
        Archive::compress(sprintf('local_%s', Version::toString(Typecho_Common::VERSION)), $this->updateList, $this->pluginRootPath);

        // decompression
        $resultPath = Archive::decompression($archiveName, $this->pluginRootPath);

        // update
        foreach ($this->updateList as $realPath => $realRootPath) {
            Archive::clearPath($realPath);
            rename($resultPath . $this->updateNameMap[$realPath], $realPath);
        }

        // finish
        $this->response('success');
    }

    public function getVersion()
    {
        $this->response(Version::getVersion($this->isDevelop));
    }

    protected function response($data, $code = 0)
    {
        headers_sent() || header('Content-Type: application/json; charset=utf-8;');

        echo json_encode(
            [
                'code' => $code,
                'data' => $data,
            ], JSON_UNESCAPED_UNICODE
        );

        exit(0);
    }

    protected function autoLoad()
    {
        $dependencePath = $this->pluginRootPath . DIRECTORY_SEPARATOR . 'library';

        // First load base class
        include_once $dependencePath . DIRECTORY_SEPARATOR . 'Base.php';

        foreach (scandir($dependencePath) as $item) {
            if (strpos($item, '.php') !== false && strpos($item, 'Base.php') === false) {
                include_once $dependencePath . DIRECTORY_SEPARATOR . $item;
            }
        }
    }
}