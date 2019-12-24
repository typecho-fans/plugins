<?php
class JustFeed_Widget extends Widget_Archive
{
	/**
	 * 请到 Widget_Archive 中看以下私有变量的作用
	 * 由于父类中是私有变量不能在子类中直接使用，只能间接获取
	 */
	private $_feed;
	private $_description;
	private $_archiveTitle;
	private $_feedType;
	
	private $_currentFeedUrl;
	private $_feedContentType;
	
	/**
	 * 构造函数,初始化组件
	 *
	 * @access public
	 * @param mixed $request request对象
	 * @param mixed $response response对象
	 * @param mixed $params 参数列表
	 * @return void
	 */
	public function __construct($request, $response, $params = NULL)
	{
		parent::__construct($request, $response, $params);
		
		$this->_feed = $this->getFeed();
		$this->_feedType = $this->getFeedType();
		
		/** 判断聚合类型 */
		switch (true) {
			case 0 === strpos($this->request->feed, '/rss/') || '/rss' == $this->request->feed:
				/** 如果是RSS1标准 */
				$this->_currentFeedUrl = $this->options->feedRssUrl;
				$this->_feedContentType = 'application/rdf+xml';
				break;
			case 0 === strpos($this->request->feed, '/atom/') || '/atom' == $this->request->feed:
				/** 如果是ATOM标准 */
				$this->_currentFeedUrl = $this->options->feedAtomUrl;
				$this->_feedContentType = 'application/atom+xml';
				break;
			default:
				$this->_currentFeedUrl = $this->options->feedUrl;
				$this->_feedContentType = 'application/rss+xml';
				break;
		}
	}
	
	/**
	 * 输出 feed
	 */
	public function feed()
	{
		$this->_description = $this->getDescription();
		$this->_archiveTitle = $this->getArchiveTitle();
		
		// 获取系统设置
		$options = Helper::options();
		// 获取 JustFeed 设置
		$settings = Helper::options()->plugin('JustFeed');
		//$related_post_num = is_numeric($settings->cfg_related_post_num) ? intval($settings->cfg_related_post_num) : 5;
		
		$search = array('{sitetitle}','{siteurl}','{author}','{authorurl}','{permalink}','{date}','{time}','{commentsnumber}');
		
        $this->_feed->setSubTitle($this->_description);
        $this->_feed->setFeedUrl($this->_currentFeedUrl);

        $this->_feed->setBaseUrl(('/' == $this->request->feed || 0 == strlen($this->request->feed)
        || '/comments' == $this->request->feed || '/comments/' == $this->request->feed) ?
        $this->options->siteUrl : Typecho_Common::url($this->request->feed, $this->options->index));
        $this->_feed->setFeedUrl($this->request->makeUriByRequest());

        if ($this->is('single') || 'comments' == $this->parameter->type) {
            $this->_feed->setTitle(_t('%s 的评论',
            $this->options->title . ($this->_archiveTitle ? ' - ' . implode(' - ', $this->_archiveTitle) : NULL)));

            if ('comments' == $this->parameter->type) {
                $comments = $this->widget('Widget_Comments_Recent', 'pageSize=10');
            } else {
                $comments = $this->widget('Widget_Comments_Recent', 'pageSize=10&parentId=' . $this->cid);
            }

            while ($comments->next()) {
				$d = getdate($comments->created);
				$replace = array(
					$options->title,
					$options->siteUrl,
					$this->author->screenName,
					$this->author->url,
					$comments->permalink,
					$d['year'].'/'.$d['mon'].'/'.$d['mday'],
					$d['hours'].':'.$d['minutes'].':'.$d['seconds'],
					$this->commentsNum
				);
				
				$copyright = str_replace($search, $replace, $settings->cfg_copyright);
				
                $suffix = $this->pluginHandle()->trigger($plugged)->commentFeedItem($this->_feedType, $comments);
                if (!$plugged) {
                    $suffix = NULL;
                }

                $this->_feed->addItem(array(
                    'title'     =>  $comments->author,
                    'content'   =>  $comments->content . $copyright,
                    'date'      =>  $comments->created,
                    'link'      =>  $comments->permalink,
                    'author'    =>  (object) array(
                        'screenName'  =>  $comments->author,
                        'url'         =>  $comments->url,
                        'mail'        =>  $comments->mail
                    ),
                    'excerpt'   =>  strip_tags($comments->content),
                    'suffix'    =>  $suffix
                ));
            }
        } else {
            $this->_feed->setTitle($this->options->title . ($this->_archiveTitle ? ' - ' . implode(' - ', $this->_archiveTitle) : NULL));

            $feedUrl = '';
            if (Typecho_Feed::RSS2 == $this->_feedType) {
                $feedUrl = $this->feedUrl;
            } else if (Typecho_Feed::RSS1 == $this->_feedType) {
                $feedUrl = $this->feedRssUrl;
            } else if (Typecho_Feed::ATOM1 == $this->_feedType) {
                $feedUrl = $this->feedAtomUrl;
            }

            while ($this->next()) {
				//set_time_limit(60);
				// 获取文字内容的时间
				$d = getdate($this->created);
				$replace = array(
					$options->title,
					$options->siteUrl,
					$this->author->screenName,
					$this->author->url,
					$this->permalink,
					$d['year'].'/'.$d['mon'].'/'.$d['mday'],
					$d['hours'].':'.$d['minutes'].':'.$d['seconds'],
					$this->commentsNum
				);
				
				$copyright = str_replace($search, $replace, $settings->cfg_copyright);
				
				// 相关日志
/* 				$related_post_html = '';
				if ($settings->cfg_related_post) {
					$relatedPosts = $this->related($related_post_num);
					if ($relatedPosts->have()) {
						$related_post_html = '<h4>相关日志</h4><ul>';
						while ($relatedPosts->next()) {
							$related_post_html .= '<li><a href="' . $relatedPosts->permalink . '" title="' . $relatedPosts->title . '">' . $relatedPosts->title . '</a></li>';
						}
						$related_post_html .= '</ul>';
					}
				} */

                $suffix = $this->pluginHandle()->trigger($plugged)->feedItem($this->_feedType, $this);
                if (!$plugged) {
                    $suffix = NULL;
                }

                $this->_feed->addItem(array(
                    'title'     =>  $this->title,
                    'content'   =>  $this->options->feedFullText ? $this->content.$related_post_html.$copyright : (false !== strpos($this->text, '<!--more-->') ?
                    $this->excerpt . "<p class=\"more\"><a href=\"{$this->permalink}\" title=\"{$this->title}\">[...]</a></p>" . $related_post_html.$copyright : $this->content.$related_post_html.$copyright),
                    'date'      =>  $this->created,
                    'link'      =>  $this->permalink,
                    'author'    =>  $this->author,
                    'excerpt'   =>  $this->description,
                    'comments'  =>  $this->commentsNum,
                    'commentsFeedUrl' => $feedUrl,
                    'suffix'    =>  $suffix
                ));
            }
        }

        $this->response->setContentType($this->_feedContentType);
        echo $this->_feed->__toString();
	}
}