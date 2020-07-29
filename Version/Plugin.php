<?php
/**
 * 对文章增加简单的修改记录功能
 *
 * @package Version
 * @author innc11
 * @version 1.3
 * @link https://github.com/typecho-fans/plugins/tree/master/Version
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class Version_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        $result = self::install();

        // 插入JS
        Typecho_Plugin::factory('admin/write-post.php')->bottom = ['Version_Plugin', 'js'];
        Typecho_Plugin::factory('admin/write-page.php')->bottom = ['Version_Plugin', 'js'];

        // 监听事件
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish =  ['Version_Plugin', 'onPostPublish'];
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishSave =     ['Version_Plugin', 'onPostSave'];
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->finishPublish =  ['Version_Plugin', 'onPagePublish'];
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->finishSave =     ['Version_Plugin', 'onPageSave'];
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->delete =  ['Version_Plugin', 'onPostDelete'];
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->delete =  ['Version_Plugin', 'onPageDelete'];

        // 注册路由
        Helper::addRoute("Version_Plugin_Revert",  "/version-plugin/revert",  "Version_Action", 'revert');
        Helper::addRoute("Version_Plugin_Delete",  "/version-plugin/delete",  "Version_Action", 'delete');
        Helper::addRoute("Version_Plugin_Preview", "/version-plugin/preview", "Version_Action", 'preview');
        Helper::addRoute("Version_Plugin_Comment", "/version-plugin/comment", "Version_Action", 'comment');

        return $result;
    }

    public static function deactivate()
    {
        $config = Typecho_Widget::widget('Widget_Options')->plugin('Version');

        if ($config->clean == 'yes')
        {
            $db = Typecho_Db::get();
            $script = self::getSQL('Clean');
            
            foreach ($script as $statement)
                $db->query($statement, Typecho_Db::WRITE);
        }
        
        Helper::removeRoute("Version_Plugin_Revert");
        Helper::removeRoute("Version_Plugin_Delete");
        Helper::removeRoute("Version_Plugin_Preview");
        Helper::removeRoute("Version_Plugin_Comment");
    }

    public static function render()
    {
        
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {

    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $clean = new Typecho_Widget_Helper_Form_Element_Radio(
            'clean', array(
                'yes' => '删除',
                'no' => '不删除',
            ), 'no', '删除数据表:', '是否在禁用插件时，删除所有文章的修改记录？');
            
        $form->addInput($clean);
        
        $clean = new Typecho_Widget_Helper_Form_Element_Radio(
            'noAutoSaveVersion', array(
                'yes' => '不保留',
                'no' => '保留',
            ), 'yes', '不保留自动保存的版本:', '是否在手动保存时(保存草稿、发布文章)，删除插件自动保存的版本？');
            
        $form->addInput($clean);
    }

    public static function js($pageOrPost)
    {
        $options = Typecho_Widget::widget('Widget_Options');
        echo '<script src="' . $options->pluginUrl . '/Version/js/overwrite.js"></script>' . PHP_EOL;
        echo '<script src="' . $options->pluginUrl . '/Version/js/version-plugin.js"></script>' . PHP_EOL;
        echo '<link rel="stylesheet" href="' . $options->pluginUrl . '/Version/css/version-plugin.css"/>' . PHP_EOL;

        $db = Typecho_Db::get();
        $table = $db->getPrefix() . 'verion_plugin';
        $rows = $db->fetchAll($db->select()->from($table)->where("cid = ? ", $pageOrPost->cid)->order('time', Typecho_Db::SORT_DESC));

        ob_start();
        include 'vp-menu.php';
        $content = ob_get_clean();

        ob_start();
        include 'vp-preview.php';
        $content2 = ob_get_clean();

        echo "<script>version_plugin_execute(`".$content."`, `".$content2."`, ".count($rows).");</script>". PHP_EOL;
    }

    public static function onPostDelete($postCid, $that)
    {
        self::onPageDelete($postCid, $that);
    }

    public static function onPageDelete($pageCid, $that)
    {
        $db = Typecho_Db::get();
        $table = $db->getPrefix() . 'verion_plugin';

        $db->query($db->delete($table)->where('cid = ? ', $pageCid));
    }

    public static function onPostPublish($contents, $that)
    {
        self::record($contents, $that);
    }

    public static function onPostSave($contents, $that)
    {
        self::record($contents, $that);
    }

    public static function onPagePublish($contents, $that)
    {
        self::record($contents, $that);
    }

    public static function onPageSave($contents, $that)
    {
        self::record($contents, $that);
    }

    public static function record($contents, $that)
    {
        $user = Typecho_Widget::widget('Widget_User');
        $user->hasLogin(); // 调用一下hasLoging()可以让$user进行初始化

        $db = Typecho_Db::get();
        $table = $db->getPrefix() . 'verion_plugin';
        $time = Helper::options()->gmtTime + (Helper::options()->timezone - Helper::options()->serverTimezone);
        $uid = $user->uid;

        if($that->request->t == 'auto') // 如果是自动保存
        {
            $row = $db->fetchRow($db->select()->from($table)->where("auto = 'auto' AND cid = ? ", $that->cid));

            // 如果没有之前保存过的自动保存版本，就新创建一个
            if(empty($row))
            {
                $row = [
                    "cid" => $that->cid,
                    'text' => $contents['text'],
                    'auto' => 'auto',
                    'time' => $time,
                    'modifierid' => $uid
                ];
    
                $db->query($db->insert($table)->rows($row));
            }else{ // 有就直接覆盖
                $row['time'] = $time;
                $row['modifierid'] = $uid;
                $row['text'] = $contents['text'];
    
                $db->query($db->update($table)->rows($row)->where("vid = ? ", $row['vid']));
            }
            
        }else{
            //                                                               自动保存的内容不包括在内
            $raw = $db->fetchRow($db->select()->from($table)->where("cid = ? AND auto IS NULL", $that->cid)->order('time', Typecho_Db::SORT_DESC));

            // 如果内容没有变更就更新一下时间什么的，就不需要新建一个记录了
            if(!empty($raw) && $contents['text']==$raw['text'])
            {
                $raw['time'] = $time;
                $raw['modifierid'] = $uid;

                $db->query($db->update($table)->rows($raw)->where("vid = ? ", $raw['vid']));
            }else{
                $row = [
                    "cid" => $that->cid,
                    'text' => $contents['text'],
                    'auto' => NULL,
                    'time' => $time,
                    'modifierid' => $uid
                ];

                $db->query($db->insert($table)->rows($row));
            }

            $config = Typecho_Widget::widget('Widget_Options')->plugin('Version');

            if ($config->noAutoSaveVersion == 'yes')
            {
                // 删掉自动保存的内容
                $db->query($db->delete($table)->where("auto = 'auto' AND cid = ? ", $that->cid));
            }
        }
        
    }

    public static function getSQL($file)
    {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();

        $config = Typecho_Widget::widget('Widget_Options');
        $script = file_get_contents($config->pluginUrl . '/Version/sql/' . $file . '.sql');
        $script = str_replace('%prefix%', $prefix, $script);
        $script = str_replace('%charset%', 'utf8', $script);
        $script = explode(';', $script);

        $statements = [];

        foreach ($script as $statement)
        {
            $statement = trim($statement);

            if ($statement)
                array_push($statements, $statement);
        }

        return $statements;
    }

    public static function install()
    {
        $db = Typecho_Db::get();
        $dbType = array_pop(explode('_', $db->getAdapterName()));
        $prefix = $db->getPrefix();

        try {
            $script = self::getSQL($dbType);

            foreach ($script as $statement)
                $db->query($statement, Typecho_Db::WRITE);

            return '插件启用成功';
        } catch (Typecho_Db_Exception $e) {
            $code = $e->getCode();
            
            if(($dbType == 'Mysql' && $code == 1050) || ($dbType == 'SQLite' && ($code =='HY000' || $code == 1)))
            {
                try {
                    $script = self::getSQL("Check");

                    foreach ($script as $statement)
                        $db->query($statement, Typecho_Db::READ);

                    return '插件启用成功';
                } catch (Typecho_Db_Exception $e) {
                    $code = $e->getCode();

                    throw new Typecho_Plugin_Exception('无法建立数据表 ErrorCode：'.$code);
                }
            } else {
                throw new Typecho_Plugin_Exception('无法建立数据表 ErrorCode：'.$code);
            }
        }

    }

    
}