<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class Sitemap_Action extends Typecho_Widget implements Widget_Interface_Do
{
	public function action()
	{
		$db = Typecho_Db::get();
		$options = Typecho_Widget::widget('Widget_Options');

		$pages = $db->fetchAll($db->select()->from('table.contents')
		->where('table.contents.status = ?', 'publish')
		->where('table.contents.created < ?', $options->gmtTime)
		->where('table.contents.type = ?', 'page')
		->order('table.contents.created', Typecho_Db::SORT_DESC));

		$articleCount = $db->fetchObject($db->select(array('COUNT(table.contents.cid)' => 'num'))->from('table.contents')
		->where('table.contents.status = ?', 'publish')
		->where('table.contents.created < ?', $options->time)
		->where('table.contents.type = ?', 'post'))->num;

		Typecho_Widget::widget(
			'Widget_Contents_Post_Recent@sitemapArticles',
			'pageSize=' . max(1, (int) $articleCount)
		)->to($articles);
		
		Typecho_Widget::widget('Widget_Metas_Category_List@cate')->to($cates);

		$tags = $db->fetchAll($db->select()->from('table.metas')
		->where('table.metas.type = ?', 'tag')
		->order('table.metas.mid', Typecho_Db::SORT_DESC));
		$minTagCount = max(1, (int) $options->plugin('Sitemap')->minTagCount);

		header("Content-Type: application/xml");
		echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
		echo "<?xml-stylesheet type='text/xsl' href='" . $options->pluginUrl . "/Sitemap/sitemap.xsl'?>\n";
		echo "<urlset xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\nxsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\"\nxmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";
		echo "\t<url>\n";
		echo "\t\t<loc>".$options->siteUrl."</loc>\n";
		echo "\t\t<changefreq>daily</changefreq>\n";
		echo "\t\t<priority>1.0</priority>\n";
		echo "\t</url>\n";
		foreach($pages AS $page) {
			$type = $page['type'];
			$routeExists = (NULL != Typecho_Router::get($type));
			$page['pathinfo'] = $routeExists ? Typecho_Router::url($type, $page) : '#';
			$page['permalink'] = Typecho_Common::url($page['pathinfo'], $options->index);

			echo "\t<url>\n";
			echo "\t\t<loc>".$page['permalink']."</loc>\n";
			echo "\t\t<lastmod>".date('Y-m-d',$page['modified'])."</lastmod>\n";
			echo "\t\t<changefreq>always</changefreq>\n";
			echo "\t\t<priority>0.6</priority>\n";
			echo "\t</url>\n";
		}
		while($articles->next()) {
			echo "\t<url>\n";
			echo "\t\t<loc>".$articles->permalink."</loc>\n";
			echo "\t\t<lastmod>".date('Y-m-d',$articles->modified)."</lastmod>\n";
			echo "\t\t<changefreq>always</changefreq>\n";
			echo "\t\t<priority>0.8</priority>\n";
			echo "\t</url>\n";
		}
		while($cates->next()){
			$art_rs = $db->fetchRow($db->select()->from('table.contents')
					->join('table.relationships', 'table.contents.cid = table.relationships.cid')
					->where('table.contents.status = ?', 'publish')
					->where('table.relationships.mid = ?', $cates->mid)
					->order('table.relationships.cid', Typecho_Db::SORT_DESC)
					->limit(1));
		    //文章的分类跳过
            if (empty($art_rs['modified'])) continue;
			echo "\t<url>\n";
			echo "\t\t<loc>".$cates->permalink."</loc>\n";
			echo "\t\t<lastmod>".date('Y-m-d',$art_rs['modified'])."</lastmod>\n";
			echo "\t\t<changefreq>daily</changefreq>\n";
			echo "\t\t<priority>0.5</priority>\n";
			echo "\t</url>\n";
		}
		foreach($tags AS $tag) {
			$type = $tag['type'];
			$tagArticles = $db->fetchAll($db->select('table.contents.modified')->from('table.contents')
					->join('table.relationships', 'table.contents.cid = table.relationships.cid')
					->where('table.contents.status = ?', 'publish')
					->where('table.contents.created < ?', $options->time)
					->where('table.contents.type = ?', 'post')
					->where('table.relationships.mid = ?', $tag['mid'])
					->order('table.contents.modified', Typecho_Db::SORT_DESC)
					->limit($minTagCount));
			if (count($tagArticles) < $minTagCount) continue;
			$latestTagArticle = current($tagArticles);
					
			$routeExists = (NULL != Typecho_Router::get($type));
			$tag['pathinfo'] = $routeExists ? Typecho_Router::url($type, $tag) : '#';
			$tag['permalink'] = Typecho_Common::url($tag['pathinfo'], $options->index);

			echo "\t<url>\n";
			echo "\t\t<loc>".$tag['permalink']."</loc>\n";
			echo "\t\t<lastmod>".date('Y-m-d',$latestTagArticle['modified'])."</lastmod>\n";
			echo "\t\t<changefreq>daily</changefreq>\n";
			echo "\t\t<priority>0.5</priority>\n";
			echo "\t</url>\n";
		}
		echo "</urlset>";
	}
}
