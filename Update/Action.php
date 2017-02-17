<?php 
class Update_Action extends Typecho_Widget implements Widget_Interface_Do {
    public $_dir;
    const lastestUrl = "https://github.com/typecho/typecho/archive/master.zip";

    public function __construct($request, $response, $params = NULL) {
        parent::__construct($request, $response, $params);

        $this->_dir ='.'. __TYPECHO_PLUGIN_DIR__.'/Update/';
        if(method_exists($this, $this->request->step))
            call_user_func(array($this, $this->request->step));
        else $this->zero();
    }

    //升级初章开启进程线
    public function zero() {
        $url = Helper::options()->index."/update/";
        $adminUrl = Helper::options()->adminUrl;
        ?>
        <h2>升级Typecho</h2>
        <div id="progress"></div>
        <script type="text/javascript">
        function update() {
            var steps = ['first', 'second', 'third', 'fourth', 'fifth', 'sixth'];
            var notices = [
                '正在备份当前版本',
                '正在从<a href="https://github.com/typecho/typecho">Github</a>下载最新版本',
                '正在解压缩升级文件',
                '正在复制升级最新版本',
                '正在清除临时文件',
                'Typecho升级成功'
            ];
            function printmsg(text, loading) {
                loading = arguments.length > 1 ? loading : true;
                text = loading ? text + "<span class='loading'>...</span>" : text;
                text = "<p>"+text+"</p>";
                document.getElementById("progress").innerHTML += text;
            }
            function ajax(url, callback) {
                var xhr;
                if(window.XMLHttpRequest) xhr = new XMLHttpRequest();
                else if(window.ActiveXObject) xhr = new ActiveXObject("Microsoft.XMLHTTP");
                else return alert("Your browser does not support XMLHTTP.");;

                xhr.onreadystatechange = function() {
                    if(xhr.readyState == 4 ) {
                        if(xhr.status == 200) callback(xhr.responseText);
                        else return alert("Network Error.");
                    } 
                }
                xhr.open("GET", url, true);
                xhr.send(null);
            }  
            (function step(s) {
                [].slice.call(document.querySelectorAll(".loading")).forEach(function(item){
                    item.innerHTML='....';
                    item.className=''
                });
                //终章结语
                if(s==steps.length) {
                    setTimeout(function() {
                        location.href="<?php echo $adminUrl; ?>";
                    }, 2000);
                    return printmsg("欢迎使用Typecho，我们将自动为你跳转到后台。如果没有自动跳转，请<a href='<?php echo $adminUrl; ?>'>点击这里</a>。", false);
                }
                printmsg(notices[s]);
                ajax("<?php echo $url; ?>"+steps[s], function(data) {
                    if( data != "" ) return printmsg(data);
                    step(s+1);
                });
            })(0)
            
            setInterval(function() {
                l = document.querySelector(".loading");
                if(l.innerHTML.length == 4) l.innerHTML = '';
                l.innerHTML += '.';
            }, 500);
        }
        update();
        </script>
        <?php
    }
    //第一步先备份
    public function first() {
        include "pclzip.lib.php";
        $backdir = $this->_dir."/backup";
        $this->clean($backdir);
        $backname = "$backdir/Backup".date("YmdHis").".zip";
        $zip = new pclZip($backname);
        $res = $zip->create(__TYPECHO_ROOT_DIR__, 
            PCLZIP_OPT_REMOVE_PATH, __TYPECHO_ROOT_DIR__);
        if( $res == 0 ) return $this->log( $zip->errorInfo(true) );
        return $backname;
    }
    //第二步下载新版本
    public function second() {
        $temp = $this->_dir."/temp/".basename(self::lastestUrl);
        $source = fopen(self::lastestUrl, "rb");
        if($source) $target = fopen($temp, "wb");
        if($target) {
            while(!feof($source)) {
                $res = fwrite($target, fread($source, 1024*8), 1024*8);
                if(!$res) return $this->log("下载新版本写入本地错误");
            }
        }
        if($source) fclose($source);
        if($target) fclose($target);
        return $temp;
    }
    //第三步解压新版本
    public function third() {
        include "pclzip.lib.php";
        $file = $this->_dir."/temp/master.zip";
        $dir = dirname($file);
        $zip = new PclZip($file);
        if( !$zip->extract(PCLZIP_OPT_PATH, $dir) === 0 ) {
            return $this->log( $zip->errorInfo(true) );
        }
        return $dir;
    }
    //第四步更新
    public function fourth() {
        $lastestDir = $this->_dir."/temp/typecho-master";
        $overWrite = array(
            "admin"=>__TYPECHO_ROOT_DIR__.__TYPECHO_ADMIN_DIR__,
            "var" => __TYPECHO_ROOT_DIR__."/var",
            "index.php" => __TYPECHO_ROOT_DIR__."/index.php"
        );
        foreach( $overWrite as $name => $to ) {
            $from = "$lastestDir/$name";
            if( is_dir($from) ) $this->copy($from, $to); 
        }
    }
    //第五步清空临时文件
    public function fifth() {
        $this->clean($this->_dir."/temp", true);
    }
    //第六步终章提示升级完毕进入后台
    public function sixth() {
    }

    private function clean($d, $r = false) {
        foreach(glob("$d/*") as $f) {
            if( is_dir($f) ) $this->clean($f, $r);
            else if(!unlink($f)) return $this->log("删除文件 $f 错误");
        }
        foreach(glob("$d/*") as $f) 
            is_dir($f) ? $this->clean($f, $r) : unlink($f);
        if($r) @rmdir($d);
    }
    private function copy($from, $to) {
        foreach(glob("$from/*") as $item) {
            $tar = str_replace($from, $to, $item);
            $tar_dir = dirname($tar);
            if( !file_exists($tar_dir) && !mkdir($tar_dir, 0777, true) ) {
                return $this->log("$tar_dir 不存在并创建失败");
            }
            if( !is_dir($item) ) {
                if( !copy($item, $tar) ) {
                    $error = error_get_last();
                    return $this->log("更新 $tar 文件发生错误，错误类型 {$error['type']} ，错误信息：{$error['message']}");
                }
            } else $this->copy_dir($item, $tar);
        }
    }
    private function log($text) {
        $text = date("Y-m-d h:i:s")." $text\r\n";
        error_log($text, 3, $this->_dir."/error.log");
        echo $text;
    }
    public function action() {
        $this->on($this->request);
    }
}

?>