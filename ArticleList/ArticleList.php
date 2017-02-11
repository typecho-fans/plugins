<?php
/**
 * 博客日志列表插件，包含随机列表、热门列表
 *
 * @package ArticleList
 * @author DEFE
 * @version 1.1.1
 * @link http://defe.me
 */

class ArticleList implements Typecho_Plugin_Interface
{
    /**
     * 
     */
    public static function  activate() {}
    public static function deactivate(){}
    public static function  config(Typecho_Widget_Helper_Form $form) {
        $numset = new Typecho_Widget_Helper_Form_Element_Radio('numset',
        array('a'=>'与Blog设置中的"文章列表数目"相同','b'=>'单独设定文章列表数目'),
        'a','文章数目选项');
        $form->addInput($numset->multiMode());

        $rndnum = new Typecho_Widget_Helper_Form_Element_Text('rndnum', NULL, '10', _t('随机文章列表数目'));
        $rndnum->input->setAttribute('class', 'mini');
        $form->addInput($rndnum->addRule('required', _t('必须填写文章列表数目'))
        ->addRule('isInteger', _t('文章数目必须是纯数字')));

        $rndtime = new Typecho_Widget_Helper_Form_Element_Text('rndtime', NULL, '60', _t('随机列表缓存时间'),_t('缓存时间单位为秒，设为0则禁用缓存'));
        $rndtime->input->setAttribute('class', 'mini');
        $form->addInput($rndtime->addRule('isInteger', _t('缓存时间必须是整数')));

        $rndlen = new Typecho_Widget_Helper_Form_Element_Text('rndlen', NULL, '0', _t('随机标题长度'),_t('这里设置截取的长度值，标题过长可能会影响版面，默认为0则不截取。'));
        $rndlen->input->setAttribute('class', 'mini');
        $form->addInput($rndlen->addRule('isInteger', _t('标题长度必须是整数')));

        $listnum = new Typecho_Widget_Helper_Form_Element_Text('hotnum', NULL, '10', _t('热门文章列表数目'));
        $listnum->input->setAttribute('class', 'mini');
        $form->addInput($listnum->addRule('required', _t('必须填写文章列表数目'))
        ->addRule('isInteger', _t('文章数目必须是纯数字')));

        $title_len = new Typecho_Widget_Helper_Form_Element_Text('hotlen', NULL, '0', _t('热门列表标题长度'),_t('这里设置截取的长度值，标题过长可能会影响版面，默认为0则不截取。'));
        $title_len->input->setAttribute('class', 'mini');
        $form->addInput($title_len->addRule('isInteger', _t('标题长度必须是整数')));
        
        $mode= new Typecho_Widget_Helper_Form_Element_Radio('mode',
                array( 'all' => '所有分类',
                       'manul' => '选择分类'),
                'all', '随机日志列表');
        $form->addInput($mode);
        
        $db1 = Typecho_Db::get();
        $test = $db1->fetchAll($db1
        ->select('table.metas.mid', 'table.metas.name')->from('table.metas')
        ->where('table.metas.type = ?', 'category'));
        $a = array();
       foreach($test as $item){
           $a[$item['mid']]=($item['name']);
       }
       
        $category = new Typecho_Widget_Helper_Form_Element_Checkbox('category',
        $a,array(),
        _t('分类显示随机日志'));
        $form->addInput($category->multiMode()); 
		
		$file = new Typecho_Widget_Helper_Form_Element_Text('file', null, '/usr/ArticleList.xml', _t('缓存文件存放位置'), _t('请确保随机列表缓存文件存放的目录可写！'));
		$form->addInput($file);
    }
    
    public static function  personalConfig(Typecho_Widget_Helper_Form $form) {}

    /**
     *输出热门列表
     *
     * @param string $format
     */
    public static function hot($format='<li><a href="{permalink}">[{commentsNum}]{title}</a></li>'){
        $option = Typecho_Widget::widget('Widget_Options')->plugin('ArticleList');
        if ($option->numset == 'a'){
            $num = Typecho_Widget::widget('Widget_Options')->postsListSize;
        }else{
            $num = $option->hotnum;
        }
        $db = Typecho_Db::get();     
        $rst = $db->fetchAll($db->select('cid','title','slug','created','type','commentsNum')->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', 'post')
//            ->where('created > ?',$option->gmtTime + $option->timezone - 180*24*3600) //热评收录范围：180天内(可修改对应数字，去掉本行开头两斜杠生效)
            ->order('table.contents.commentsNum',Typecho_Db::SORT_DESC)
            ->limit($num));
        foreach($rst as $result){
            $result['text'] = ''; //fix php5.6 warning
            $value = Typecho_Widget::widget('Widget_Abstract_Contents')->push($result);
            $title = $option->hotlen ? self::cutstr($value['title'],$option->hotlen) : $value['title'];
            echo str_replace(array('{permalink}','{title}','{commentsNum}'),array($value['permalink'],$title,$value['commentsNum']),$format);
        }
    }

