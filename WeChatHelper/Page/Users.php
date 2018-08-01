<?php
include_once 'common.php';
include 'header.php';
include 'menu.php';
$siteUrl = Helper::options()->siteUrl;
?>
<div class="main">
    <div class="body container">
        <div class="typecho-page-title">
            <h2><?php _e($menu->title);?></h2>
        </div>
        <div class="row typecho-page-main">
            <div class="col-mb-12 typecho-list">
                <div class="typecho-list-operate clearfix">
                    <div class="operate">
                        <form action="<?php $security->index('/action/WeChat?users&do=syncList') ?>" method="post">
                            <button class="btn dropdown-toggle btn-s" type="submit">同步微信关注者数据</button>
                        </form>
                    </div>
                    <div class="search" role="search" style="display:none">
                        <input type="text" class="text-s" placeholder="请输入关键字" value="" name="keywords">
                        <select name="category">
                            <option value="">所有分类</option>
                            <option value="1">信手涂鸦</option>
                            <option value="2">分享网事</option>
                            <option value="3">折腾代码</option>
                            <option value="4">乱七八糟</option>
                        </select>
                        <button type="submit" class="btn btn-s">筛选</button>
                    </div>
                </div>

                <div class="typecho-table-wrap">
                    <table class="typecho-list-table">
                        <colgroup>
                            <col width="5%">
                            <col width="26%">
                            <col width="12%">
                            <col width="5%">
                            <col width="14%">
                            <col width="16%">
                            <col width="6%">
                            <col width="6%">
                            <col width="10%">
                        </colgroup>
                        <thead>
                            <tr>
                                <th> </th>
                                <th><?php _e('微信 OpenID'); ?></th>
                                <th><?php _e('用户'); ?></th>
                                <th><?php _e('性别'); ?></th>
                                <th><?php _e('地区'); ?></th>
                                <th><?php _e('订阅时间'); ?></th>
                                <th><?php _e('积分'); ?></th>
                                <th><?php _e('绑定'); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php Typecho_Widget::widget('WeChatHelper_Widget_Users')->to($users);?>
                            <?php if($users->have()): ?>
                                <?php while ($users->next()): ?>
                                    <tr id="users-uid-<?php $users->uid(); ?>">
                                        <td><img src="<?php _e($users->headimgurl46) ?>" alt="微信 OpenID：<?php _e($users->openid) ?>" height="32px" width="32px"/></td>
                                        <td><?php _e($users->openid) ?></td>
                                        <td><?php _e($users->nickname) ?></td>
                                        <td><?php _e($users->sexVal) ?></td>
                                        <td><?php _e($users->address) ?></td>
                                        <td><?php _e($users->subscribeFormat) ?></td>
                                        <td><?php _e($users->credits) ?></td>
                                        <td><?php _e($users->bindVal) ?></td>
                                        <td>
                                        
                                            <a href="<?php $security->index('/action/WeChat?users&do=syncInfo&page='.$users->getCurrentPage().'&uid='.$users->uid) ?>">更新</a>｜<a href="#">详情</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align:center"><?php _e('没有任何用户'); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="typecho-list-operate clearfix">
                    <?php if($users->have()): ?>
                    <ul class="typecho-pager">
                        <?php $users->pageNav(); ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>
