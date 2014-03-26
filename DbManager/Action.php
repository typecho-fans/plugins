<?php

class DbManager_Action extends Typecho_Widget implements Widget_Interface_Do
{
    /**
     * 导出备份
     *
     * @access public
     * @return void
     */
    public function doExport()
    {
        // 需要备份的数据表
        $tableSelect = $this->request->get('tableSelect');
        // 备份的数据
        $content = $this->getSql($tableSelect);
        // 备份文件名
        $fileName = $this->request->get('fileName');

        if (0 == $this->request->get('bakplace')) {
            header('Content-Type: text/x-sql');
            header('Content-Disposition: attachment; filename=' . $fileName);
            if (preg_match("/MSIE ([0-9].[0-9]{1,2})/", $_SERVER['HTTP_USER_AGENT'])) {
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Pragma: public');
            } else {
                header('Pragma: no-cache');
                header('Last-Modified: '. gmdate('D, d M Y H:i:s',
                    Typecho_Date::gmtTime() + (Typecho_Date::$timezoneOffset - Typecho_Date::$serverTimezoneOffset)) . ' GMT');
            }
            header('Expires: ' . gmdate('D, d M Y H:i:s',
                Typecho_Date::gmtTime() + (Typecho_Date::$timezoneOffset - Typecho_Date::$serverTimezoneOffset)) . ' GMT');
            echo $content;
        } else {
            // 备份目录及路径
            $config = Typecho_Widget::widget('Widget_Options')->plugin('DbManager');
            $path = __TYPECHO_ROOT_DIR__ . '/' . trim($config->path, '/') . '/';
            $file = $path . $fileName;

            if (!empty($fileName)) {
                if ((is_dir($path) || @mkdir($path, 0777)) && is_writable($path)) {
                    $handle = fopen($file, 'wb');
                    if ($handle && fwrite($handle, $content)) {
                        fclose($handle);
                        $this->widget('Widget_Notice')->set(_t('备份文件 ' . $fileName . ' 已创建'), 'success');
                    } else {
                        $this->widget('Widget_Notice')->set(_t('备份文件创建失败，请检查目录权限'), 'error');
                    }
                } else {
                    $this->widget('Widget_Notice')->set(_t('文件夹创建失败或目录权限限制'), 'error');
                }
            } else {
                $this->widget('Widget_Notice')->set(_t('备份文件名不能为空'), 'error');
            }
            $this->response->goBack();
        }
    }

