<?php
/**
 * 文章评分管理
 */
class_exists('Typecho_Widget') or die('This file can not be loaded directly.');
include 'header.php';
include 'menu.php';

$db = Typecho_Db::get();
$postrating = $db->getPrefix() . 'postrating';
$options = Typecho_Widget::widget('Widget_Options');
$contents = Typecho_Widget::widget('Widget_Abstract_Contents');
$hash = substr(md5(Typecho_Widget::widget('Widget_User')->authCode), -10); // 安全密鑰, 別人無法得知此數值

// 勾選式刪除
if (isset($_POST['hash']) && $_POST['hash'] == $hash) {

    // 單篇選擇
    if (isset($_POST['created'])) {
        $created = $_POST['created'];
        $select = $db->delete('table.postrating');
        foreach($created as $row) {
            $select->orWhere('created = ?', $row);
        }
        $db->query($select);

    // 總覽選擇
    } elseif (isset($_POST['cid'])) {
        $cid = $_POST['cid'];
        $select = $db->delete('table.postrating');
        foreach($cid as $row) {
            $select->orWhere('cid = ?', $row);
        }
        $db->query($select);
    }
}

if (isset($_GET['cid'])) {

    // 取得單篇文章
    $result = $contents->push($db->fetchRow($contents->select()->where('table.contents.cid = ?', $_GET['cid'])));

    // 取得單篇文章的評分
    $query = $db->fetchAll($db->select()->from('table.postrating')->where('cid = ?', $_GET['cid'])->order('created', Typecho_Db::SORT_DESC));
}

?>
<div class="main">
    <div class="body body-950">
        <?php include 'page-title.php'; ?><?php if (isset($query)) echo '<label class="typecho-label">　标题: 《<a href="', $result['permalink'], '">', $result['title'], '</a>》</label>'; ?>
        <div class="container typecho-page-main">

            <div class="column-24 start-01 typecho-list">
                <div class="typecho-list-operate">
                <form method="get" action="">
                    <p class="operate">
                    <?php if (isset($query)) { ?>
                    <a href="<?php $url = explode('&cid=', $_SERVER['REQUEST_URI']); echo htmlspecialchars($url[0]); ?>" class="button">&#9650; 返回上层</a>
                    <?php } ?>操作: 
                        <span class="operate-button typecho-table-select-all">全选</span>, 
                        <span class="operate-button typecho-table-select-none">不选</span>&nbsp;&nbsp;&nbsp;&nbsp;选中项: 
                        <span lang="你确认要删除这些评分吗" class="operate-button operate-delete typecho-table-select-submit">删除评分</span>
                    </p>

                </form>
                </div>


