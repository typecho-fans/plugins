<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>

<?php
    $page = $pageOrPost;

    $db = Typecho_Db::get();
    $table = $db->getPrefix() . 'verion_plugin';
    $rows = $db->fetchAll($db->select()->from($table)->where("cid = ? ", $page->cid)->order('time', Typecho_Db::SORT_DESC));
?>

<div id="tab-verions" class="tab-content hidden p">
    <p><label class="typecho-label" style="color: #f79148;"><?php _e('提示: 鼠标悬停以显示更多操作，点击标签和按钮之间的空白处以预览文章内容'); ?></label></p>
    
    <div class="typecho-table-wrap Version-table-wrap-narrow">
        <table class="typecho-list-table">
            <colgroup>
                <col width="50%"/>
                <col width="50%"/>
            </colgroup>

            <thead>
                <tr>
                    <th><?php _e('时间'); ?></th>
                    <th><?php _e('标签'); ?></th>
                </tr>
            </thead>

            <tbody>
                <?php 
                    $index = 0; 
                    foreach($rows as $row):

                    $_time = date("y-m-d H:i", $row['time']);
                    $_user = $db->fetchRow($db->select()->from('table.users')->where('uid = ?', $row['modifierid']));
                    $_artical = $db->fetchRow($db->select()->from('table.contents')->where('cid = ?', $row['cid']));
                    $auto = $row['auto']=='auto';
                    
                    $hightlight = $auto?'Version-highlight':'';
                ?>
                    <tr class="Version-row" title="<?php _e($_time."   vid: ".$row['vid']) ?>"
                        version-id="<?php _e($row['vid']); ?>" 
                        modifier="<?php _e($_user['screenName']); ?>"
                        time="<?php _e($_time); ?>"
                        is-auto="<?php _e($row['auto']); ?>"
                    >
                        <td>
                            <div class="Version-row-time <?php _e($hightlight) ?>" <?php $auto&&false?_e('style="display: inline-block"'):'' ?> >
                                <?php _e($_time); ?>
                            </div>

                            <div class="Version-actions hidden" >
                                <button type="button" class="btn primary Version-btn Version-btn-revert"  title="回退到这个版本"><?php _e('回退'); ?></button>
                                <button type="button" class="btn primary Version-btn Version-btn-delete"  title="从数据库删除这个版本"><?php _e('删除'); ?></button>
                            </div>
                            
                        </td>
                        <td>
                            <div class="Version-row-label <?php _e($hightlight) ?>">
                                <?php $_content = !$auto?$row['comment']:'自动保存版本'; ?>
                                <?php if(!$auto || true): ?>
                                    <input type="text" <?php if($auto) _e('disabled'); ?> autocomplete="off" rows="1" class="w-100 mono pastable Version-not-listen-input" last="<?php _e($_content); ?>" value="<?php _e($_content); ?>"></input>
                                <?php endif; ?>
                            </div>

                            <div class="Version-modifier hidden">
                                <?php _e($_user['screenName']); ?>
                            </div>

                            

                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            
        </table>
    </div>
    
</div>