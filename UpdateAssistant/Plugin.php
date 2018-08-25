<?php
/**
 * Update Assistant
 *
 * @package UpdateAssistant
 * @author  mrgeneral
 * @version 1.0.1
 * @link    https://www.chengxiaobai.cn
 */

class UpdateAssistant_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        Helper::addPanel(1, 'UpdateAssistant/Start.php', _t('Update Blogging Platform'), _t('Update Blogging Platform'), 'administrator');

        Helper::addRoute('version_latest', '/update-assistant/version/latest', 'UpdateAssistant_Action', 'getVersion');
        Helper::addRoute('version_process', '/update-assistant/version/process', 'UpdateAssistant_Action', 'action');
    }

    /**
     * For safety's sake, history is not deleted
     */
    public static function deactivate()
    {
        Helper::removePanel(1, 'UpdateAssistant/Start.php');

        Helper::removeRoute('version_latest');
        Helper::removeRoute('version_process');
    }

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $element = new Typecho_Widget_Helper_Form_Element_Radio('isDevelop', [0 => _t('No'), 1 => _t('Yes')], 1, _t('Upgrade to developer edition'));
        $form->addInput($element);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
        // TODO: Implement personalConfig() method.
    }
}
