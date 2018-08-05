<?php
/**
 * ShortLinks Plugin
 *
 * @copyright  Copyright (c) 2011 DEFE (http://defe.me)
 * @license	GNU General Public License 2.0
 * 
 */
class ShortLinks_Action extends Typecho_Widget implements Widget_Interface_Do
{
	private $db;   
	public function __construct($request, $response, $params = NULL)
	{
		parent::__construct($request, $response, $params);
		$this->db = Typecho_Db::get();
	}
	/**
	 * 添加新的链接转换
	 * 
	 */
	public function add(){
		$key = $this->request->key;
		$key = $key ? $key : Typecho_Common::randString(8);
		$target = $this->request->target;
		if($target === "" || $target === "http://"){
			$this->widget('Widget_Notice')->set(_t('请输入目标链接。'), NULL, 'error');
		}
		//判断key是否被占用
		elseif($this->getTarget($key)){
			$this->widget('Widget_Notice')->set(_t('该key已被使用，请更换key值。'), NULL, 'error');
		}  else {
			 $links=array(
			'key' => $key,
			'target' => $this->request->target,
			'count' => 0
			);
			$insertId = $this->db->query($this->db->insert('table.shortlinks')->rows($links));
		}
	}
	
	/**
	 * 修改链接
	 * 
	 */
	
	public function edit(){
		$target = $this->request->url;
		$id = $this->request->id;
		if(trim($target) == "" || $target == "http://"){
			Typecho_Response::throwJson('error');
		}else{
			if($id){
				$this->db->query($this->db->update('table.shortlinks')->rows(array('target' => $target))
					->where('id = ?', $id));
				Typecho_Response::throwJson('success');
			}
		}
	}
	/**
	 *删除链接转换
	 *
	 * @param int $id
	 */
	public function del($id){
		$this->db->query($this->db->delete('table.shortlinks')
					->where('id = ?', $id));
		
	}
	/**
	 * 链接重定向
	 * 
	 */
	public function shortlink(){
		$key = $this->request->key;
		$siteUrl = Typecho_Widget::widget('Widget_Options')->siteUrl;
		$requestString = str_replace("|","/",$key); // 特殊字符处理
		$referer = $this->request->getReferer(); 
		$pOption = Typecho_Widget::widget('Widget_Options')->Plugin('ShortLinks'); // 插件选项
		$referer_list = ShortLinks_Plugin::textareaToArr($pOption->referer_list); // 允许的referer列表
		$target = $this->getTarget($key);
		// 设置nofollow属性
		$this->response->setHeader('X-Robots-Tag','noindex, nofollow');
		if($target){
			// 自定义短链
			// 增加统计
			$count = $this->db->fetchObject($this->db->select('count')
				->from('table.shortlinks')
				->where('key = ?', $key))->count;
			$count = $count+1;
			$this->db->query($this->db->update('table.shortlinks')
				->rows(array('count' => $count))
				->where('key = ?', $key));
		} else if ($requestString === base64_encode(base64_decode($requestString))){
			// 自动转换链接处理
			$target = base64_decode($requestString);
			$allow_redirect = false; // 默认不允许跳转
			// 检查 referer
			$allow_redirect = ShortLinks_Plugin::checkDomain($referer, $referer_list); 
			if (strpos($referer,$siteUrl) !== false) {
				$allow_redirect = true;
			}
			if (!$allow_redirect) {
				// referer 非法跳转到首页
				$this->response->redirect($siteUrl,301);
				exit();
			}
		} else {
			throw new Typecho_Widget_Exception(_t('您访问的网页不存在'), 404);
		}
		if ($pOption->go_page == 0) {
			// 无跳转页面
			$this->response->redirect(htmlspecialchars_decode($target),301);
		} else {
			$html = str_replace(array('{{url}}', '{{delay}}'), array($target, $pOption->go_delay), file_get_contents(__DIR__ . '/go.html'));
			_e($html);
			exit();
		}
	}
	/**
	 * 获取目标链接
	 *
	 * @param string $key
	 * @return void
	 */
	public function getTarget($key){
		$target = $this->db->fetchRow($this->db->select('target')
				->from('table.shortlinks')
				->where(' key = ?' , $key));
		 if(isset($target['target'])){
			 return  $target['target'];
		 }else{
			 return FALSE;
		 }
	}
	
	/**
	 * 重设自定义链接
	 */
	public function resetLink(){
		$link = $this->request->link;
		Helper::removeRoute('go');
		Helper::addRoute('go', $link, 'ShortLinks_Action', 'shortlink');
		Typecho_Response::throwJson('success');
	}
	public function action(){
		$this->widget('Widget_User')->pass('administrator');
		$this->on($this->request->is('add'))->add();
		$this->on($this->request->is('edit'))->edit();
		$this->on($this->request->is('del'))->del($this->request->del);
		$this->on($this->request->is('resetLink'))->resetLink();
		$this->response->goBack();
	}
}
?>