<?php
if (isset($query)) {

/* 單篇文章 */
?>
                <form method="post" name="manage_rating" class="operate-form" action="">
                <table class="typecho-list-table">
                    <colgroup>
                        <col width="30"/>
                        <col width="40"/>
                        <col width="100"/>
                        <col width="200"/>
                        <col width="200"/>
                        <col width="200"/>
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="typecho-radius-topleft"> </th>
                            <th> </th>
                            <th class="center">日期</th>
                            <th class="center">评分</th>
                            <th class="center">IP</th>
                            <th class="center typecho-radius-topright">评分者</th>
                        </tr>
                    </thead>
                    <tbody>
                      <?php if(!empty($query)): ?>
                        <?php foreach ($query as $row) { ?>
                        <tr class="even" id="created-<?php echo $row['created']; ?>">

                            <td><input type="checkbox" value="<?php echo $row['created']; ?>" name="created[]"/></td>

                            <td class="center"><a class="hidden-by-mouse" style="cursor:pointer" onclick="req(this);">删除</a></td>

                            <td class="center"><?php echo gmdate('m-d H:i', $row['created']); ?></td>

                            <td style="padding-left:90px;color:#E47E00"><?php $rating = $row['rating']; $color = $rating == 5 ? 'f44' : ($rating == 4 ? 'f80' : ($rating == 3 ? 'bb0': ($rating == 2 ? '0a0' : '555')));for($i = 0; $i < $row['rating']; $i++) { echo "<span style='color:#$color'>★</span>"; } ?></td>

                            <td class="center"><?php echo $row['ip']; ?></td>

                            <td class="center"><?php echo $row['name']; ?></td>

                        </tr>
                        <?php } ?>

                        <?php else: ?>
                        <tr class="even">
                          <td colspan="6"><h6 class="typecho-list-table-title">没有任何評分</h6></td>
                        </tr>
                        <?php endif; ?>

                    </tbody>
                </table>

                <input type="hidden" name="hash" value="<?php echo $hash; ?>" />
                <input type="hidden" name="do" value="" />
                </form>

<script type='text/javascript'>
//<![CDATA[
function req(t){
var request = new Request({
    method: 'post',
    url: '<?php echo $options->pluginUrl; ?>/PostRating/rating.php'
}).send('created=' + t.getParent('tr').get('id') + '&hash=<?php echo $hash; ?>');

t.getParent('tr').getChildren().setStyle('background', '#fa8');
t.getParent('tr').fade('out');
var destroy = function(){ 
    t.getParent('tr').destroy(); 
}
destroy.delay(500);
}
//]]>
</script>


<?php

} else { 

/* 文章總覽 */

    // 取得有評分的 cid 及 rating
    $query = $db->fetchAll($db->select('cid', 'rating')->from('table.postrating'));
    foreach ($query as $row) {
        $rating_cid[] = $row['cid'];
        $rating[$row['cid']][] = $row['rating'];
    }

    // 取得所有 cid
    $post_cid = Typecho_Common::arrayFlatten($db->fetchAll($db->select('cid')->from('table.contents')), 'cid');

    // 刪除不存在 cid 的評分 (若文章已刪除, 就要刪除此 cid 評分)
    $query = isset($rating_cid) ? array_diff(array_unique($rating_cid), $post_cid) : '';
    if ($query) {
        $select = $db->delete('table.postrating');
        foreach ($query as $row) {
            $select->orWhere('cid = ?', $row);
        }
        $db->query($select);
    }
?>
                <form method="post" name="manage_posts" class="operate-form" action="">
                <table class="typecho-list-table">
                    <colgroup>
                        <col width="25"/>
                        <col width="50"/>
                        <col width="20"/>
                        <col width="220"/>
                        <col width="40"/>
                        <col width="70"/>
                        <col width="90"/>
                        <col width="90"/>
                        <col width="90"/>
                        <col width="90"/>
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="typecho-radius-topleft"> </th>
                            <th class="center">cid</th>
                            <th> </th>
                            <th>标题</th>
                            <th> </th>
                            <th class="center">评分人数</th>
                            <th class="center">累计分数</th>
                            <th class="center">平均得分</th>
                            <th class="center">作者</th>
                            <th class="center typecho-radius-topright">分类</th>
                        </tr>
                    </thead>
                    <tbody>
                      <?php $posts = Typecho_Widget::widget('Widget_Contents_Post_Admin'); ?>
                      <?php if($posts->have()): ?>
                        <?php while($posts->next()): ?>
                        <tr<?php $posts->alt(' class="even"', ''); ?> id="<?php $posts->theId(); ?>">

                            <td><input type="checkbox" value="<?php $posts->cid(); ?>" name="cid[]"/></td>
                            <td class="center"><?php $posts->cid(); ?></td>

                            <td>
                            <?php if ('publish' == $posts->status): ?>
                            <a class="right hidden-by-mouse" href="<?php $posts->permalink(); ?>"><img src="<?php $options->adminUrl('images/view.gif'); ?>" title="<?php _e('浏览 %s', $posts->title); ?>" width="16" height="16" alt="view" /></a>
                            <?php endif; ?>
                            </td>

                            <td<?php if ('draft' != $posts->status && 'waiting' != $posts->status): ?> colspan="2"<?php endif; ?>>
                            <?php if (isset($rating[$posts->cid])): ?>
                            <a href="<?php echo $_SERVER['REQUEST_URI'], '&amp;cid=', $posts->cid; ?>"><?php $posts->title(); ?></a>
                            <?php else: ?>
                            <?php $posts->title(); ?>
                            <?php endif; ?>
                            <?php if ('draft' == $posts->status || 'waiting' == $posts->status): ?>
                            </td>
                            <td>
                            <span class="balloon right"><?php echo 'draft' == $posts->status ? '草稿' : '待审核'; ?></span>
                            <?php endif; ?></td>

                            <td class="center"><?php /* 评分人数 */ echo isset($rating[$posts->cid]) ? count($rating[$posts->cid], 1) : '--'; ?></td>

                            <td class="center"><?php /* 累计分数 */ echo isset($rating[$posts->cid]) ? array_sum($rating[$posts->cid]) : '--'; ?></td>

                            <td class="center"><?php /* 平均得分 */ echo isset($rating[$posts->cid]) ? number_format(array_sum($rating[$posts->cid])/count($rating[$posts->cid], 1), 2) : '--'; ?></td>

                            <td class="center"><?php $posts->author(); ?></td>

                            <td class="center"><?php $categories = $posts->categories; $length = count($categories); ?>
                            <?php foreach ($categories as $key => $val): ?>
                                <?php echo $val['name'] . ($key < $length - 1 ? ', ' : ''); ?>
                            <?php endforeach; ?>
                            </td>

                        </tr>
                        <?php endwhile; ?>

                        <?php else: ?>
                        <tr class="even">
                          <td colspan="10"><h6 class="typecho-list-table-title">没有任何文章</h6></td>
                        </tr>
                        <?php endif; ?>

                    </tbody>
                </table>

                <input type="hidden" name="hash" value="<?php echo $hash; ?>" />
                <input type="hidden" name="do" value="" />
                </form>

            <?php if($posts->have()): ?>
            <div class="typecho-pager">
                <div class="typecho-pager-content">
                    <ul>
                        <?php $posts->pageNav(); ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

<?php } ?>

            </div>
        </div>


    </div>
</div>
<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>