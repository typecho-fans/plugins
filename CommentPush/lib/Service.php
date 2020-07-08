<?php
/**
 * @author gaobinzhan <gaobinzhan@gmail.com>
 */

require_once 'Contract/ServiceInterface.php';

abstract class Service implements ServiceInterface
{
    public function __handler($active, $comment, $plugin)
    {
        // TODO: Implement __handler() method.
    }

    public function logger($service, $object, $context, $result, $error = '')
    {
        $db = Typecho_Db::get();
        $db->query($db->insert('table.comment_push')
            ->rows([
                'service' => $service,
                'object' => is_array($object) ? json_encode($object, JSON_UNESCAPED_UNICODE) : $object,
                'context' => is_array($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : $context,
                'result' => is_array($result) ? json_encode($result, JSON_UNESCAPED_UNICODE) : $result,
                'error' => is_array($error) ? json_encode($error, JSON_UNESCAPED_UNICODE) : $error,
                'time' => time()
            ]));
    }
}