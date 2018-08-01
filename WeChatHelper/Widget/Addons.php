<?php
/**
 * WeChatHelper Plugin
 *
 * @copyright  Copyright (c) 2013 Binjoo (http://binjoo.net)
 * @license    GNU General Public License 2.0
 * 
 */
include_once 'Utils.php';
class WeChatHelper_Widget_Addons extends Widget_Abstract implements Widget_Interface_Do {
    public function __construct($request, $response, $params = NULL) {
        parent::__construct($request, $response, $params);
    }
    public function select() {}
    public function insert(array $options) {}
    public function update(array $options, Typecho_Db_Query $condition){}
    public function delete(Typecho_Db_Query $condition){}
    public function size(Typecho_Db_Query $condition){}

    public function getAddons(){
        $files = glob(__TYPECHO_ROOT_DIR__ . '/' . __TYPECHO_PLUGIN_DIR__ . '/WeChatHelper/Addons/*/Addon.php');
        $result = array();
        foreach ($files as $file) {
            $info = $this->parseInfo($file);
            if ($info['name'] != '' && $info['package'] != '') {
                $result[$file] = $info;
            }
        }
        return $result;
    }

    public function parseInfo($file){
        $tokens = token_get_all(file_get_contents($file));
        $isDoc = false;

        $info = array(
            'name'   =>  '',
            'author'    =>  '',
            'link'    =>  '',
            'package'   =>  '',
            'version'   =>  '',
            'description'   =>  '',
            'param'   =>  ''
        );

        foreach ($tokens as $token) {
            if (is_array($token) && T_DOC_COMMENT == $token[0]) {
                /** 分行读取 */
                $lines = preg_split("(\r|\n)", $token[1]);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line) && '*' == $line[0]) {
                        $line = trim(substr($line, 1));

                        if (!empty($line) && '@' == $line[0]) {
                            $line = trim(substr($line, 1));
                            $args = explode(' ', $line);
                            $key = array_shift($args);
                            if (isset($key)) {
                                $info[$key] = trim(implode(' ', $args));
                            }
                        }
                    }
                }
            }
        }
        return $info;
    }

    public function action() {
    }
}
