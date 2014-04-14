<?php
/**
 * @name 驾驶执照考试
 * @package DrivingTest
 * @author 冰剑
 * @version 1.0.0
 *
 * @param false
 */
class AddonsDrivingTest {
    private $result;
    private $data;

    function __construct($result) {
        $this->result = $result;

        $this->data = array(
            array(
                'title' => '驾校一点通模拟考试',
                'description' => '',
                'picurl' => '',
                'url' => ''
            ),
            array(
                'title' => '小车考试科目一 车型：C1 C2照',
                'description' => '',
                'picurl' => '',
                'url' => 'http://m.jxedt.com/mnks/indexlx.asp'
            ),
            array(
                'title' => '货车考试科目一 车型：A2 B2照',
                'description' => '',
                'picurl' => '',
                'url' => 'http://m.jxedt.com/mnks/indexlx.asp?type=b'
            ),
            array(
                'title' => '客车考试科目一 车型：A1 A3 B1照',
                'description' => '',
                'picurl' => '',
                'url' => 'http://m.jxedt.com/mnks/indexlx.asp?type=a'
            ),
            array(
                'title' => '安全文明科目四 车型：C1 C2 B2照',
                'description' => '',
                'picurl' => '',
                'url' => 'http://m.jxedt.com/mnks/indexlx.asp?type=s'
            )
        );
    }

    public function execute(){
        foreach ($this->data as $row) {
            $this->result->addItem($row);
        }
        $this->result->setMsgType(MessageTemplate::NEWS)->send();
    }
}
?>