        /**
     * 输出随机列表
     * 
     * @param string $format 输出格式
     */
    public static function random($format='<li><a href="{permalink}">{title}</a></li>'){
        $option = Typecho_Widget::widget('Widget_Options')->plugin('ArticleList');
        /**缓存文件*/
        $file = '.'.$option->file;
        
        /**获取日志列表数目*/
        if ($option->numset == 'a'){
            $randomNum = Typecho_Widget::widget('Widget_Options')->postsListSize;
        }else{
            $randomNum = $option->rndnum;         
        }
       
        /**处理随机列表*/        
        $xml1=@simplexml_load_file($file);
        /**可以直接返回xml对象*/
        if($xml1 && $option->rndtime!=0 && time()-$xml1->attributes()<$option->rndtime){
            foreach($xml1->rd as $rd)
            {
                echo str_replace(array('{permalink}','{title}'),array($rd->link,$rd->title),$format);
            }
        }else{ //读取数据库，判断是否输出或是更新缓存
                /**获取数据库连接*/
            $db=Typecho_Db::get();
                /**获取日志总数*/
            if($option->mode == 'all'){
                $rs = $db->fetchRow($db->select(array('COUNT(cid)' => 'total'))->from('table.contents')
                ->where('table.contents.status = ?', 'publish')
                ->where('table.contents.type = ?', 'post'));
            }else{
                $category = implode(",", $option->category);
                $sql = 'table.relationships.mid in ('.$category.')';
                $rs = $db->fetchRow($db->select(array('COUNT(table.contents.cid)' => 'total'))->from('table.contents')
                ->join('table.relationships', 'table.relationships.cid = table.contents.cid',Typecho_Db::INNER_JOIN)
                ->where('table.contents.status = ?', 'publish')
                ->where($sql));
            }
            $total=$rs['total'];

            /**设置随机数组*/
            srand((float) microtime() * 10000000);
            $ary=range(0,$total-1);
            if($randomNum>$total) $randomNum=$total;
            $rand = array_rand($ary, $randomNum);

            $list = '<lists/>';
            $xml = new SimpleXMLElement($list);
            $xml->addAttribute('time', time());

            if($option->mode == 'all'){
                foreach($rand as $index){
                    $result = $db->fetchRow($db->select('cid','title','slug','created','type')->from('table.contents')
                    ->where('table.contents.status = ?', 'publish')
                    ->where('table.contents.type = ?', 'post')
                    ->offset($index)
                    ->limit(1));

                    $result['text'] = ''; //fix php5.6 warning
                    $value = Typecho_Widget::widget('Widget_Abstract_Contents')->push($result);
                    $title = $option->rndlen ? self::cutstr($value['title'], $option->rndlen ) : $value['title'];
                    echo str_replace(array('{permalink}', '{title}'), array($value['permalink'],  $title), $format);
                    $rd=$xml->addChild('rd');
                    $rd->addChild('title',$title);
                    $rd->addChild('link',$value['permalink']);
                }
            }else{
                foreach($rand as $index){
                    $result = $db->fetchRow($db->select('table.contents.cid', 'table.contents.title','table.contents.created', 'table.contents.slug', 'table.contents.type')->from('table.contents')
                    ->join('table.relationships', 'table.relationships.cid = table.contents.cid',Typecho_Db::INNER_JOIN)
                    ->where('table.contents.status = ?', 'publish')
                    ->where($sql)
                    ->offset($index)
                    ->limit(1));

                    $result['text'] = ''; //fix php5.6 warning
                    $value = Typecho_Widget::widget('Widget_Abstract_Contents')->push($result);
                    $title = $option->rndlen ? self::cutstr($value['title'], $option->rndlen ) : $value['title'];
                    echo str_replace(array('{permalink}', '{title}'), array($value['permalink'],  $title), $format);
                    $rd=$xml->addChild('rd');
                    $rd->addChild('title',$title);
                    $rd->addChild('link',$value['permalink']);
                }
            }
            if($option->rndtime!=0)file_put_contents($file, $xml->asXML());            
        }   
    }

    /**
     *字符串截断
     *
     * @param string $string
     * @param interger $length
     * @return string
     */
    private static function cutstr($string, $length) {
        $wordscut='';
        $j=0;
        preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $string, $info);
        for($i=0; $i<count($info[0]); $i++) {
                $wordscut .= $info[0][$i];
                $j = ord($info[0][$i]) > 127 ? $j + 2 : $j + 1;
                if ($j > $length - 3) {
                        return $wordscut." ...";
                }
        }
        return join('', $info[0]);
    }
}
?>