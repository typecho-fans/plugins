<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

$options = Typecho_Widget::widget('Widget_Options');
$config = $options->plugin('Xiami');

$user_id = $config->user_id;
$user_type = $config->user_type;

include 'header.php';
include 'menu.php';
?>
<div class="main">
    <div class="body container">
        <div class="colgroup">
            <div class="typecho-page-title col-mb-12">
                <h2>虾米音乐同步预览</h2>
            </div>
        </div>
        <div class="colgroup typecho-page-main" role="main">
            <div class="col-mb-12 wrap typecho-list" ng-app="WPXiaMiApp" ng-controller="WPXiaMiAppController">
                <div class="wrap-box">
                    <div ng-controller="WPXiaMiAppAlertContronller">
                        <div class="wp-xiami-alert" ng-repeat="alert in alerts" ng-class="alert.type && alert.type">
                            <p ng-bind="alert.msg"></p>
                            <span type="button" class="wp-xiami-close" ng-click="alert.close()"></span>
                        </div>
                    </div>
                    <div class="wrap-menu clearfix">
                        <ng-menu></ng-menu>
                        <a href="javascript:;" class="balloon-button" ng-bind="(collects.data|WPXiaMiFilter:collects.type).length"></a>
                    </div>
                    <div id="wp-xiami-main" ng-class="{loading: !collects.data.length}" ng-collect></div>
                </div>
                <div id="wp-xiami-sync-preview" ng-show="collects.selected!==null" ng-preview></div>
            </div><!-- end .typecho-list -->
        </div>
    </div>
</div>
<?php
include 'copyright.php';
include 'common-js.php';
?>

<link rel="stylesheet" href="../usr/plugins/Xiami/static/css/style.css"/>

<script type="text/javascript">
    var global = {
        "tmpl_url":"../usr/plugins/Xiami/static/tmpl/",
        "ajax_url":"http://goxiami.duapp.com/do.php",
        "user_id": <?php echo $user_id;?>,
        "user_type": "<?php echo $user_type;?>"
    };
</script>
<script src="../usr/plugins/Xiami/static/js/angular.min.js"></script>
<script src="../usr/plugins/Xiami/static/js/angular-resource.min.js"></script>
<script src="../usr/plugins/Xiami/static/js/sync.js"></script>
<?php include 'footer.php';?>
