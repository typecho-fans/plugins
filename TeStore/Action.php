<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class TeStore_Action extends Typecho_Widget
{
    private $options;
    private $settings;
    private $security;
    private $user;
    private $useCurl;
    private $pluginRoot;

    /**
     * 构造函数与初始化
     *
     * @access public
     * @param Typecho_Request $request
     * @param Typecho_Response $response
     * @param mixed $params
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);

        $this->options = $this->widget('Widget_Options');
        $this->settings = $this->options->plugin('TeStore');
        $this->security = $this->widget('Widget_Security');
        $this->user = $this->widget('Widget_User');
        $this->useCurl = $this->settings->curl;
        $this->pluginRoot = __TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__;
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
        return array_keys($activatedPlugins['activated']);
    }

    /**
     * 获取已安装插件信息
     *
     * @access private
     * @param string $name 插件名称
     * @return array
     */
    private function getLocalInfos($name)
    {
        $infos = array();
        $pluginDir = $this->pluginRoot . '/' . $name;
        $pluginFile = is_dir($pluginDir) ? $pluginDir . '/Plugin.php' : $pluginDir . '.php';
        if (is_file($pluginFile)) {
            $parse = Typecho_Plugin::parseInfo($pluginFile);
            $infos = array(strip_tags($parse['author']), strip_tags($parse['version'])); //兼容 html 混写
        }
        return $infos;
    }

    /**
     * 读取并整理插件信息
     *
     * @access public
     * @return array
     */
    public function getPluginData()
    {
        $pluginInfo = array();
        $cacheDir = $this->pluginRoot . '/TeStore/data/';
        $cacheFile = $cacheDir . 'list.json';
        $cacheTime = $this->settings->cache_time;

        //读取缓存文件
        if ($cacheTime && is_file($cacheFile) && (time() - filemtime($cacheFile)) <= $cacheTime * 3600) {
            $data = file_get_contents($cacheFile);
            $pluginInfo = Json::decode($data, true);
            //读取表格地址
        } else {
            $html = '';
            $pages = array_filter(preg_split('/(\r|\n|\r\n)/', strip_tags($this->settings->source)));
            foreach ($pages as $page) {
                $page = trim($page);
                if ($page) {
                    $sourceHtml = $this->fetchSource($page);
                    if ($sourceHtml !== false) {
                        $html .= $sourceHtml;
                    }
                }
            }


            //解析表格内容
            if ($html) {
                $dom = new DOMDocument('1.0', 'utf-8');
                $html = function_exists('mb_convert_encoding') ? mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') : $html;
                @$dom->loadHTML($html);
                $trs = $dom->getElementsByTagName('tr');
                $tdVal = '';
                $texts = array();
                $tds = array();
                $a = (object)array();
                $href = '';
                $urls = array();
                foreach ($trs as $trKey => $trVal) {
                    if ($trVal->parentNode->tagName == 'tbody') {
                        //获取 td 纯文本
                        foreach ($trVal->childNodes as $tdKey => $td) {
                            $tdVal = $td->nodeValue;
                            if ($tdVal) {
                                $texts[$trKey][] = htmlspecialchars(trim($tdVal));
                            }
                        }
                        $tds = $trs->item($trKey)->getElementsByTagName('td');
                        $rowUrls = array(); // 每行单独收集 URL
                        //获取 td 元数据
                        foreach ($tds as $tdKey => $tdVal) {
                            if ($tdKey !== 1 && $tdKey !== 2) {
                                $a = $tds->item($tdKey)->getElementsByTagName('a');
                                $href = $a->item(0) ? $a->item(0)->getAttribute('href') : '';
                                if ($tdKey == 3) {
                                    // 获取 td 内部的 HTML 内容（作者栏）
                                    $innerHTML = '';
                                    $tdNode = $tds->item($tdKey);
                                    foreach ($tdNode->childNodes as $child) {
                                        if ($child->nodeType == XML_ELEMENT_NODE) {
                                            // 元素节点：保留完整标签
                                            $innerHTML .= $dom->saveHTML($child);
                                        } else if ($child->nodeType == XML_TEXT_NODE) {
                                            // 文本节点：直接使用文本内容
                                            $innerHTML .= $child->nodeValue;
                                        }
                                    }
                                    $href = $innerHTML;
                                }
                                $rowUrls[] = trim($href);
                            }
                        }
                        // 确保每行都有 3 个 URL 元素，不足的补空字符串
                        while (count($rowUrls) < 3) {
                            $rowUrls[] = '';
                        }
                        // 只取前 3 个元素，防止多余
                        $rowUrls = array_slice($rowUrls, 0, 3);
                        $urls = array_merge($urls, $rowUrls);
                    }
                }
                $texts = array_values($texts);
                $urls = array_chunk($urls, 3);

                //合并关联键名
                $keys = array('pluginName', 'desc', 'version', 'mark', 'pluginUrl', 'authorHtml', 'zipFile');
                $names = array();
                $vals = array();
                $datas = array();
                $i = 0;
                foreach ($texts as $key => $val) {
                    $names[] = isset($val[0]) ? $val[0] : $val[1]; //fix for PHP 7.0+
                    $vals = array_values(array_filter($val));
                    // 表格有 5 列：[0:名称，1:简介，2:版本，3:作者，4:zip 标记]
                    // 需要保留：[0:名称，1:简介，2:版本，4:zip 标记]，删除 3:作者
                    // array_filter 后重新索引，所以作者在索引 3 的位置
                    if (isset($vals[3])) {
                        unset($vals[3]); //去除作者栏 text
                        $vals = array_values($vals); //重新索引，确保连续
                    }
                    $datas[] = array_combine($keys, array_merge($vals, $urls[$key]));
                }
                //按插件名排序
                array_multisort($names, SORT_ASC, $datas);

                $pluginInfo = $datas;
            }

            //生成缓存文件
            if ($pluginInfo && $cacheTime) {
                if (!is_dir($cacheDir)) {
                    $this->makedir($cacheDir);
                }
                file_put_contents($cacheFile, Json::encode($pluginInfo));
            }
        }

        return $pluginInfo;
    }

    /**
     * 输出插件列表页面
     *
     * @access private
     * @return void
     */
    public function market()
    {
        //禁止非管理员访问
        $this->user->pass('administrator');

        include_once 'views/market.php';
    }

    /**
     * 执行安装插件步骤
     *
     * @access public
     * @return void
     */
    public function install()
    {
        $this->security->protect();
        //禁止非管理员访问
        $this->user->pass('administrator');

        $plugin = $this->request->plugin;
        $author = $this->request->author;
        $zip = $this->request->zip;
        $result = array(
            'status' => false,
            'error' => _t('没有找到插件文件')
        );

        if ($zip) {
            //检测是否已启用
            $activated = $this->getActivePlugins();
            if (!empty($activated) && in_array($plugin, $activated)) {
                $result['error'] = _t('请先禁用此插件');
            } else {
                $tempDir = $this->pluginRoot . '/TeStore/.tmp';
                $tempFile = $tempDir . '/' . $plugin . '.zip';
                if (is_dir($tempDir)) {
                    @$this->delTree($tempDir, true); //清理临时目录
                } else {
                    $this->makedir($tempDir); //创建临时目录
                }
                $zipUrls = array();
                $mirror = $this->getMirrorConfig();
                if ($mirror['type']) {
                    $cdn = $this->ZIP_CDN($plugin, $author);
                    if ($cdn) {
                        $zipUrls[] = $cdn;
                    }
                }
                $zipUrls[] = $zip;

                //下载至临时目录并校验压缩包
                $invalidZip = false;
                $phpZip = $this->downloadZip($zipUrls, $tempFile, $invalidZip);
                if (!$phpZip) {
                    $result['error'] = $invalidZip ? _t('压缩包校验错误') : _t('下载压缩包出错');
                } else {
                    //解压至临时目录
                    if (!$phpZip->extractTo($tempDir)) {
                        $error = error_get_last();
                        $result['error'] = $error['message'];
                        $phpZip->close();
                        @unlink($tempFile);
                    } else {
                        $phpZip->close();
                        @unlink($tempFile); //删除已解压包

                        //遍历各文件层级
                        $tmpRoutes = array();
                        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir)) as $fileName) {
                            if (!is_dir($fileName)) {
                                $tmpRoutes[] = $fileName;
                            }
                        }

                        //定位 Plugin.php
                        $trueDir = '';
                        $parentDir = '';
                        foreach ($tmpRoutes as $tmpRoute) {
                            if (!strcasecmp(basename($tmpRoute), 'Plugin.php')) {
                                $trueDir = dirname($tmpRoute);
                                $parentDir = dirname($trueDir);
                            }
                        }

                        //处理目录型插件
                        if ($trueDir) {
                            $pluginDir = $this->pluginRoot . '/' . $plugin;
                            if (is_dir($pluginDir)) {
                                @$this->delTree($pluginDir, true); //清理旧版残留
                            }
                            foreach ($tmpRoutes as $tmpRoute) {
                                //按文件路径创建目录
                                $fileDir = $parentDir == $tempDir ? $tempDir : $parentDir;
                                $tarRoute = str_replace((strpos($tmpRoute, $trueDir) === 0 ? $trueDir : $fileDir),
                                    $pluginDir, $tmpRoute);
                                $tarDir = dirname($tarRoute);
                                if (!is_dir($tarDir)) {
                                    $this->makedir($tarDir);
                                }
                                //移动文件到各层目录
                                if (!rename($tmpRoute, $tarRoute)) {
                                    $error = error_get_last();
                                    $result['error'] = $error['message'];
                                }
                            }
                            $result['status'] = true;

                            //处理单文件型插件
                        } elseif (count($tmpRoutes) <= 2) {
                            foreach ($tmpRoutes as $tmpRoute) {
                                $name = basename($tmpRoute);
                                if ($name == $plugin . '.php') {
                                    //移动文件到根目录
                                    if (!rename($tmpRoute, $this->pluginRoot . '/' . $name)) {
                                        $result['error'] = _t('移动文件出错');
                                    } else {
                                        $result['status'] = true;
                                    }
                                }
                            }
                        }

                        //清空临时目录
                        @$this->delTree($tempDir, true);
                    }
                }
            }
        }

        //返回提示信息
        if ($result['status']) {
            $this->widget('Widget_Notice')->highlight('plugin-' . $plugin);
            $this->widget('Widget_Notice')->set(_t('安装插件 %s 成功, 可以在下方启用', $plugin), 'success');
            $this->response->redirect($this->options->adminUrl . 'plugins.php#plugin-' . end($activated));
        } else {
            $this->widget('Widget_Notice')->set(_t('安装插件 %s 失败: %s', $plugin, $result['error']), 'error');
            $this->response->goBack();
        }
    }

    /**
     * 执行卸载插件步骤
     *
     * @access public
     * @return void
     */
    public function uninstall()
    {
        $this->security->protect();
        //禁止非管理员访问
        $this->user->pass('administrator');

        $plugin = $this->request->plugin;
        $result = array(
            'status' => false,
            'error' => _t('移除文件出错')
        );

        if ($this->getLocalInfos($plugin)) {
            $activated = $this->getActivePlugins();
            //已启用则自动禁用
            if (!empty($activated) && in_array($plugin, $activated)) {
                Helper::removePlugin($plugin);
            }

            $pluginDir = $this->pluginRoot . '/' . $plugin;
            //清空目录型插件
            if (is_dir($pluginDir)) {
                if (!@$this->delTree($pluginDir)) {
                    $error = error_get_last();
                    $result['error'] = $error['message'];
                } else {
                    $result['status'] = true;
                }
                //删除单文件插件
            } else {
                @unlink($pluginDir . '.php');
                $result['status'] = true;
            }
        }

        //返回提示信息
        if ($result['status']) {
            $this->widget('Widget_Notice')->set(_t('删除插件 %s 成功', $plugin), 'success');
        } else {
            $this->widget('Widget_Notice')->set(_t('删除插件 %s 失败: %s', $plugin, $result['error']), 'error');
        }
        $this->response->goBack();
    }

    /**
     * 检测可加速 zip 地址
     *
     * @access public
     * @param string $name 插件名称
     * @param string $author 作者名称
     * @return string
     */
    public function ZIP_CDN($name = '', $author = '')
    {
        $datas = array();
        $cacheDir = $this->pluginRoot . '/TeStore/data/';
        $cacheFile = $cacheDir . 'zip_cdn.json';
        $failureCacheFile = rtrim(sys_get_temp_dir(), '/\\') . '/testore_zip_cdn_'
            . md5($cacheFile) . '.failed';
        $cacheTime = $this->settings->cache_time;
        $cacheData = false;

        if (is_file($cacheFile)) {
            $cacheData = file_get_contents($cacheFile);
        }

        // 缓存有效时不发起远程请求。
        if ($cacheTime && $cacheData !== false && (time() - filemtime($cacheFile)) <= $cacheTime * 3600) {
            $decoded = Json::decode($cacheData, true);
            $datas = is_array($decoded) ? $decoded : array();
        } else {
            $failureCached = is_file($failureCacheFile)
                && (time() - filemtime($failureCacheFile)) <= 300;
            $data = false;

            if (!$failureCached) {
                $indexUrl = 'https://raw.githubusercontent.com/typecho-fans/plugins/master/ZIP_CDN.json';
                foreach ($this->getDownloadCandidates($indexUrl) as $candidate) {
                    $content = $this->httpGet($candidate);
                    if ($content === false) {
                        continue;
                    }
                    $decoded = Json::decode($content, true);
                    if (is_array($decoded) && $decoded) {
                        $data = $content;
                        $datas = $decoded;
                        break;
                    }
                }
            }

            if ($data !== false) {
                if ($cacheTime) {
                    if (!is_dir($cacheDir)) {
                        $this->makedir($cacheDir);
                    }
                    file_put_contents($cacheFile, $data);
                }
                @unlink($failureCacheFile);
            } else {
                // 刷新失败时继续使用旧缓存，并短暂记录失败以避免连续超时。
                if ($cacheData !== false) {
                    $decoded = Json::decode($cacheData, true);
                    $datas = is_array($decoded) ? $decoded : array();
                }
                @touch($failureCacheFile);
            }
        }

        $exactZip = '';
        $fallbackZip = '';
        if ($name && $author) {
            foreach ($datas as $data) {
                if (!isset($data['name']) || empty($data['download_url'])) {
                    continue;
                }
                if ($data['name'] == $name . '_' . $author . '.zip') {
                    $exactZip = $data['download_url'];
                    break;
                }
                if ($data['name'] == $name . '.zip') {
                    $fallbackZip = $data['download_url'];
                }
            }
        }

        return $exactZip ? $exactZip : $fallbackZip;
    }

    /**
     * 判断是否启用了镜像
     *
     * @access public
     * @return boolean
     */
    public function isMirrorEnabled()
    {
        $mirror = $this->getMirrorConfig();
        return $mirror['type'] !== '' && $mirror['endpoint'] !== '';
    }

    /**
     * 读取新镜像配置并兼容旧 proxy 字段
     *
     * @access private
     * @return array
     */
    private function getMirrorConfig()
    {
        $type = '';
        $endpoint = '';

        if (isset($this->settings->mirror_type) || isset($this->settings->mirror_endpoint)
            || isset($this->settings->mirror_custom)) {
            $type = $this->settings->mirror_type;
            $selected = isset($this->settings->mirror_endpoint) ? $this->settings->mirror_endpoint : '';
            if ($selected === 'custom') {
                $endpoint = isset($this->settings->mirror_custom) ? trim($this->settings->mirror_custom) : '';
            } else {
                $endpoint = $selected;
            }
        } elseif (isset($this->settings->proxy) && $this->settings->proxy) {
            if ($this->settings->proxy === 'cdn.jsdelivr.net/gh') {
                $type = 'cdn';
                $endpoint = 'https://cdn.jsdelivr.net';
            } elseif ($this->settings->proxy === 'jsd.onmicrosoft.cn/gh') {
                $type = 'cdn';
                $endpoint = 'https://cdn.jsdmirror.cn';
            } else {
                $type = 'proxy';
                $endpoint = $this->settings->proxy;
            }
        }

        $endpoint = rtrim($endpoint, '/');
        if ($type === 'cdn' && substr($endpoint, -3) === '/gh') {
            $endpoint = substr($endpoint, 0, -3);
        }

        $parts = $endpoint ? @parse_url($endpoint) : false;
        if (($type !== 'cdn' && $type !== 'proxy') || !$parts
            || !isset($parts['scheme']) || strtolower($parts['scheme']) !== 'https'
            || empty($parts['host']) || isset($parts['query']) || isset($parts['fragment'])
            || isset($parts['user']) || isset($parts['pass'])) {
            $type = '';
            $endpoint = '';
        }

        return array('type' => $type, 'endpoint' => $endpoint);
    }

    /**
     * 生成镜像地址和原地址候选列表
     *
     * @access private
     * @param string $url 原地址
     * @return array
     */
    private function getDownloadCandidates($url)
    {
        $candidates = array();
        $mirrorUrl = $this->buildMirrorUrl($url);
        if ($mirrorUrl && $mirrorUrl !== $url) {
            $candidates[] = $mirrorUrl;
        }
        $candidates[] = $url;
        return array_values(array_unique($candidates));
    }

    /**
     * 根据镜像类型转换地址
     *
     * @access private
     * @param string $url 原地址
     * @return string
     */
    private function buildMirrorUrl($url)
    {
        $mirror = $this->getMirrorConfig();
        if (!$mirror['type'] || !preg_match('#^https?://#i', $url)) {
            return $url;
        }

        if ($mirror['type'] === 'proxy') {
            $prefix = $mirror['endpoint'] . '/';
            return strpos($url, $prefix) === 0 ? $url : $prefix . $url;
        }

        $path = '';
        if (preg_match('#^https?://raw\.githubusercontent\.com/([^/]+)/([^/]+)/refs/(?:heads|tags)/([^/]+)/(.*)$#i', $url, $matches)) {
            $path = $matches[1] . '/' . $matches[2] . '@' . $matches[3] . '/' . $matches[4];
        } elseif (preg_match('#^https?://raw\.githubusercontent\.com/([^/]+)/([^/]+)/([^/]+)/(.*)$#i', $url, $matches)) {
            $path = $matches[1] . '/' . $matches[2] . '@' . $matches[3] . '/' . $matches[4];
        } elseif (preg_match('#^https?://github\.com/([^/]+)/([^/]+)/(?:blob|raw)/([^/]+)/(.*)$#i', $url, $matches)) {
            $path = $matches[1] . '/' . $matches[2] . '@' . $matches[3] . '/' . $matches[4];
        }

        return $path ? $mirror['endpoint'] . '/gh/' . $path : $url;
    }

    /**
     * 下载并验证插件信息源
     *
     * @access private
     * @param string $url 信息源地址
     * @return string|boolean
     */
    private function fetchSource($url)
    {
        foreach ($this->getDownloadCandidates($url) as $candidate) {
            $content = $this->httpGet($candidate);
            if ($content === false) {
                continue;
            }
            if (stripos($content, '<table') === false) {
                $content = htmlspecialchars_decode(Markdown::convert($content));
            }
            if ($this->hasPluginTable($content)) {
                return $content;
            }
        }
        return false;
    }

    /**
     * 判断内容是否包含可解析的插件表格
     *
     * @access private
     * @param string $html 页面内容
     * @return boolean
     */
    private function hasPluginTable($html)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $document = function_exists('mb_convert_encoding') ? mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') : $html;
        if (!@$dom->loadHTML($document)) {
            return false;
        }
        foreach ($dom->getElementsByTagName('tr') as $row) {
            $headers = $row->getElementsByTagName('th');
            if ($headers->length >= 5) {
                $headerText = '';
                foreach ($headers as $header) {
                    $headerText .= ' ' . trim($header->nodeValue);
                }
                if (strpos($headerText, '名称') !== false && strpos($headerText, '简介') !== false
                    && strpos($headerText, '版本') !== false && strpos($headerText, '作者') !== false
                    && stripos($headerText, 'zip') !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 下载并验证 ZIP，镜像失败时回退原地址
     *
     * @access private
     * @param array $urls 下载地址
     * @param string $tempFile 临时文件
     * @param boolean $invalidZip 是否收到无效压缩包
     * @return ZipArchive|boolean
     */
    private function downloadZip($urls, $tempFile, &$invalidZip)
    {
        $invalidZip = false;
        $candidates = array();
        foreach ($urls as $url) {
            $candidates = array_merge($candidates, $this->getDownloadCandidates($url));
        }
        foreach (array_values(array_unique($candidates)) as $candidate) {
            $zipFile = $this->httpGet($candidate);
            if ($zipFile === false || @file_put_contents($tempFile, $zipFile) === false) {
                @unlink($tempFile);
                continue;
            }

            $phpZip = new ZipArchive();
            if ($phpZip->open($tempFile, ZipArchive::CHECKCONS) === true) {
                return $phpZip;
            }
            $invalidZip = true;
            @unlink($tempFile);
        }
        return false;
    }

    /**
     * 获取远程内容并检查 HTTP 状态
     *
     * @access private
     * @param string $url 请求地址
     * @param array $headers 请求头
     * @return string|boolean
     */
    private function httpGet($url, $headers = array())
    {
        if (!preg_match('#^https?://#i', $url)) {
            return false;
        }
        if ($this->useCurl) {
            return $this->curlGet($url, $headers);
        }

        $options = array(
            'http' => array(
                'timeout' => 20,
                'ignore_errors' => true,
                'follow_location' => 1,
                'max_redirects' => 5
            )
        );
        if ($headers) {
            $options['http']['header'] = implode("\r\n", $headers);
        }
        $content = @file_get_contents($url, false, stream_context_create($options));
        $status = 0;
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#i', $header, $matches)) {
                    $status = (int)$matches[1];
                }
            }
        }
        return $content !== false && $content !== '' && $status >= 200 && $status < 400 ? $content : false;
    }

    /**
     * 递归创建本地目录
     *
     * @access private
     * @param string $path 目录路径
     * @return boolean
     */
    private function makedir($path)
    {
        $path = preg_replace('/\\\+/', '/', $path);
        $current = rtrim($path, '/');
        $last = $current;

        while (!is_dir($current) && false !== strpos($path, '/')) {
            $last = $current;
            $current = dirname($current);
        }
        if ($last == $current) {
            return true;
        }
        if (!@mkdir($last)) {
            return false;
        }

        $stat = @stat($last);
        $perms = $stat['mode'] & 0007777;
        @chmod($last, $perms);

        return $this->makedir($path);
    }

    /**
     * 清空目录内文件
     *
     * @access private
     * @param string $folder 目录路径
     * @param boolean $keep 保留目录
     * @return boolean
     */
    private function delTree($folder, $keep = false)
    {
        $files = array_diff(scandir($folder), array('.', '..'));
        foreach ($files as $file) {
            $path = $folder . '/' . $file;
            is_dir($path) ? $this->delTree($path) : unlink($path);
        }
        return $keep ? true : rmdir($folder);
    }

    /**
     * 使用 cURL 方法下载
     *
     * @access private
     * @return string
     */
    private function curlGet($url, $headers = array())
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        if (!ini_get('safe_mode') && !ini_get('open_basedir')) {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
        }
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_CAINFO, $this->pluginRoot . '/TeStore/data/cacert.pem'); //证书识别库
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); //设 30 秒超时
        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($curl, CURLOPT_URL, $url);

        $result = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return $result !== false && $result !== '' && $status >= 200 && $status < 400 ? $result : false;
    }

}
