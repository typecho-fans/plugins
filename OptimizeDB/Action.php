<?php
/**
 * OptimizeDB Plugin
 *
 * @copyright  Copyright (c) 2014 Binjoo (http://binjoo.net)
 * @license    GNU General Public License 2.0
 *
 */

class OptimizeDB_Action extends Typecho_Widget implements Widget_Interface_Do
{
    public function optimize()
    {
        $db = Typecho_Db::get();
        $config = $db->getConfig();
        $tables = $db->fetchAll("SHOW TABLE STATUS FROM " . $config[0]->database);
        foreach ($tables as $row) {
            $result = $db->fetchAll('OPTIMIZE TABLE ' . $row['Name']);
        }
        $this->widget('Widget_Notice')->set(_t('数据库优化完成！'), 'success');
        $this->response->goBack();
    }

    public function action()
    {
        $this->widget('Widget_User')->pass('administrator');
        $this->on($this->request->is('optimize'))->optimize();
    }
}
