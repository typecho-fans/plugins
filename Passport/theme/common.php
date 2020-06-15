<?php
if (!defined('__DIR__')) {
    define('__DIR__', dirname(__FILE__));
}

define('__TYPECHO_ADMIN__', true);
define('__ADMIN_DIR__', __TYPECHO_ROOT_DIR__ . __TYPECHO_ADMIN_DIR__);

/** 初始化组件 */
Typecho_Widget::widget('Widget_Init');
Typecho_Widget::widget('Widget_Options')->to($options);
Typecho_Widget::widget('Widget_Security')->to($security);
Typecho_Widget::widget('Widget_Menu')->to($menu);

list($prefixVersion, $suffixVersion) = explode('/', $options->version);
