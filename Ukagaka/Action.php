<?php
class Ukagaka_Action extends Typecho_Widget implements Widget_Interface_Do
{
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
    }

    public static function info(){
        $options = Typecho_Widget::widget('Widget_Options');
        $Ukagaka = $options->plugin('Ukagaka');
        $wcc['notice'] = stripslashes($Ukagaka->notice);

        $db = Typecho_Db::get();
        $select = $db->select()->from('table.options')
->where('name = ?', 'Ukagaka_starttime');
        $lifetime = $db->fetchAll($select);
        $lifetime = self::get_wcc_lifetime($lifetime[0]['value']);
        $name = Typecho_Widget::widget('Widget_Options')->title;
        $wcc['showlifetime'] = '我已经与主人 '.$name.' 一起生存了 <font color="red">'.$lifetime["day"].'</font> 天 <font color="red">'.$lifetime["hours"].'</font> 小时 <font color="red">'.$lifetime["minutes"].'</font> 分钟 <font color="red">'.$lifetime["seconds"].'</font> 秒的快乐时光啦～*^_^*';
        $foods = explode("\r\n", $Ukagaka->foods);
        foreach ($foods as $key => $value) {
            $xx = explode("//", $value);
            $wcc['foods'][] = $xx[0];
            $wcc['eatsay'][] = $xx[1];
        }
        if($Ukagaka->contact){
            $contact = explode("\r\n", $Ukagaka->contact);
            foreach ($contact as $key => $value) {
                $xx = explode("//", $value);
                $wcc['ques'][] = $xx[0];
                $wcc['ans'][] = $xx[1];
            }
        } else {
            $wcc['contactapi'] = '1';
        }
        $wcc = json_encode($wcc);
        echo $wcc;
    }

    public static function get_wcc_lifetime($starttime){
        $endtime = time();
        $lifetime = $endtime-$starttime;
        $day = intval($lifetime / 86400);
        $lifetime = $lifetime % 86400;
        $hours = intval($lifetime / 3600);
        $lifetime = $lifetime % 3600;
        $minutes = intval($lifetime / 60);
        $lifetime = $lifetime % 60;
        return array('day'=>$day, 'hours'=>$hours, 'minutes'=>$minutes, 'seconds'=>$lifetime);
    }
    /**
     * 绑定动作
     *
     * @access public
     * @return void
     */
    public function action(){
        $this->on($this->request);
        $this->info();
    }
}
?>
