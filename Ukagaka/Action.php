<?php
class Ukagaka_Action extends Typecho_Widget implements Widget_Interface_Do
{
  public function __construct($request, $response, $params = NULL)
  {
    parent::__construct($request, $response, $params);
  }

  private function info($t)
  {
    $database = Typecho_Db::get();
    $options  = Typecho_Widget::widget('Widget_Options');
    $Ukagaka  = $options->plugin('Ukagaka');

    $select   = $database->select()->from('table.options')
                         ->where('name = ?', 'Ukagaka_starttime');
    $lifetime = $database->fetchAll($select);

    $ki = array();

    $ki['notice']   = stripslashes($Ukagaka->notice);
    $ki['nickname'] = stripcslashes($Ukagaka->nickname);
    $ki['born']     = stripslashes($lifetime[0]['value']);

    $foods = explode("\r\n", $Ukagaka->foods);

    foreach ($foods as $key => $value) {
      $xx = explode("//", $value);
      $ki['foods'][]  = $xx[0];
      $ki['eatsay'][] = $xx[1];
    }

    if ($Ukagaka->contact) {
      $contact = explode("\r\n", $Ukagaka->contact);

      foreach ($contact as $key => $value) {
        $xx = explode("//", $value);
        $ki['question'][] = $xx[0];
        $ki['answer'][]  = $xx[1];
      }
    }

    if ($Ukagaka->selftalk) {
      $selftalk = explode("\r\n", $Ukagaka->selftalk);
      foreach ($selftalk as $key => $value) {
        $ki['talk'][] = explode("//", $value);
      }
    } else {
      $ki['talk'] = '';
    }

    $ki = json_encode($ki);
    echo $ki;
  }

  private function get_ki_lifetime($starttime)
  {
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
  public function action()
  {
    header('Content-type: application/json');

    $this->on($this->request);
    $this->info((isset($_GET['type']) ? htmlspecialchars($_GET['type']) : ''));
  }
}