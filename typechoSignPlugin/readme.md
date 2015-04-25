## typecho 用户个性签名插件

### 安装方法：
在插件目录下面建立UserSign文件夹，把Plugin.php复制进去

**注意：由于typecho的问题，插件的用户配置在读取的时候会自动读取在配置表里面的插件数据。如果要让用户的设置正常运行请修改 '/var/Widget/Users/Profile.php'。修改下面这个函数。不修改的话用户的个人页面里面显示用户的当前签名**

    /**
     * 输出自定义设置选项
     *
     * @access public
     * @param string $pluginName 插件名称
     * @param string $className 类名称
     * @param string $pluginFileName 插件文件名
     * @param string $group 用户组
     * @return Typecho_Widget_Helper_Form
     */
    public function personalForm($pluginName, $className, $pluginFileName, &$group)
    {
        /** 构建表格 */
        $form = new Typecho_Widget_Helper_Form($this->security->getIndex('/action/users-profile'),
        Typecho_Widget_Helper_Form::POST_METHOD);
        $form->setAttribute('name', $pluginName);
        $form->setAttribute('id', $pluginName);

        require_once $pluginFileName;
        $group = call_user_func(array($className, 'personalConfig'), $form);
        $group = $group ? $group : 'subscriber';

        $options = $this->options->personalPlugin($pluginName);

        if (!empty($options)) {
            foreach ($options as $key => $val) {
				if(!isset($form->getInput($key)->value))
					$form->getInput($key)->value($val);
            }
        }

        $form->addItem(new Typecho_Widget_Helper_Form_Element_Hidden('do', NULL, 'personal'));
        $form->addItem(new Typecho_Widget_Helper_Form_Element_Hidden('plugin', NULL, $pluginName));
		
		$submit = new Typecho_Widget_Helper_Form_Element_Submit(NULL, NULL, _t('保存设置'));
		$submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);
		
        return $form;
    }

###使用方法

由于已经在users表里面插入了userSign这个字段了，这个字段会自动的被系统读取。只需要在需要的地方输出$user->userSign就可以了