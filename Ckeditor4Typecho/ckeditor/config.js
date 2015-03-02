/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	config.language = 'zh-cn';
	// config.uiColor = '#AADC6E';
	config.enterMode = CKEDITOR.ENTER_P;

	config.toolbar_STANDARD =
	[
	    ['Source','-','Save','NewPage','Preview','-','Templates'],
	    ['Cut','Copy','Paste','PasteText','PasteFromWord','-','Print', 'SpellChecker', 'Scayt'],
	    ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	    ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'],
	    '/',
	    ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
	    ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote','CreateDiv'],
	    ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
	    ['BidiLtr', 'BidiRtl'],
	    ['Link','Unlink','Anchor'],
	    ['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak','Iframe'],
	    '/',
	    ['Styles','Format','Font','FontSize'],
	    ['TextColor','BGColor'],
	    ['Maximize', 'ShowBlocks']
	];
	
	config.toolbar_SIMPLE =
	[
	    ['Bold','Italic','Underline','Strike','-','TextColor','BGColor','RemoveFormat','-',],
	    ['NumberedList','BulletedList','Blockquote','JustifyLeft','JustifyCenter','-'],
	    ['Preview','Image','Table','HorizontalRule','SpecialChar'] ,
	    ['Undo','Redo','-','Replace'],
	    '/',
	    ['Font','Format','FontSize'],
	    ['Cut','Copy','Paste','PasteText','PasteFromWord'],
	    ['Maximize', 'ShowBlocks','-','Source']	    
	];

	config.toolbar_MINI =
	[
	    ['Bold','Italic','Underline','-','TextColor','BGColor','RemoveFormat','-',],
	    ['NumberedList','BulletedList','JustifyLeft','JustifyCenter','-'],
	    ['Table','HorizontalRule','SpecialChar'] ,
	    '/',
	    ['Source','Preview','-','Font','Format','FontSize']
	];

	config.format_p = { element: 'p', styles: { 'text-indent': '2em' } };
	
	config.font_names = '宋体;微软雅黑;黑体;楷体;隶书;Arial;Times New Roman;Verdana;Comic Sanc MS;Courier New;';

	config.font_defaultLabel = '宋体';

	config.fontSize_defaultLabel = '14px';

	config.fontSize_sizes ='12/12px;14/14px;16/16px;18/18px;20/20px;22/22px;24/24px;26/26px;28/28px;36/36px;';

	config.allowedContent = true;

	config.disallowedContent = 'script;link;a;style; *[on*]';
};