    /**
     * 导入备份
     *
     * @access public
     * @return void
     */
    public function doImport()
    {
        // 数据库对象
        $db = Typecho_Db::get();

        // 获取备份目录并设置文件
        $config = Typecho_Widget::widget('Widget_Options')->plugin('DbManager');
        $path = __TYPECHO_ROOT_DIR__ . '/' . trim($config->path, '/') . '/';

        $bid = $this->request->get('bid');
        $deleteCount = 0;
        $scripts = '';

        if ($bid) {
            $imports = is_array($bid) ? $bid : array($bid);
            foreach ($imports as $import) {
                $scripts .= file_get_contents($path . $import);
                $deleteCount ++;
            }

            // 导入数据
            $scripts = explode(";\r\n", $scripts);
            foreach ($scripts as $script) {
                $script = trim($script);
                if ($script) {
                    $db->query($script, Typecho_Db::WRITE);
                }
            }
        }

        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('备份已经被导入') : _t('没有备份被导入'),
        $deleteCount > 0 ? 'success' : 'notice');
        $this->response->goBack();
    }

    /**
     * 删除备份
     *
     * @access public
     * @return void
     */
    public function doDelete()
    {
        // 获取备份目录并设置文件
        $config = Typecho_Widget::widget('Widget_Options')->plugin('DbManager');
        $path = __TYPECHO_ROOT_DIR__ . '/' . trim($config->path, '/') . '/';

        $bid = $this->request->get('bid');
        $deleteCount = 0;
        if ($bid) {
            $fileNames = is_array($bid) ? $bid : array($bid);
            foreach ($fileNames as $fileName) {
                @unlink($path . $fileName);
                $deleteCount ++;
            }
        }

        $this->widget('Widget_Notice')->set($deleteCount > 0 ? _t('备份已经被删除') : _t('没有备份被删除'),
        $deleteCount > 0 ? 'success' : 'notice');
        $this->response->goBack();
    }

    /**
     * 数据库优化
     *
     * @access public
     * @return void
     */
    public function doOptimize()
    {
        $db = Typecho_Db::get();
        $select = $this->request->get('tableSelect');
        $sql = 'OPTIMIZE TABLE  ';
        foreach ($select as $value) {
            $sql .= '`' . $value . '`, ';
        }
        $sql = rtrim($sql, ', ') . ';';

        $result = $db->query($sql, Typecho_Db::WRITE);
        $this->widget('Widget_Notice')->set($result ? _t('所选表已优化')
            : _t('数据库优化失败'), $result ? 'success' : 'notice');
        $this->response->goBack();
    }

    /**
     * 构建SQL语句
     *
     * @access public
     * @param  array $tables 数据表数组
     * @return string $sql SQL语句
     */
    public function getSql(array $tables)
    {
        // 数据库对象
        $db = Typecho_Db::get();

        // SQL语句
        $sql = '-- Typecho Backup SQL' . "\r\n"
             . '-- 程序版本: ' . Typecho_Common::VERSION . "\r\n"
             . '--' . "\r\n"
             . '-- 数据管理: DbManager' . "\r\n"
             . '-- 插件作者: ShingChi' . "\r\n"
             . '-- 主页链接: http://lcz.me' . "\r\n"
             . '-- 生成日期: ' . date('Y 年 m 月 d 日', Typecho_Date::gmtTime()) . "\r\n\r\n";

        // 循环构建每张表的SQL语句
        foreach ($tables as $table) {
            // 创建表语句
            $createSql = '';
            // 插入记录语句
            $insertSql = '';

            // 创建表注释
            $createSql .= '-- --------------------------------------------------------' . "\r\n\r\n"
                . '--' . "\r\n"
                . '-- 表的结构 `' . $table . "`\r\n"
                . '--' . "\r\n\r\n";

            /* 表结构 */
            $dropTable = "DROP TABLE IF EXISTS `$table`;\r\n";
            $showTable = $db->fetchRow($db->query('SHOW CREATE TABLE ' . $table));
            $createTable = $showTable['Create Table'] . ";\r\n\r\n";
            $createSql .= $dropTable . $createTable;

            /* 表记录 */
            $rows = $db->fetchAll($db->select()->from($table));
            if ($rows) {
                // 字段组合SQL语句
                $fieldText = '';
                // 值的组合SQL语句
                $recordText = '';
                // 所有记录
                $records = array();

                // 插入注释
                $insertSql .= '--' . "\r\n"
                    . '-- 转存表中的数据 `' . $table . "`\r\n"
                    . '--' . "\r\n\r\n";

                // 组合字段语句
                foreach ($rows[0] as $key => $value) {
                    $fieldText .= '`' . $key . '`, ';
                }
                $fieldText = rtrim($fieldText, ', ');
                $insertSql .= 'INSERT INTO ' . $table . ' (' . $fieldText . ') VALUES' . "\r\n";

                // 组合一条记录的语法
                foreach ($rows as $k => $row) {
                    $records[$k] = '';
                    foreach ($row as $record) {
                        $records[$k] .= isset($record) ? '\'' . mysql_escape_string($record) . '\', ' : 'NULL, ';
                    }
                    $records[$k] = rtrim($records[$k], ', ');
                }

                // 组合所有记录的语法
                foreach ($records as $val) {
                    $recordText .= '(' . $val . '),' . "\r\n";
                }
                $recordText = rtrim($recordText, ",\r\n") . ";\r\n\r\n";
                $insertSql .= $recordText;
            }
            $sql .= $createSql . $insertSql;
        }

        return $sql;
    }

    /**
     * 绑定动作
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->widget('Widget_User')->pass('administrator');
        $this->on($this->request->is('export'))->doExport();
        $this->on($this->request->is('import'))->doImport();
        $this->on($this->request->is('delete'))->doDelete();
        $this->on($this->request->is('optimize'))->doOptimize();
    }
}
