<?php

class RoutesHelper_Action extends Typecho_Widget implements Widget_Interface_Do
{
    /**
     * 数据库中的路由数据
     *
     * @access private
     * @var array
     */
    private $_default = array();

    /**
     * 系统默认的路由数据
     *
     * @access private
     * @var array
     */
    private $_restore = array();

    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->_default = Helper::options()->routingTable;
        $this->_restore = unserialize('a:25:{s:5:"index";a:3:{s:3:"url";s:1:"/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:7:"archive";a:3:{s:3:"url";s:6:"/blog/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:2:"do";a:3:{s:3:"url";s:22:"/action/[action:alpha]";s:6:"widget";s:9:"Widget_Do";s:6:"action";s:6:"action";}s:4:"post";a:3:{s:3:"url";s:24:"/archives/[cid:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:10:"attachment";a:3:{s:3:"url";s:26:"/attachment/[cid:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:8:"category";a:3:{s:3:"url";s:17:"/category/[slug]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:3:"tag";a:3:{s:3:"url";s:12:"/tag/[slug]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:6:"author";a:3:{s:3:"url";s:22:"/author/[uid:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:6:"search";a:3:{s:3:"url";s:19:"/search/[keywords]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:10:"index_page";a:3:{s:3:"url";s:21:"/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:12:"archive_page";a:3:{s:3:"url";s:26:"/blog/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:13:"category_page";a:3:{s:3:"url";s:32:"/category/[slug]/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:8:"tag_page";a:3:{s:3:"url";s:27:"/tag/[slug]/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:11:"author_page";a:3:{s:3:"url";s:37:"/author/[uid:digital]/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:11:"search_page";a:3:{s:3:"url";s:34:"/search/[keywords]/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:12:"archive_year";a:3:{s:3:"url";s:18:"/[year:digital:4]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:13:"archive_month";a:3:{s:3:"url";s:36:"/[year:digital:4]/[month:digital:2]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:11:"archive_day";a:3:{s:3:"url";s:52:"/[year:digital:4]/[month:digital:2]/[day:digital:2]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:17:"archive_year_page";a:3:{s:3:"url";s:38:"/[year:digital:4]/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:18:"archive_month_page";a:3:{s:3:"url";s:56:"/[year:digital:4]/[month:digital:2]/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:16:"archive_day_page";a:3:{s:3:"url";s:72:"/[year:digital:4]/[month:digital:2]/[day:digital:2]/page/[page:digital]/";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:12:"comment_page";a:3:{s:3:"url";s:53:"[permalink:string]/comment-page-[commentPage:digital]";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}s:4:"feed";a:3:{s:3:"url";s:20:"/feed[feed:string:0]";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:4:"feed";}s:8:"feedback";a:3:{s:3:"url";s:31:"[permalink:string]/[type:alpha]";s:6:"widget";s:15:"Widget_Feedback";s:6:"action";s:6:"action";}s:4:"page";a:3:{s:3:"url";s:12:"/[slug].html";s:6:"widget";s:14:"Widget_Archive";s:6:"action";s:6:"render";}}');
    }

    /**
     * 恢复系统默认的路由
     */
    public function restore()
    {
        $modified = false;
        if ($this->request->isPost()) {
            foreach ($this->_default as $key => $value) {
                if (array_key_exists($key, $this->_restore) && $this->_restore[$key]['url'] != $this->_default[$key]['url']) {
                    Helper::removeRoute($key);
                    Helper::addRoute($key, $this->_restore[$key]['url'], $this->_restore[$key]['widget'], $this->_restore[$key]['action']);
                    $modified = true;
                }
            }
        }
        if ($modified) {
            $this->widget('Widget_Notice')->set(_t("已恢复为系统默认路由"), NULL, 'success');
        } else {
            $this->widget('Widget_Notice')->set(_t("当前路由已为默认，无需恢复"), NULL, 'notice');
        }
    }

    /**
     * 修改路由
     */
    public function edit()
    {
        $modified = false;
        if ($this->request->isPost()) {
            foreach ($this->_default as $key => $value) {
                if (array_key_exists($key, $this->_restore) && $this->request->__isSet($key) && $this->request->{$key} != $this->_default[$key]['url'] && $key != 'do') {
                    Helper::removeRoute($key);
                    Helper::addRoute($key, $this->request->{$key}, $this->_default[$key]['widget'], $this->_default[$key]['action']);
                    $modified = true;
                }
            }
        }
        if ($modified) {
            $this->widget('Widget_Notice')->set(_t("路由变更已经保存"), NULL, 'success');
        } else {
            $this->widget('Widget_Notice')->set(_t("路由未变更"), NULL, 'notice');
        }
    }

    /**
     * 绑定动作
     *
     * @access public
     * @return void
     */
    public function action(){
        $this->widget('Widget_User')->pass('administrator');
        $this->on($this->request->is('restore'))->restore();
        $this->on($this->request->is('edit'))->edit();
        $this->response->goBack();
    }
}
?>
