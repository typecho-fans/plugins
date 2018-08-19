<?php
/**
 * 文章评分
 *
 * @package PostRating
 * @author willin kan,wuwovr
 * @version 1.1.0
 * @update: 2016.12.10
 * @link http://wuwovr.com
 */
class PostRating_Plugin implements Typecho_Plugin_Interface
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
        Typecho_Plugin::factory('Widget_Archive') ->header = array('PostRating_Plugin', 'headerScript');
        Typecho_Plugin::factory('Widget_Archive') ->footer = array('PostRating_Plugin', 'footerScript');

        $db = Typecho_Db::get();
        $postrating = $db->getPrefix() . 'postrating';
        Helper::addPanel(3, 'PostRating/manage.php', '文章评分', '管理文章评分', 'administrator');

        // 数据库若无 'postrating' table 则建立
        if (current($db->fetchRow("SHOW TABLES LIKE '$postrating'")) != $postrating) {
            $createTable = $db->query("CREATE TABLE ". $postrating ." (
            cid       int(10)      NOT NULL,
            rating    int(2)       NOT NULL,
            ip        varchar(32)  NOT NULL,
            name      varchar(32)  NOT NULL,
            created   int(10)      NOT NULL,
            UNIQUE KEY created (created)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

           if ($createTable) return('"postrating" table 创建成功, 插件已经被激活!');
           else throw new Typecho_Plugin_Exception('"postrating" table 创建失败, 无法使用本插件!');
        }
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate()
    {
        Helper::removePanel(3, 'PostRating/manage.php');

        // 删除 'postrating' table
        $db = Typecho_Db::get();
        $config = Typecho_Widget::widget('Widget_Options')->plugin('PostRating');
        if ($config->drop_table) $db->query("DROP TABLE IF EXISTS " . $db->getPrefix() . 'postrating');

    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $jq_set = new Typecho_Widget_Helper_Form_Element_Radio(
          'jq_set', array(0 => '自己处理', 1 => '随着本插件载入'), 0,
          'jQuery 来源', '若选择 "随着本插件载入", 会从 Google API 自动载入 jQurey 1.2.6 到 header().');
        $form->addInput($jq_set);

        $options = Typecho_Widget::widget('Widget_Options');
        $plug_url = $options->pluginUrl;
        $db = Typecho_Db::get();

        $registered = $db->fetchRow($db->select()->from('table.options')
                      ->where('name = ?', 'plugin:PostRating')
                      );
        $img = $registered ? $options->plugin('PostRating')->img_set : 'star_19';

        $cats = array('star',     'custom_star',     'black_star',
                      'fat_star', 'custom_fat_star', 'black_fat_star',
                      'heart',    'custom_heart',    'black_heart',
                      'ball',     'custom_ball',     'black_ball');
        $size = array(16, 19, 23);

        $i = 0;
        foreach($cats as $c) {
          foreach($size as $s) {
            $file = $c.'_'.$s;
            $row[$file] = '<img src="'. $plug_url .'/PostRating/images/'. $file
              .'.png" alt="'.$file.'" title="'.$file.'" style="width:'.$s.'px;height:'.$s.'px;background:#fa0"/>';
          }
          $i++;
          $row[$file] .= $i % 3 ? '' : '<br/>';
        }

        $img_set = new Typecho_Widget_Helper_Form_Element_Radio(
          'img_set', $row, 'star_19',
          '选取評分图片', '请选取适合模板的图片. 若改用自己创建的图片会更有个性.');
        $form->addInput($img_set);

        $num = new Typecho_Widget_Helper_Form_Element_Text(
          'num', NULL, 5,
          '图片数量(总分)', '建议 5 或 10 个, 决定之后, 请不要随意更改, 以免计分错乱.');
        $num->input->setAttribute('class', 'mini');
        $form->addInput($num->addRule('isInteger', _t('请填入一个数字')));

        $tmp = $registered ? $options->plugin('PostRating')->color : '#fa0';
        $color_demo = '<img src="'.$plug_url .'/PostRating/images/'.$img
          .'.png" alt="" title="'.$tmp.'" style="background:'.$tmp.';margin:auto 10px"/>';
        $color = new Typecho_Widget_Helper_Form_Element_Text(
          'color', NULL, '#fa0',
          '图片主体颜色', $color_demo);
        $color->input->setAttribute('class', 'mini');
        $color->input->setAttribute('style', 'float:left');
        $form->addInput($color);

        $tmp = $registered ? $options->plugin('PostRating')->un_rating_color : '#aaa';
        $color_demo = '<img src="'.$plug_url .'/PostRating/images/'.$img
          .'.png" alt="" title="'.$tmp.'" style="background:'.$tmp.';margin:auto 10px"/>';
        $un_rating_color = new Typecho_Widget_Helper_Form_Element_Text(
          'un_rating_color', NULL, '#aaa',
          '未评分的颜色', $color_demo);
        $un_rating_color->input->setAttribute('class', 'mini');
        $un_rating_color->input->setAttribute('style', 'float:left');
        $form->addInput($un_rating_color);

        $tmp = $registered ? $options->plugin('PostRating')->hover_color : '#f00';
        $color_demo = '<img src="'.$plug_url .'/PostRating/images/'.$img
          .'.png" alt="" title="'.$tmp.'" style="background:'.$tmp.';margin:auto 10px"/>';
        $hover_color = new Typecho_Widget_Helper_Form_Element_Text(
          'hover_color', NULL, '#f00',
          '鼠标悬停颜色', $color_demo);
        $hover_color->input->setAttribute('class', 'mini');
        $hover_color->input->setAttribute('style', 'float:left');
        $form->addInput($hover_color);

        $tmp = $registered ? $options->plugin('PostRating')->active_color : '#0a0';
        $color_demo = '<img src="'.$plug_url .'/PostRating/images/'.$img
          .'.png" alt="" title="'.$tmp.'" style="background:'.$tmp.';margin:auto 10px"/>';
        $active_color = new Typecho_Widget_Helper_Form_Element_Text(
          'active_color', NULL, '#0a0',
          '评分完成颜色', $color_demo);
        $active_color->input->setAttribute('class', 'mini');
        $active_color->input->setAttribute('style', 'float:left');
        $form->addInput($active_color);

        $before = new Typecho_Widget_Helper_Form_Element_Text(
          'before', NULL, '请为这篇文章评分: ',
          '图片的前置语', '此语句在文章或独立页面, 可以评分时才出现.');
        $form->addInput($before);

        $after = new Typecho_Widget_Helper_Form_Element_Text(
          'after', NULL, '( 已有 {person} 人评分, 平均得分: {avg} 分 )',
          '图片的后置语', '此语句在文章或独立页面, 已有评分才出现, 首页或其它页会在鼠标提示.');
        $form->addInput($after);

        $no_rating = new Typecho_Widget_Helper_Form_Element_Text(
          'no_rating', NULL, '( 这篇文章尚未评分 )',
          '未评分提示语', '此语句在文章或独立页面, 从未被评分过才出现, 首页或其它页会在鼠标提示.
          <br/>以上三个语句可用参数：{person} 评分人数、{avg} 平均分数.');
        $form->addInput($no_rating);

        $finished = new Typecho_Widget_Helper_Form_Element_Text(
          'finished', NULL, '您评了 {rating}  分, 谢谢!',
          '评分完提示语', '此语句在评分完才出现. 可用参数：{rating} 评分.');
        $form->addInput($finished);

        $need_login = new Typecho_Widget_Helper_Form_Element_Radio(
          'need_login', array(0 => '所有访客皆可评分', 1 => '用戶登录后才可评分'), 0,
          '访客评分权', '以 IP 或昵称作判断, 每人在每篇文章只能评分一次. ( 管理者不在此限 )');
        $form->addInput($need_login);

        $drop_table = new Typecho_Widget_Helper_Form_Element_Radio(
          'drop_table', array(0 => '不刪除', 1 => '刪除'), 0,
          '禁用时删除所有评分', '若决定以后不再使用此插件, 或想清空评分数据, 请选择删除后再禁用.
          <div style="font-family:arial; background:#E8EFD1; width:500px; margin:20px 0 0 -60px; padding:8px 12px">
          请在模板适当位置贴上 <b style="color:#CF7000">&lt;?php PostRating_Plugin::output(); ?></b>
          <br/>例: &lt;?php $this->content("阅读剩余部分..."); PostRating_Plugin::output(); ?>
          <br/>　　或 &lt;?php $this->content(); ?>&lt;?php PostRating_Plugin::output(); ?>
          <br/>　　只可用在 index, post, page, archive... 有输出文章的地方.
          </div>');
        $form->addInput($drop_table);

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
     * 加入 header
     *
     * @access public
     * @return void
     */
    public static function headerScript()
    {
        $options = Typecho_Widget::widget('Widget_Options');

        // 载入 jQuery
        if ($options->plugin('PostRating')->jq_set)
            echo "<script type='text/javascript' src='http://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js'></script>\n";

        /** 载入评分的 css **/
        echo "<link rel='stylesheet' type='text/css' href='", $options->pluginUrl, "/PostRating/rating.css' />\n";
    }

    /**
     * 加入 footer
     *
     * @access public
     * @return void
     */
    public static function footerScript()
    {
        /** 载入评分的 js **/
        if (Typecho_Widget::widget('Widget_Archive')->is('single')) {
            $options = Typecho_Widget::widget('Widget_Options');
            $config = $options->plugin('PostRating');
            $finished = explode('{rating}', $config->finished);
            $f1 = isset($finished[0]) ? '&amp;f1='.base64_encode($finished[0]) : '';
            $f2 = isset($finished[1]) ? '&amp;f2='.base64_encode($finished[1]) : '';
            echo "<script type='text/javascript' src='", $options->pluginUrl, "/PostRating/rating.js?hc=", $config->hover_color, "&amp;ac=", $config->active_color, "{$f1}{$f2}'></script>\n";
        }
    }

    /**
     * 输出
     *
     * @access public
     * @return void
     */
    public static function output()
    {
        $db = Typecho_Db::get();
        if (!$db->fetchRow($db->select()->from('table.options')->where('name = ?', 'plugin:PostRating'))) return;

        $prefix = $db->getPrefix();
        $options = Typecho_Widget::widget('Widget_Options');
        $config = $options->plugin('PostRating');
        $user = Typecho_Widget::widget('Widget_User');
        $archive = Typecho_Widget::widget('Widget_Archive');
        $is_single = $archive->is('single');
        $cid = $archive->cid;

        $query = $db->fetchAll($db->select()->from('table.postrating')->where('cid = ?', $cid));
        foreach ($query as $row) {
            $rating[] = $row['rating'];
            $ip[]     = $row['ip'];
            $name[]   = $row['name'];
        }

        $sum    = isset($rating) ? array_sum($rating) : 0;
        $person = isset($rating) ? count($rating) : 0;
        $avg = $person > 0 ? round($sum/$person, 2) : 0;

        $str = array('{person}' => $person, '{avg}' => "<b>".$avg."</b>");
        $before    = strtr(trim($config->before), $str);
        $after     = strtr(trim($config->after), $str);
        $no_rating = strtr(trim($config->no_rating), $str);
        $color = $config->color;
        $num  = $config->num;
        $img   = $config->img_set;
        $img_size = substr($img, -2);

        $w_img = $img_size * $num;
        $w_bg  = $img_size * $avg + 1;
        $pos   = 11 - $img_size / 2;     // 調整 .r_info 水平線
        $r_pos = $pos - 3;               // 調整 .no_rating 水平線
        $l_pos = $img_size / 2 - 9;      // 調整 .need_login, .r_label 水平線

        // 訪客評分權
        $vistor_ip = isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : (isset($_SERVER["HTTP_CLIENT_IP"]) ? $_SERVER["HTTP_CLIENT_IP"] : $_SERVER["REMOTE_ADDR"]);
        $vistor_name = $user->hasLogin() ? $user->screenName : Typecho_Cookie::get('__typecho_remember_author', null);
        $user_can = $user->group == 'administrator' ? 1  // 管理者
        : (isset($ip) && in_array($vistor_ip, $ip)       // IP
        || isset($vistor_name) && isset($name) && in_array($vistor_name, $name) // 名字
        || $archive->hidden                              // 受保護
        ? 0 : 1);

        // 可評分時才出現 $befor
        $label = $is_single && $user_can ? (!$user->hasLogin() && $config->need_login ? "<div class='need_login' style='margin-top:{$l_pos}px;'>用戶登录后才可评分</div>" : "<div class='r_label' style='margin-top:{$l_pos}px;'>{$before}</div>") : '';

        // single 才有 $info
        $no_rating = $no_rating ? "<div class='no_rating' style='bottom:{$r_pos}px;'>{$no_rating}</div>" : '';
        $after = $after ? "<div class='r_info' style='bottom:{$pos}px;'>{$after}</div>" : '';
        $info = $is_single ? ($person == 0 ? $no_rating : $after) : '';

        // IE 版本判斷
        $ua = $_SERVER["HTTP_USER_AGENT"];
        $msie = strpos($ua, 'MSIE');
        $img = $msie && substr($ua, $msie+5, 2) < 7 ? 'star_' . $img_size . '.gif' : $img . '.png'; // IE7 以下用 gif

        // 圖片位址
        $img = $options->pluginUrl . '/PostRating/images/' . $img;
        $img_bg = $person == 0 ? $config->un_rating_color : 'transparent';
        $img_src = '';
        for ($i = 1; $i <= $num; $i++) {
          // 鼠標提示
          $title = strip_tags($is_single ? "{$i} 分" : ($person == 0 ? $no_rating : $after));
          // 圖片鏈接
          $img_src .= "<img src='$img' class='score_{$i}' title='{$title}' alt='' />";
        }
        echo "
<div class='postrating'>{$label}
    <div class='r_score' style='width:{$w_img}px;height:{$img_size}px'><div class='r_bg' style='width:{$w_bg}px;height:{$img_size}px;background:{$color}'></div><div class='r_img' style='width:{$w_img}px;background:{$img_bg};bottom:{$img_size}px'>{$img_src}</div></div>
    {$info}
</div><div class='r_clear'></div>\n";
    }

}
