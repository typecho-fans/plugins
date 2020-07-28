<?php
/**
 * @author gaobinzhan <gaobinzhan@gmail.com>
 */


interface ServiceInterface
{
    public function __handler($active, $comment, $plugin);

    public function logger($object, $context, $result, $error);
}