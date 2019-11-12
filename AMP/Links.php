<?php
error_reporting(E_ALL);
include 'header.php';
include 'menu.php';
date_default_timezone_set('PRC');

$user = Typecho_Widget::widget('Widget_User');
if(!$user->pass('administrator')){
    die('未登录用户!');
}

if (isset($_GET['send'])) {
    $http = Typecho_Http_Client::get();
    $db = Typecho_Db::get();

    //URL分页
    if (isset($_GET['page'])) {
        $page = (int)($_GET['page']);
    } else {
        $page = 1;
    }
    //URL类型
    if ((isset($_GET['type']) and $_GET['type'] == 'amp') OR (isset($_POST['type']) and $_POST['type'] == 'amp')) {
        $sendtype = 'amp';
        $type = 'amp';
    } elseif ((isset($_GET['type']) and $_GET['type'] == 'mip') OR (isset($_POST['type']) and $_POST['type'] == 'mip')) {
        $sendtype = 'mip';
        $type = 'mip';
    } elseif ((isset($_GET['type']) and $_GET['type'] == 'batch') OR (isset($_POST['type']) and $_POST['type'] == 'batch')) {
        $sendtype = 'mip';
        $type = 'batch';
        if (isset(Helper::options()->plugin('AMP')->baiduAPPID) and isset(Helper::options()->plugin('AMP')->baiduTOKEN)) {
            $appid = trim(Helper::options()->plugin('AMP')->baiduAPPID);//过滤空格
            $token = trim(Helper::options()->plugin('AMP')->baiduTOKEN);//过滤空格
            $api = "http://data.zz.baidu.com/urls?appid={$appid}&token={$token}&type=batch";
        } else {
            throw new Typecho_Widget_Exception('未设置熊掌号参数！');
        }
    } else {
        $sendtype = 'mip';
        $type = 'mip';
    }

    $articleList = Typecho_Widget::widget('AMP_Action')->MakeArticleList($sendtype, $page, 20);


    //接口类型
    if (!isset($api)) {
        if (empty(Helper::options()->plugin('AMP')->baiduAPI)) {
            throw new Typecho_Widget_Exception('未设置MIP/AMP推送接口调用地址!');
        } else {
            $api = trim(Helper::options()->plugin('AMP')->baiduAPI); //过滤空格
            $api = preg_replace("/&type=[a-z]+/", "&type={$sendtype}", $api);//替换接口中的类型

        }
    }

    $urls = array();
    foreach ($articleList AS $article) {
        if(Helper::options()->plugin('AMP')->PostURL !== Helper::options()->index){
            $article['permalink']=str_replace(Helper::options()->index,Helper::options()->plugin('AMP')->PostURL,$article['permalink']);//替换提交的前缀
        }
        echo '正在提交:' . $article['permalink'] . " <br>";
        $urls[] = $article['permalink'];
    }


    if (count($urls) > 0) {
        $http->setData(implode("\n", $urls));
        $http->setHeader('Content-Type', 'text/plain');
        try {
            $api = trim($api); //经过单步调试，发现api这个字符串前面多了一个空格，导致parse_url无法解析正确的`host`
            $result = $http->send($api);
        } catch (Exception $e) {
            throw new Typecho_Plugin_Exception(_t('对不起, 您的主机不支持远程访问。<br>请检查 curl 扩展、allow_url_fopen和防火墙设置！<br><hr>出错信息：'.$e->getMessage()));
        }

//    string '{"remain":4999960,"success":0,"not_valid":[""]}'
//    string '{"success_mip":20,"remain_mip":9980}' (length=36)
//    $result='{"success_amp":20,"remain_amp":9980}';
//    string(43) "{"success_batch":20,"remain_batch":4999960}"

        $obj = json_decode($result, true);
        $name = "success_{$type}";

        if (isset($obj[$name])) {

            echo '<hr>';
            echo "第{$page}页提交成功,";
            $count = $obj["remain_{$type}"];
            echo "还可提交{$count}条URL,准备提交下一页>>>";
            $page += 1;

            ?>
            <script language="JavaScript">
                window.setTimeout("location='<?php $options->adminUrl('extending.php?panel=AMP/Links.php' . "&send=1&type={$type}&page={$page}");
                    ?>'", 2000);
            </script>
            未自动跳转请点击<a
                    href="<?php $options->adminUrl('extending.php?panel=AMP/Links.php' . "&send=1&type={$type}&page={$page}"); ?>">这里</a>
            <?php
        } else {
            echo "<hr>错误提示：";
            print_r($obj);
            echo "<br>提交失败，请检查提交地址。如有必要，请将错误提示<a href='https://github.com/holmesian/Typecho-AMP/issues'>反馈给作者</a>";
        }
    } else {
        echo "已全部提交完成";
        ?>
        <script language="JavaScript">
            window.setTimeout("location='<?php $options->adminUrl('extending.php?panel=AMP/Links.php');?>'", 2000);
        </script>
        未自动跳转请点击<a href="<?php $options->adminUrl('extending.php?panel=AMP/Links.php'); ?>">这里</a>
        <?php
    }
} else {
    ?>
    <div class="main">
        <div class="body container">
            <?php include 'page-title.php'; ?>
            <div class="row typecho-page-main" role="main">
                <form action="<?php $options->adminUrl('extending.php?panel=AMP/Links.php&send=1'); ?>" method="POST">
                    <div class="operate" style="text-align: center;">
                        <select name="type" style="width:200px;text-align-last: center;">
                            <option value="amp">AMP</option>
                            <option value="mip">MIP</option>
                            <option value="batch">熊掌号</option>
                        </select>
                        <button type="submit" class="btn btn-s"><?php _e('开始提交'); ?></button>
                    </div>
                </form>
                <div>
                    <p>1.AMP（Accelerated Mobile
                        Pages），是谷歌的一项开放源代码计划，可在移动设备上快速加载的轻便型网页，旨在使网页在移动设备上快速加载并且看起来非常美观。选择该项为自动向百度提交AMP页面地址。</p>
                    <p>2.MIP(Mobile Instant Page -
                        移动网页加速器)，是一套应用于移动网页的开放性技术标准。通过提供MIP-HTML规范、MIP-JS运行环境以及MIP-Cache页面缓存系统，实现移动网页加速。选择该项为自动向百度提交页面地址。</p>
                    <p>
                        3.熊掌号，是百度熊掌号是内容和服务提供者入驻百度生态的实名账号。通过历史内容接口，每天可提交最多500万条有价值的内容，所提交内容会进入百度搜索统一处理流程。请先设置好APPID和TOKEN后再进行提交。</p>
                    <p></p>
                    <p><b>如果因服务器环境无法自动提交</b>，可打开<a target="_blank"
                                                  href="<?php print(Helper::options()->index . '/amp_sitemap.xml?txt=1'); ?>">AMP网址列表</a>、<a
                                target="_blank"
                                href="<?php print(Helper::options()->index . '/mip_sitemap.xml?txt=1'); ?>">MIP网址列表</a>，手动复制URL提交到百度站长后台。
                    </p>
                </div>

            </div><!-- end .typecho-page-main -->
        </div>
    </div>
    <?php
}
include 'copyright.php';
include 'common-js.php';
include 'table-js.php';
?>
<?php
include 'footer.php';
?>
