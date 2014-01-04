<?php if(!defined('__DIR__')) exit; ?>
<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>
<?php
$default = unserialize('a:25:{s:5:"index";a:3:{s:3:"url";s:1:"/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:7:"archive";a:3:{s:3:"url";s:6:"/blog/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:2:"do";a:3:{s:3:"url";s:22:"/action/[action:alpha]";s:6:"widget";s:9:"Widget_Do";s:6:"action";s:6:"action";}s:4:"post";a:3:{s:3:"url";s:24:"/archives/[cid:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:10:"attachment";a:3:{s:3:"url";s:26:"/attachment/[cid:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:8:"category";a:3:{s:3:"url";s:17:"/category/[slug]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:3:"tag";a:3:{s:3:"url";s:12:"/tag/[slug]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:6:"author";a:3:{s:3:"url";s:22:"/author/[uid:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:6:"search";a:3:{s:3:"url";s:19:"/search/[keywords]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:10:"index_page";a:3:{s:3:"url";s:21:"/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:12:"archive_page";a:3:{s:3:"url";s:26:"/blog/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:13:"category_page";a:3:{s:3:"url";s:32:"/category/[slug]/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:8:"tag_page";a:3:{s:3:"url";s:27:"/tag/[slug]/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:11:"author_page";a:3:{s:3:"url";s:37:"/author/[uid:digital]/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:11:"search_page";a:3:{s:3:"url";s:34:"/search/[keywords]/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:12:"archive_year";a:3:{s:3:"url";s:18:"/[year:digital:4]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:13:"archive_month";a:3:{s:3:"url";s:36:"/[year:digital:4]/[month:digital:2]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:11:"archive_day";a:3:{s:3:"url";s:52:"/[year:digital:4]/[month:digital:2]/[day:digital:2]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:17:"archive_year_page";a:3:{s:3:"url";s:38:"/[year:digital:4]/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:18:"archive_month_page";a:3:{s:3:"url";s:56:"/[year:digital:4]/[month:digital:2]/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:16:"archive_day_page";a:3:{s:3:"url";s:72:"/[year:digital:4]/[month:digital:2]/[day:digital:2]/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:12:"comment_page";a:3:{s:3:"url";s:53:"[permalink:string]/comment-page-[commentPage:digital]";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:4:"feed";a:3:{s:3:"url";s:20:"/feed[feed:string:0]";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:4:"feed";}s:8:"feedback";a:3:{s:3:"url";s:31:"[permalink:string]/[type:alpha]";s:6:"widget";s:15:"Widget_Feedback";s:6:"action";s:6:"action";}s:4:"page";a:3:{s:3:"url";s:12:"/[slug].html";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}}');
?>

<div class="main">
    <div class="body container">
        <div class="col-group">
            <div class="typecho-page-title col-mb-12">
                <h2>高级路由设置</h2>
            </div>
        </div>
        <div class="col-group typecho-page-main">
            <div class="col-mb-12 col-tb-8 col-tb-offset-2">
                <form action="<?php $options->index('/action/RoutesHelper?restore'); ?>" method="post" enctype="application/x-www-form-urlencoded">
                    <ul class="typecho-option" id="routeshelper-option-item-restore-0">
                        <li>
                            <label class="typecho-label">路由还原</label>
                            <button type="submit" class="primary">恢复系统默认路由</button>
                            <p class="description">恢复为程序安装时默认的路由，不影响由插件增加的路由。</p>
                        </li>
                    </ul>
                </form>
<?php 
$routingTable = Helper::options()->routingTable;
if (isset($routingTable[0])) unset($routingTable[0]);
?>
                <form action="<?php $options->index('/action/RoutesHelper?edit'); ?>" method="post" enctype="application/x-www-form-urlencoded">
                    <ul class="typecho-option" id="">
                        <li>
                            <label class="typecho-label">路由表</label>
                            <?php foreach ($routingTable as $key => $value){ ?>
                            <span class="multiline">
                                <input id="route-<?php echo $key; ?>" name="<?php echo $key; ?>" type="text" class="w-60 text-s mono" value="<?php echo $value['url']; ?>" <?php if (!isset($default[$key])||'do'==$key) echo 'disabled="disabled" '; ?>/>
                                 => <label for="route-<?php echo $key; ?>" id="for-route-<?php echo $key; ?>" <?php if(isset($default[$key])){if($value['url']!=$default[$key]['url']) echo 'style="color:red;font-weight:bold;"';}else{echo 'style="color:blue;font-weight:bold;"';}?>>[ <?php echo $key; ?> ]</label>
                            </span>
                            <?php } ?>
                            <p class="description">1. 与默认路由不同的以红色显示，由插件增加的路由以蓝色显示。<br/>2. [ do ] 为后台路由，避免出现问题，本插件默认不允许修改插件路由和后台路由。</p>
                        </li>
                    </ul>
                    <ul class="typecho-option typecho-option-submit" id="routeshelper-option-item-submit-0">
                        <li>
                            <button type="submit" class="primary">保存设置</button>
                        </li>
                    </ul>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>
