<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * <a href="https://dt27.org/Slanted-for-Typecho/" target="_blank">Slanted 主题</a>扩展插件
 *
 * 浏览量统计部分来自 	willin kan 的 Views 插件
 *
 * @package SlantedExtend
 * @author DT27
 * @version 1.0.0
 * @link https://dt27.org/SlantedExtend/
 */
class SlantedExtend_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        //独立页面顶部标题
        Typecho_Plugin::factory('admin/write-page.php')->bottom = array('SlantedExtend_Plugin', 'headingJS');
        //文章缩略图
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('SlantedExtend_Plugin', 'thumbJS');
        //原创文章
        Typecho_Plugin::factory('admin/write-post.php')->option = array('SlantedExtend_Plugin', 'originalHtml');
        //原创文章
        Typecho_Plugin::factory('admin/write-post.php')->bottom = array('SlantedExtend_Plugin', 'originalJS');
        //浏览量统计
        Typecho_Plugin::factory('Widget_Archive')->beforeRender = array('SlantedExtend_Plugin', 'viewsCounter');
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        // contents 表中若无 views 字段则添加
        if (!array_key_exists('views', $db->fetchRow($db->select()->from('table.contents'))))
            $db->query('ALTER TABLE `'. $prefix .'contents` ADD `views` INT(10) DEFAULT 0;');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */
    public static function slantedExtendFields()
    {

    }
    /**
     * 文章缩略图自定义字段扩展
     *
     * @access public
     * @return void
     */
    public static function thumbJS()
    {
?>
        <script>
        $(document).ready(function () {
            function attachDeleteEvent(el) {
                $('button.btn-xs', el).click(function () {
                    if (confirm('<?php _e('确认要删除此字段吗?'); ?>')) {
                        $(this).parents('tr').fadeOut(function () {
                            $(this).remove();
                        });
                    }
                });
            }

            var btn = $('i', $('#custom-field-expand'));
            if (btn.hasClass('i-caret-right')) {
                btn.removeClass('i-caret-right').addClass('i-caret-down');
                $('#custom-field-expand').parent().removeClass('fold');
            }

            if (!$('[name="fieldNames[]"]').is("[value='thumbUrl']")) {
                if($('[name="fieldNames[]"]').last().val()!=""){
                    var html = '<tr><td><input type="text" name="fieldNames[]" placeholder="<?php _e('字段名称'); ?>" class="text-s w-100"></td>'
                            + '<td><select name="fieldTypes[]" id="">'
                            + '<option value="str"><?php _e('字符'); ?></option>'
                            + '<option value="int"><?php _e('整数'); ?></option>'
                            + '<option value="float"><?php _e('小数'); ?></option>'
                            + '</select></td>'
                            + '<td><textarea name="fieldValues[]" placeholder="<?php _e('字段值'); ?>" class="text-s w-100" rows="2"></textarea></td>'
                            + '<td><button type="button" class="btn btn-xs"><?php _e('删除'); ?></button></td></tr>',
                        el = $(html).hide().appendTo('#custom-field table tbody').fadeIn();

                    attachDeleteEvent(el);
                }
                $('[name="fieldTypes[]"]').last().find("option[value='str']").attr("selected",true).siblings().remove();
                $('[name="fieldNames[]"]').last().attr('value', 'thumbUrl').attr('readonly','readonly');
                $('[name="fieldValues[]"]').last().attr('placeholder','文章缩略图，推荐尺寸：300px*300px');
                var html = '<tr><td><input type="text" name="fieldNames[]" placeholder="<?php _e('字段名称'); ?>" class="text-s w-100"></td>'
                        + '<td><select name="fieldTypes[]" id="">'
                        + '<option value="str"><?php _e('字符'); ?></option>'
                        + '<option value="int"><?php _e('整数'); ?></option>'
                        + '<option value="float"><?php _e('小数'); ?></option>'
                        + '</select></td>'
                        + '<td><textarea name="fieldValues[]" placeholder="<?php _e('字段值'); ?>" class="text-s w-100" rows="2"></textarea></td>'
                        + '<td><button type="button" class="btn btn-xs"><?php _e('删除'); ?></button></td></tr>',
                    el = $(html).hide().appendTo('#custom-field table tbody').fadeIn();

                attachDeleteEvent(el);
            }else{
                $("[value='thumbUrl']").parent().parent().children('td:eq(2)').children('textarea').attr('placeholder','文章缩略图，推荐尺寸：180px*180px');
            }
        });
        </script>
<?php
    }
    /**
     *
     * 独立页面头部主副文字及短标题自定义字段扩展
     *
     * @access public
     * @return void
     */
    public static function headingJS()
    {
?>
        <script>
            $(document).ready(function () {
                function attachDeleteEvent(el) {
                    $('button.btn-xs', el).click(function () {
                        if (confirm('<?php _e('确认要删除此字段吗?'); ?>')) {
                            $(this).parents('tr').fadeOut(function () {
                                $(this).remove();
                            });
                        }
                    });
                }

                //默认展开自定义字段设置
                var btn = $('i', $('#custom-field-expand'));
                if (btn.hasClass('i-caret-right')) {
                    btn.removeClass('i-caret-right').addClass('i-caret-down');
                    $('#custom-field-expand').parent().removeClass('fold');
                }


                //不存在heading字段
                if (!$('[name="fieldNames[]"]').is("[value='heading']")) {
                    if($('[name="fieldNames[]"]').last().val()!=""){
                        var html = '<tr><td><input type="text" name="fieldNames[]" placeholder="<?php _e('字段名称'); ?>" class="text-s w-100"></td>'
                                + '<td><select name="fieldTypes[]" id="">'
                                + '<option value="str"><?php _e('字符'); ?></option>'
                                + '<option value="int"><?php _e('整数'); ?></option>'
                                + '<option value="float"><?php _e('小数'); ?></option>'
                                + '</select></td>'
                                + '<td><textarea name="fieldValues[]" placeholder="<?php _e('字段值'); ?>" class="text-s w-100" rows="2"></textarea></td>'
                                + '<td><button type="button" class="btn btn-xs"><?php _e('删除'); ?></button></td></tr>',
                            el = $(html).hide().appendTo('#custom-field table tbody').fadeIn();

                        attachDeleteEvent(el);
                    }
                    $('[name="fieldTypes[]"]').last().find("option[value='str']").attr("selected",true).siblings().remove();
                    $('[name="fieldNames[]"]').last().attr('value', 'heading').attr('readonly','readonly');
                    $('[name="fieldValues[]"]').last().attr('placeholder','Slanted主题头部主文字');
                    var html = '<tr><td><input type="text" name="fieldNames[]" placeholder="<?php _e('字段名称'); ?>" class="text-s w-100"></td>'
                            + '<td><select name="fieldTypes[]" id="">'
                            + '<option value="str"><?php _e('字符'); ?></option>'
                            + '<option value="int"><?php _e('整数'); ?></option>'
                            + '<option value="float"><?php _e('小数'); ?></option>'
                            + '</select></td>'
                            + '<td><textarea name="fieldValues[]" placeholder="<?php _e('字段值'); ?>" class="text-s w-100" rows="2"></textarea></td>'
                            + '<td><button type="button" class="btn btn-xs"><?php _e('删除'); ?></button></td></tr>',
                        el = $(html).hide().appendTo('#custom-field table tbody').fadeIn();

                    attachDeleteEvent(el);
                }else{
                    $("[value='heading']").parent().parent().children('td:eq(2)').children('textarea').attr('placeholder','Slanted主题头部主文字');
                }

                //不存在subheading字段
                if (!$('[name="fieldNames[]"]').is("[value='subheading']")) {
                    if($('[name="fieldNames[]"]').last().val()!=""){
                        var html = '<tr><td><input type="text" name="fieldNames[]" placeholder="<?php _e('字段名称'); ?>" class="text-s w-100"></td>'
                                + '<td><select name="fieldTypes[]" id="">'
                                + '<option value="str"><?php _e('字符'); ?></option>'
                                + '<option value="int"><?php _e('整数'); ?></option>'
                                + '<option value="float"><?php _e('小数'); ?></option>'
                                + '</select></td>'
                                + '<td><textarea name="fieldValues[]" placeholder="<?php _e('字段值'); ?>" class="text-s w-100" rows="2"></textarea></td>'
                                + '<td><button type="button" class="btn btn-xs"><?php _e('删除'); ?></button></td></tr>',
                            el = $(html).hide().appendTo('#custom-field table tbody').fadeIn();

                        attachDeleteEvent(el);
                    }
                    $('[name="fieldTypes[]"]').last().find("option[value='str']").attr("selected",true).siblings().remove();
                    $('[name="fieldNames[]"]').last().attr('value', 'subheading').attr('readonly','readonly');
                    $('[name="fieldValues[]"]').last().attr('placeholder','Slanted主题头部副文字');
                    var html = '<tr><td><input type="text" name="fieldNames[]" placeholder="<?php _e('字段名称'); ?>" class="text-s w-100"></td>'
                            + '<td><select name="fieldTypes[]" id="">'
                            + '<option value="str"><?php _e('字符'); ?></option>'
                            + '<option value="int"><?php _e('整数'); ?></option>'
                            + '<option value="float"><?php _e('小数'); ?></option>'
                            + '</select></td>'
                            + '<td><textarea name="fieldValues[]" placeholder="<?php _e('字段值'); ?>" class="text-s w-100" rows="2"></textarea></td>'
                            + '<td><button type="button" class="btn btn-xs"><?php _e('删除'); ?></button></td></tr>',
                        el = $(html).hide().appendTo('#custom-field table tbody').fadeIn();

                    attachDeleteEvent(el);
                }else{
                    $("[value='subheading']").parent().parent().children('td:eq(2)').children('textarea').attr('placeholder','Slanted主题头部副文字');
                }


                //不存在shotTitle字段
                if (!$('[name="fieldNames[]"]').is("[value='shotTitle']")) {
                    if($('[name="fieldNames[]"]').last().val()!=""){
                        var html = '<tr><td><input type="text" name="fieldNames[]" placeholder="<?php _e('字段名称'); ?>" class="text-s w-100"></td>'
                                + '<td><select name="fieldTypes[]" id="">'
                                + '<option value="str"><?php _e('字符'); ?></option>'
                                + '<option value="int"><?php _e('整数'); ?></option>'
                                + '<option value="float"><?php _e('小数'); ?></option>'
                                + '</select></td>'
                                + '<td><textarea name="fieldValues[]" placeholder="<?php _e('字段值'); ?>" class="text-s w-100" rows="2"></textarea></td>'
                                + '<td><button type="button" class="btn btn-xs"><?php _e('删除'); ?></button></td></tr>',
                            el = $(html).hide().appendTo('#custom-field table tbody').fadeIn();

                        attachDeleteEvent(el);
                    }
                    $('[name="fieldTypes[]"]').last().find("option[value='str']").attr("selected",true).siblings().remove();
                    $('[name="fieldNames[]"]').last().attr('value', 'shotTitle').attr('readonly','readonly');
                    $('[name="fieldValues[]"]').last().attr('placeholder','短标题，作为导航栏名称');
                    var html = '<tr><td><input type="text" name="fieldNames[]" placeholder="<?php _e('字段名称'); ?>" class="text-s w-100"></td>'
                            + '<td><select name="fieldTypes[]" id="">'
                            + '<option value="str"><?php _e('字符'); ?></option>'
                            + '<option value="int"><?php _e('整数'); ?></option>'
                            + '<option value="float"><?php _e('小数'); ?></option>'
                            + '</select></td>'
                            + '<td><textarea name="fieldValues[]" placeholder="<?php _e('字段值'); ?>" class="text-s w-100" rows="2"></textarea></td>'
                            + '<td><button type="button" class="btn btn-xs"><?php _e('删除'); ?></button></td></tr>',
                        el = $(html).hide().appendTo('#custom-field table tbody').fadeIn();

                    attachDeleteEvent(el);
                }else{
                    $("[value='shotTitle']").parent().parent().children('td:eq(2)').children('textarea').attr('placeholder','短标题，作为显示在导航栏的名称');
                }

            });
        </script>
<?php
    }

    /**
     *
     * 原创文章选项
     *
     * @access public
     * @return void
     */
    public static function originalHtml(){
        echo '<section class="typecho-post-option"><label class="typecho-label" for="token-input-tags">原创文章</label><p><span><input type="radio" id="isOriginal-1" value="1" name="isOriginal"><label for="isOriginal-1">是</label></span> <span><input type="radio" checked="true" id="isOriginal-0" value="0" name="isOriginal"><label for="isOriginal-0">否</label></span></p><p class="description">原创文章将在文章底部显示转载提示信息</p></section>';
    }

    /**
     *
     * 原创文章选项
     *
     * @access public
     * @return void
     */
    public static function originalJS(){
?>
<script>
$(document).ready(function () {
    var isOriginalInput = $('input[value=isOriginal]');
    if($(isOriginalInput).length>0){
        var isOriginalValue = $(isOriginalInput).parent().parent().find('textarea');
        $(isOriginalInput).parent().parent().hide();
        if($(isOriginalValue).val()=='0'){
            $('#isOriginal-0').attr('checked',true);
        }else{
            $('#isOriginal-1').attr('checked',true);
        }

        $('#isOriginal-1').click(function () {
            $(isOriginalValue).val('1');
        });
        $('#isOriginal-0').click(function () {
            $(isOriginalValue).val('0');
        });
    }else {
        var oHtml = '<div id="isOriginal"><input type="hidden" name="fieldNames[]" value="isOriginal"><input type="hidden" name="fieldTypes[]" value="int"><input id="isOriginalValue" type="hidden" name="fieldValues[]" value="0"></div>';
        $(oHtml).appendTo('#custom-field table tbody');

        $('#isOriginal-1').click(function () {
            $('#isOriginalValue').val('1');
        });
        $('#isOriginal-0').click(function () {
            $('#isOriginalValue').val('0');
        });
    }
});
</script>
<?php
    }
    /**
     * 加入 beforeRender
     *
     * @access public
     * @return void
     */
    public static function viewsCounter()
    {
        // 访问计数
        if (Typecho_Widget::widget('Widget_Archive')->is('single')) {
            $db = Typecho_Db::get();
            $cid = Typecho_Widget::widget('Widget_Archive')->cid;
            $row = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid));
            $db->query($db->update('table.contents')->rows(array('views' => (int)$row['views']+1))->where('cid = ?', $cid));
        }
    }

    /**
     * 输出访问次数
     *
     * 语法: Views_Plugin::theViews();
     * 输出: '访问: xx,xxx 次'
     *
     * 语法: Views_Plugin::theViews('有 ', ' 次点击');
     * 输出: '有 xx,xxx 次点击'
     *
     * @access public
     * @param string  $before 前字串
     * @param string  $after  后字串
     * @param bool    $echo   是否显示 (0 用于运算，不显示)
     * @return string
     */
    public static function theViews($before = '浏览量: ', $after = ' ', $echo = 1)
    {
        $db = Typecho_Db::get();
        $cid = Typecho_Widget::widget('Widget_Archive')->cid;
        $row = $db->fetchRow($db->select('views')->from('table.contents')->where('cid = ?', $cid));
        if ($echo)
            echo $before, number_format($row['views']), $after;
        else
            return $row['views'];
    }

    /**
     * 输出最受欢迎文章
     *
     * 语法: Views_Plugin::theMostViewed();
     *
     * @access public
     * @param int     $limit  文章数目
     * @param int     $show 是否显示浏览量 1 是, 0 否
     * @param string  $before 前字串
     * @param string  $after  后字串
     * @return string
     */
    public static function theMostViewed($limit = 10, $show = 1, $before = ' ( 浏览量: ', $after = ' ) ')
    {
        $db = Typecho_Db::get();
        $options = Typecho_Widget::widget('Widget_Options');
        $limit = is_numeric($limit) ? $limit : 10;
        $posts = $db->fetchAll($db->select()->from('table.contents')
            ->where('type = ? AND status = ? AND password IS NULL', 'post', 'publish')
            ->order('views', Typecho_Db::SORT_DESC)
            ->limit($limit)
        );

        if ($posts) {
            foreach ($posts as $post) {
                $result = Typecho_Widget::widget('Widget_Abstract_Contents')->push($post);
                $post_views = number_format($result['views']);
                $post_title = htmlspecialchars($result['title']);
                $permalink = $result['permalink'];
                echo "<li><a href='$permalink' title='$post_title'>$post_title</a>";
                if($show)
                    echo "<span style='font-size:70%'>$before $post_views $after</span></li>\n";
                else
                    echo "</li>\n";
            }

        } else {
            echo "<li>N/A</li>\n";
        }
    }
}
