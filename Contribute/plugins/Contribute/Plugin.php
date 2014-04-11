<?php
/**
 * 文章投稿插件
 *
 * @package Contribute
 * @author ShingChi
 * @version 1.0.0
 * @link http://lcz.me
 */
class Contribute_Plugin implements Typecho_Plugin_Interface
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
        /* 创建投稿页面及投稿数据表 */
        self::initPage();
        $result = self::initTable();

        /* 添加动作和面板 */
        Helper::addAction('contribute', 'Contribute_Action');
        Helper::addPanel(3, 'Contribute/panel.php', _t('投稿'), _t('管理投稿'), 'administrator');

        return _t($result);
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
        Helper::removeAction('contribute');
        Helper::removePanel(3, 'Contribute/panel.php');

        self::dropTable();
        self::hiddenPage();
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
        /** 删除数据表 */
        $drop = new Typecho_Widget_Helper_Form_Element_Radio('drop',
            array('y' => '删除', 'n' => '保留'), 'y',
            _t('投稿数据表'), _t('禁用插件时是否删除投稿数据表'));
        $form->addInput($drop);

        /* 显示撰稿信息 */
        $author = new Typecho_Widget_Helper_Form_Element_Radio('author',
            array('y' => '显示', 'n' => '隐藏'), 'y',
            _t('作者信息'), _t('文章中是否显示撰稿人信息'));
        $form->addInput($author);

        /* 默认审核设置 */
        $approved = new Typecho_Widget_Helper_Form_Element_Radio('approved',
            array('post' => '发布', 'draft' => '草稿'), 'post',
            _t('审核操作'), _t('默认审核通过操作是发布，还是保存到草稿'));
        $form->addInput($approved);
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
     * 创建稿件数据表
     *
     * @access public
     * @return void
     */
    public static function initTable()
    {
        $db = Typecho_Db::get();
        $script = file_get_contents(__TYPECHO_ROOT_DIR__ .
            __TYPECHO_PLUGIN_DIR__ . '/Contribute/Mysql.sql');
        $script = str_replace('typecho_', $db->getPrefix(), $script);

        try {
            $script = trim($script);
            if ($script) {
                $db->query($script, Typecho_Db::WRITE);
            }
            return _t('稿件数据表已创建, 插件启用成功, 请配置');
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            if (1050 == $code) {
                try {
                    $db->query($db->select()->from('table.contribute'));
                    return _t('检测到数据表已存在, 插件启用成功, 请配置');
                } catch (Typecho_Db_Exception $e) {
                    $code = $e->getCode();
                    throw new Typecho_Plugin_Exception('数据表检测失败, 插件无法启用: ' . $code);
                }
            }
            throw new Typecho_Plugin_Exception('数据表建立失败, 插件无法启用: ' . $code);
        }
    }

    /**
     * 创建投稿页面
     *
     * @access public
     * @return void
     */
    public static function initPage()
    {
        $db = Typecho_Db::get();

        $exist = $db->fetchRow($db->select()->from('table.contents')
            ->where('slug = ? AND type = ?', 'contribute', 'page'));

        if (empty($exist)) {
            $options = Helper::options();

            $db->query($db->insert('table.contents')->rows(array(
                'title'         =>  '投稿',
                'slug'          =>  'contribute',
                'created'       =>  $options->gmtTime,
                'modified'      =>  $options->gmtTime,
                'text'          =>  '<!--markdown-->',
                'order'         =>  5,
                'authorId'      =>  1,
                'template'      =>  'contribute.php',
                'type'          =>  'page',
                'status'        =>  'publish',
                'password'      =>  NULL,
                'commentsNum'   =>  0,
                'allowComment'  =>  '0',
                'allowPing'     =>  '0',
                'allowFeed'     =>  '0',
                'parent'        =>  0
            )));
        } else {
            $db->query($db->update('table.contents')->rows(array(
                'title'         =>  '投稿',
                'slug'          =>  'contribute',
                'order'         =>  5,
                'template'      =>  'contribute.php',
                'type'          =>  'page',
                'status'        =>  'publish',
                'password'      =>  NULL,
                'parent'        =>  0
            ))->where('slug = ? AND type = ?', 'contribute', 'page'));
        }
    }

    /**
     * 删除稿件数据表
     *
     * @access public
     * @return void
     */
    public static function dropTable()
    {
        $config = Helper::options()->plugin('Contribute');

        if ($config->drop == 'y') {
            $db = Typecho_Db::get();
            $script = 'DROP TABLE `' . $db->getPrefix() . 'contribute`';
            $db->query($script, Typecho_Db::WRITE);
        }
    }

    /**
     * 隐藏投稿页面
     *
     * @access public
     * @return void
     */
    public static function hiddenPage()
    {
        $db = Typecho_Db::get();
        $db->query($db->update('table.contents')
            ->rows(array('status' => 'hidden'))
            ->where('type = ? AND slug = ?', 'page', 'contribute'));
    }
}
