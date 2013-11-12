<?php

//require_once dirname(__FILE__) . '/markdownify/markdownify.php';
require_once 'Converter.php';

class MarkdownTransfer_Action extends Typecho_Widget implements Widget_Interface_Do
{
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
    }

    /**
     * 转换文章内容
     */
    public function transform()
    {
        $db = Typecho_Db::get();
        //$md = new Markdownify();
        $md = new Converter();

        $i = 1;
        while (true) {
            $result = $db->query($db->select()->from('table.contents')
            ->where('table.contents.type = ? OR table.contents.type = ?', 'post', 'page')
            ->order('table.contents.cid', Typecho_Db::SORT_ASC)->page($i, 100));
            $j = 0;

            while ($row = $db->fetchRow($result)) {
                if (substr($row['text'], 0, 15) != '<!--markdown-->') {
                    $text = '<!--markdown-->' . $md->parseString($row['text']);
                } else {
                    $text = $row['text'];
                }

                $db->query($db->update('table.contents')->rows(array('text' => $text))
                ->where('cid = ?', $row['cid']));

                $j ++;
                unset($row);
            }

            if ($j < 100) {
                break;
            }

            $i ++;
            unset($result);
        }

        $this->widget('Widget_Notice')->set(_t("文章已经转换完成，欢迎进入 Markdown 的世界！"), NULL, 'success');
        $this->response->goBack();
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
        $this->on($this->request->is('transform'))->transform();
    }
}
