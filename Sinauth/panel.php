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
                
                <div id="tab-cacheman" class="tab-content">
                           
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
                            <th><?php _e('用户'); ?></th>
                            <th><?php _e('openid'); ?></th>
                            <th><?php _e('创建时间'); ?> </th>
                            <th><?php _e('过期时间'); ?> </th>
                            <th><?php _e('操作'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $page = isset($request->page) ? $request->page : 1 ; ?>
                        <?php $users = $db->fetchAll($db->select()->from('table.users_oauth')->join('table.users', 'table.users_oauth.uid=table.users.uid')->page($page, 15)->order('table.users_oauth.bind_time', Typecho_Db::SORT_DESC)); ?>
                        <?php foreach($users as $user): ?>
                        <tr class="even" >                           
                            <td>
                                [<?php echo $user['uid']; ?>]  <?php echo $user['name']; ?> 
                                </td>
                            <td>
                                <?php echo $user['openid']; ?> 
                                </td>
                                <td>
                                 <?php echo date('Y-m-d H:i:s', $user['bind_time']); ?>
                            </td>
                            <td><?php if($user['expires_in']) {echo date('H:i:s', $user['expires_in']);} ?></td>
                            
                            <td>
                                -
                            </td>
                        </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>
                </div>               
                <div class="typecho-pager">
                    <div class="typecho-pager-content">
                        <ul>                            
                            <?php $total = $db->fetchObject($db->select(array('COUNT(uid)' => 'num'))->from('table.users_oauth'))->num; ?>
                            <?php for($i=1;$i<=ceil($total/15);$i++): ?>
                            <li class='current'><a href="<?php $options->adminUrl('extending.php?panel=Sinauth%2Fpanel.php&page='.$i); ?>" style= 'cursor:pointer;' title='第 <?php _e($i); ?> 页'> <?php _e($i); ?> </a></li>
                            <?php endfor; ?>

                        </ul>
                    </div>
                </div>                
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

