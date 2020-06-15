<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>
<div class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="container typecho-page-main" role="form">
            <div id="MostCacheMain" class="col-mb-12 typecho-list">
                <ul class="typecho-option-tabs clearfix">
                    <li style="width:50%;" class="active"><a href="#tab-cacheman"><?php _e('缓存列表'); ?></a></li>
                    <li style="width:50%;"><a href="#tab-setting" id="tab-files-btn"><?php _e('缓存规则'); ?></a></li>
                </ul>
                <div id="tab-cacheman" class="tab-content">
                <div class="typecho-option-tabs">
                    <ul class="typecho-option-tabs clearfix">
                        <li class="current">
                            <button id="qcaches" class="btn primary" type="button">清空缓存</button>
                        </li>   
                        <li class="right">
                            <button class="btn" onclick="location.reload();" type="button">刷新页面</button>
                        </li>                                          
                        <lable class="description">清空缓存会自动判断缓存类型，基于Memcache的缓存不提供列表管理。但清空缓存有效。</lable>
                    </ul>
               </div>                 
                <div class="typecho-table-wrap">
                <table class="typecho-list-table">
                    <colgroup>                       
                        <col width="40%"/>
                        <col width="20%"/>
                        <col width="20%"/>
                        <col width="10%"/>
                        <col width="10%"/>
                    </colgroup>
                    <thead>
                        <tr>                            
                            <th><?php _e('KEY'); ?></th>
                            <th><?php _e('创建时间'); ?></th>
                            <th><?php _e('过期时间'); ?> </th>
                            <th><?php _e('URL'); ?> </th>
                            <th><?php _e('操作'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $page = isset($request->page) ? $request->page : 1 ; ?>
                        <?php $caches = $db->fetchAll($db->select()->from('table.most_cache')->page($page, 15)->order('table.most_cache.dateline', Typecho_Db::SORT_DESC)); ?>
                        <?php foreach($caches as $cache): ?>
                        <tr class="even" >                           
                            <td>
                                <?php echo $cache['hash']; ?> 
                                </td>
                                <td>
                                 <?php echo date('Y-m-d H:i:s', $cache['dateline']); ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i:s', $cache['dateline']+$cache['expire']); ?></td>
                            <td><a href="<?php $options->index($cache['hash']); ?>">查看</a></td>
                            <td>
                                <a lang="<?php _e('你确认要删除该缓存吗?'); ?>" href="<?php $options->index('/action/mostcache?del=' . $cache['hash']); ?>" class="operate-delete"><?php _e('删除'); ?></a>
                            </td>
                        </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>
                </div>               
                <div class="typecho-pager">
                    <div class="typecho-pager-content">
                        <ul>                            
                            <?php $total = $db->fetchObject($db->select(array('COUNT(hash)' => 'num'))->from('table.most_cache'))->num; ?>
                            <?php for($i=1;$i<=ceil($total/15);$i++): ?>
                            <li class='current'><a href="<?php $options->adminUrl('extending.php?panel=MostCache%2Fpanel.php&page='.$i); ?>" style= 'cursor:pointer;' title='第 <?php _e($i); ?> 页'> <?php _e($i); ?> </a></li>
                            <?php endfor; ?>

                        </ul>
                    </div>
                </div>                
                </div>
                <div id="tab-setting" class="tab-content hidden">
          
                     <?php
			$select = $db->query("SELECT * FROM ".$db->getPrefix()."options WHERE name ='plugin:MostCache'");
			$oldConfig = $db->fetchAll($select);				
			$newConfig = unserialize($oldConfig[0]['value']);
                        if($newConfig['cacheType']){
                     ?>
                        <form action="<?php $options->index('/action/mostcache?edit'); ?>" method="post" enctype="application/x-www-form-urlencoded">
                        <ul class="typecho-option">
                            <li>
                                <label class="typecho-label"><?php _e('缓存规则'); ?></label>
                                <?php 
                                    foreach ($newConfig['cacheType'] as $key => $value) {
                                        echo '<input type="text" class="w-100" name="preg[]" type="text" value="'.$value.'" /><br>';               
                                    }
                              ?>                                
                                <p class="description"><?php _e('依次为所选中的缓存项目的默认缓存匹配规则，如有特殊需要可进行自定义修改'); ?></p>
                            </li>
                        </ul>                            
                        <ul class="typecho-option typecho-option-submit" id="typecho-option-item-submit-3">
                            <li>
                                <button type="submit" class="btn primary"><?php _e('修改'); ?></button>
                            </li>
                        </ul>                            
                         </form>                         
                         
                        <?php }?>                        
                   
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
include 'table-js.php';
?>
<script type="text/javascript">
$(document).ready(function () {
    $('#qcaches').click(function(){       
        $.ajax({
            url:'<?php $options->index('/action/mostcache?resetCache'); ?>',
            dataType:'json',
            success:function(data){
                if('success' === data){                   
                    location.reload();
                }
            }
        });
    });
    
    $('#MostCacheMain .typecho-option-tabs li').click(function() {
        var tabBox = $('#MostCacheMain > div');

        $(this).siblings('li')
        .removeClass('active').end()
        .addClass('active');

        tabBox.siblings('div')
        .addClass('hidden').end()
        .eq($(this).index()).removeClass('hidden');
        console.log($(this).index());

        return false;
    });
});
</script>
