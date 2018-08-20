<?php
//异步加载插件数据
if( $this->request->is('action=loadinfos') ){
    $pluginInfo = $this->getPluginData();
    $keywords = trim($this->request->get('keywords'));

    if($pluginInfo) {
        $installed = $this->getLocalPlugins();
        $pluginRez = array();
        //关键词筛选结果
        foreach( $pluginInfo as $plugin){
            $name = $plugin->pluginName;
            if( !$keywords || false !== stripos($name, $keywords) || false !== stripos($plugin->desc, $keywords) ){
                $pluginRez[] = $plugin;
            }
        }

        //已安装插件提前
        $pluginIns = array();
        foreach( $pluginRez as $key => $plugin){
            $name = $plugin->pluginName;
            if( in_array($name, $installed) ){
                $pluginIns[] = $plugin;
                unset($pluginRez[$key]);
            }
        }
        ksort($pluginIns);
        $pluginRez = array_merge($pluginIns, $pluginRez);

        //处理为分页数组
        $pluginInfo = array_chunk($pluginRez, 20);
        $page = $this->request->get('page');
        $page = $page && isset($pluginInfo[$page-1]) ? $page - 1 : 0;
        $keyquery = $keywords ? 'keywords=' . $keywords . '&' : '';
        $nav = new Typecho_Widget_Helper_PageNavigator_Box(count($pluginRez), $page+1, 20, $marketUrl . '?' . $keyquery . 'page={page}');
    }
?>
                <?php if( $pluginInfo || $keywords ): ?>
                <div class="typecho-list-operate clearfix">
                    <form method="get">
                        <div class="search" role="search">
                            <?php if( $keywords ): ?>
                            <a href="<?php $options->adminUrl('extending.php?panel=TeStore%2Fmarket.php'); ?>"><?php _e('&laquo; 取消筛选'); ?></a>
                            <?php endif; ?>
                            <input type="text" class="text-s" placeholder="<?php _e('请输入关键字'); ?>" value="<?php echo htmlspecialchars($keywords); ?>" name="keywords" />
                            <button type="submit" class="btn btn-s"><?php _e('筛选'); ?></button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <div class="typecho-table-wrap">
                    <table class="typecho-list-table">
                        <colgroup>
                            <col width="20%">
                            <col width="36%">
                            <col width="14%">
                            <col width="20%">
                            <col width="10%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th><?php _e('名称 (文档链接)');?></th>
                                <th><?php _e('简介');?></th>
                                <th><?php _e('版本');?></th>
                                <th><?php _e('作者 (主页)');?></th>
                                <th><?php _e('操作');?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if( $pluginInfo ): ?>
                            <?php foreach( $pluginInfo[$page] as $plugin): $name = trim(strip_tags($plugin->pluginName)); $plugin->pluginUrl; ?>
                            <?php $source = $plugin->source; $url = $plugin->pluginUrl; $url = in_array($source, array('Download', 'N/A', 'Special')) ? (strpos($url, 'typecho-fans') ? 'https://github.com' . $url : 'https://github.com/typecho-fans/plugins/blob/master/' . $url) : $url; ?>
                            <tr id="plugin-<?php echo $name; ?>" data-name="<?php echo $name;?>" class="plugin">
                                <td><a href="<?php echo $url; ?>" <?php if($url!=='#') echo 'target="_blank"'; ?>><?php echo $name; ?></a>
                                <?php if($source=='Download'): ?>
                                    <a href="http://typecho-fans.github.io" title="<?php _e('Typecho-Fans社区维护版'); ?>" target="_blank"><img style="margin-bottom:-1px;" src="<?php echo $pluginPath;?>/views/tf.png" alt="typecho-fans"/></a></td>
                                <?php elseif($source=='N/A'): ?>
                                    <img style="margin-bottom:-1px;" src="<?php echo $pluginPath;?>/views/na.png" title="<?php _e('已失效或不适用于当前版本'); ?>" alt="n/a"/>
                                    <a href="http://typecho-fans.github.io" title="<?php _e('Typecho-Fans社区维护版'); ?>" target="_blank"><img style="margin-bottom:-1px;" src="<?php echo $pluginPath;?>/views/tf.png" alt="typecho-fans"/></a></td>
                                <?php elseif($source=='不可用'): ?>
                                    <img style="margin-bottom:-1px;" src="<?php echo $pluginPath;?>/views/na.png" title="<?php _e('已失效或不适用于当前版本'); ?>" alt="n/a"/>
                                <?php elseif($source=='Special'): ?>
                                    <img style="margin-bottom:-1px;" src="<?php echo $pluginPath;?>/views/sp.png" title="<?php _e('安装用法特殊请先阅读文档'); ?>" alt="special"/>
                                    <a href="http://typecho-fans.github.io" title="<?php _e('Typecho-Fans社区维护版'); ?>" target="_blank"><img style="margin-bottom:-1px;" src="<?php echo $pluginPath;?>/views/tf.png" alt="typecho-fans"/></a></td>
                                <?php elseif($source=='特殊'): ?>
                                    <img style="margin-bottom:-1px;" src="<?php echo $pluginPath;?>/views/sp.png" title="<?php _e('安装用法特殊请先阅读文档'); ?>" alt="special"/>
                                <?php endif; ?>
                                <td><?php echo trim(strip_tags($plugin->desc)); ?></td>
                                <td><?php $version = trim(strip_tags($plugin->version)); echo $version; ?>
                                <?php $version = strpos($version, 'v')===0 ? substr($version, 1) : $version; $local = in_array($name, $installed); $infos = $local ? $this->getLocalInfos($name) : array(0, 0) ; $author = trim(strip_tags($plugin->author)); $authors = explode(', ', $author); ?>
                                <?php if( $local && ($infos[0]==$author || in_array($infos[0], $authors)) && ($infos[1] < $version)): ?>
                                    &#8672 <span class="error"><?php _e('有新版本！');?></span></td>
                                <?php endif; ?>
                                <?php $sites = explode(', ', trim($plugin->site)); ?>
                                <td>
                                <?php foreach( $authors as $key => $val): ?>
                                <?php if( !empty($sites[$key]) ): ?><a href="<?php echo $sites[$key]; ?>" target="_blank"><?php endif; ?><?php echo $val; ?><?php if( isset($sites[$key]) ): ?></a><?php endif; ?><?php if( $val!==end($authors) ): ?>, <?php endif; ?>
                                <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php if( $local && $infos[0]==$author ): ?>
                                        <span class="install" style="display: none;"><?php _e('安装');?></span>
                                        <span class="uninstall"><?php _e('卸载');?></span>
                                    <?php else: ?>                                        
                                        <span class="install"><?php _e('安装');?></span>
                                        <span class="uninstall" style="display: none;"><?php _e('卸载');?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php elseif( $keywords ): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; font-weight: bold;">
                                    <?php _e('没有找到符合要求的插件。'); ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; font-weight: bold;">
                                    <?php _e('没有找到任何插件，请检查插件源%s设置%s是否正确。', '<a href="'.$options->adminUrl .'options-plugin.php?config=TeStore">', '</a>'); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if( $pluginInfo ): ?>
                <div class="typecho-list-operate clearfix">
                    <div class="operate">
                        <a class="profile-avatar success" href="https://github.com/typecho-fans/plugins/blob/master/TESTORE.md" target="_blank"><img style="margin-bottom:-1.5px;" src="<?php echo $pluginPath;?>/views/gh.svg"/> <?php _e('我要添加插件信息'); ?> <i class="i-exlink"></i></a>
                    </div>
                    <ul class="typecho-pager">
                        <?php $nav->render('&laquo;', '&raquo;'); ?>
                    </ul>
                </div>
                <?php endif; ?>
<?php
}else{
include TYPEHO_ADMIN_PATH . 'common.php';
$menu->title = _t('TE插件仓库');
include TYPEHO_ADMIN_PATH . 'header.php';
include TYPEHO_ADMIN_PATH . 'menu.php';
?>

<style type="text/css">
.uninstall{
    color: #B94A48;
}
.install,.uninstall{
    cursor: pointer;
}
</style>
<div class="main">
    <div class="body container">
        <div class="colgroup">
            <div class="typecho-page-title col-mb-12">
                <h2><?php echo $menu->title; ?></h2>
            </div>
        </div>
        <div class="row typecho-page-main" role="form">
            <div class="col-mb-12 typecho-list">

            </div>
        </div>
    </div>
</div>

<?php
    include TYPEHO_ADMIN_PATH.'copyright.php';
    include TYPEHO_ADMIN_PATH.'common-js.php';
    include TYPEHO_ADMIN_PATH.'footer.php';
?>
<link rel="stylesheet" type="text/css" href="<?php echo $pluginPath; ?>/sticky/sticky.css">
<script type="text/javascript" src="<?php echo $pluginPath; ?>/sticky/sticky.js"></script>
<script type="text/javascript">
$(function(){
    $('.typecho-pager a').removeAttr('target');
    var body = $('.typecho-list'),
        setting = {'img' : '<?php echo $pluginPath;?>/sticky/close.png'};
    $.ajax({
        type:'post',
        url: '<?php echo $marketUrl . '?action=loadinfos&keywords=' . $this->request->get('keywords') . '&page=' . $this->request->get('page'); ?>',
        beforeSend: function() {
            body.html('<div class="typecho-table-wrap"><table class="typecho-list-table"><tbody><tr><td colspan="5" style="text-align: center; font-weight: bold;"><span class="loading"><?php _e('插件列表加载中... 首次读取或耗时较长，使用缓存后可大幅减少等待时间。'); ?></span></td></tr></tbody></table></div>');
        },
        error: function() {
            body.html('<div class="typecho-table-wrap"><table class="typecho-list-table"><tbody><tr><td colspan="5" class="loading" style="text-align: center; font-weight: bold;"><?php _e('插件列表加载失败，请刷新页面重试。'); ?></td></tr></tbody></table></div>');
        },
        success: function(content) {
            body.html(content);
            clickInstall();
            clickUninstall();
        }
    });

    function clickInstall() {
        var inst = $('.plugin .install');

        inst.on('click', function() {
            var $this = $(this),
            name = $this.parents('.plugin').data('name'),
            sucStr = '<span style="color:#467B96;"><?php _e('安装'); ?>' + name + '<?php _e('成功'); ?></span>',
            errorStr = '<span class="warning"><?php _e('安装'); ?>' + name + '<?php _e('失败'); ?></span>';
            if (! confirm('<?php _e('确定安装该插件吗？'); ?>')) {
                return false;
            }

            $.ajax({
                url: '<?php $security->index(__TYPECHO_ADMIN_DIR__ . 'te-store/install'); ?>',
                dataType: 'json',
                data: {
                    plugin:  name
                },
                beforeSend: function() {
                    $.sticky('<?php _e('正在安装...'); ?>', {'img' : '<?php echo $pluginPath;?>/sticky/close.png', 'autoclose' : false});
                    inst.css({'pointer-events' : 'none', 'color' : '#999'});
                }
            }).fail(function() {
                $('.sticky').remove();
                $.sticky(errorStr, setting);
                inst.css({'pointer-events' : 'auto', 'color' : '#444'});
            }).done(function(result) {
                $('.sticky').remove();
                inst.css({'pointer-events' : 'auto', 'color' : '#444'});
                if (result.status) {
                    $this.hide().parent().find('.uninstall').show();
                    $.sticky(sucStr, setting);
                } else {
                    $this.show().parent().find('.uninstall').hide();
                    if ( result.error != '' ) {
                        $.sticky(errorStr + ': ' + result.error, setting);
                    }
                }
            });
        });
    }

    function clickUninstall() {
        $('.plugin .uninstall').on('click', function(){
            var $this = $(this),
                name = $this.parents('.plugin').data('name');
            if (! confirm('<?php _e('如果插件在启用中将自动禁用后卸载，是否继续？'); ?>')) {
                return false;
            }

            $.ajax({
                url: '<?php $security->index(__TYPECHO_ADMIN_DIR__ . 'te-store/uninstall'); ?>',
                dataType: 'json',
                data: {
                    plugin: name
                }
            }).done(function(result) {
                if (result.status) {
                    $this.hide().parent().find('.install').show();
                    $.sticky('<span style="color:#467B96;"><?php _e('卸载'); ?>' + name + '<?php _e('成功'); ?></span>', setting);
                } else {
                    $this.show().parent().find('.install').hide();
                    errorMsg = result.error != '' ? ': ' + result.error : '';
                    $.sticky('<span class="warning"><?php _e('卸载'); ?>' + name + '<?php _e('失败'); ?></span>' + errorMsg, setting);
                }
            });

        });
    }
});
</script>
<?php
}