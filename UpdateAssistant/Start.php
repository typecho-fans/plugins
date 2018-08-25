<?php
/**
 * Typecho update assistant.
 *
 * @package UpdateAssistant
 * @author  mrgeneral
 * @version 1.0.1
 * @link    https://www.chengxiaobai.cn
 */

include 'common.php';
include 'header.php';
include 'menu.php';
include 'library/Base.php';
include 'library/Version.php';
?>

<?php
$isDevelop     = (bool)Helper::options()->plugin('UpdateAssistant')->isDevelop;
$remoteVersion = Version::getVersion($isDevelop);
?>
<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12">
                <div id="typecho-welcome">
                    <?php if (!Version::compare(Typecho_Common::VERSION, $remoteVersion, '=')): ?>
                        <h3><?php _e('<strong class="warning">%s</strong>', 'Found new edition'); ?></h3>
                        <ul>
                            <li><?php _e('Blog will be upgraded from <strong>%s</strong> to <strong>%s (%s)</strong>.', Typecho_Common::VERSION, $remoteVersion, $isDevelop ? 'dev' : 'release'); ?></li>
                            <br>
                            <li><?php _e('Please be patient. <strong>Depend on the network speed</strong>.'); ?></li>
                            <br>
                            <li><?php _e('Don\'t worry! The system will automatically backup your data. You can find it at <strong>\'%s\'</strong>', 'usr/plugins/UpdateAssistant/archive/back/'); ?></li>
                        </ul>
                        <p>
                            <button class="btn primary" id="start_action"><?php _e('Action'); ?></button>
                        </p>
                    <?php else: ?>
                        <h3><?php _e('Already up-to-date'); ?></h3>
                        <li><?php _e('Current Version : <strong>%s</strong> Remote Version : <strong>%s (%s)</strong>', Typecho_Common::VERSION, $remoteVersion, $isDevelop ? 'dev' : 'release'); ?></li>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
?>
<script>
    $("#start_action").click(function () {
        $(this).text("...").attr("disabled", "true");
        $.ajax({
            type: "GET",
            url: "<?php echo Helper::options()->siteUrl . 'update-assistant/version/process';?>",
            cache: false,
            async: true
        }).success(function (data) {
            var button = $("#start_action");
            button.text("Success");
            if (data.code === 0) {
                alert("Congratulations! Please login again");
                location.href = "<?php echo Helper::options()->siteUrl . "action/logout"; ?>";
            } else {
                button.text("Retry").removeAttr("disabled");
                alert(data.data);
            }
        }).error(function (xhr, status, error) {
            alert("Request fail!");
        });
    });
</script>
<?php include 'footer.php'; ?>
