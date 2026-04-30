# [AutoTags](https://dt27.cn/php/autotags-for-typecho/)

**标签自动生成插件 For Typecho**

~~关键词提取 API：[玻森](http://bosonnlp.com/)~~

~~因玻森服务异常，标签提取API由玻森改为百度。~~

由于百度文章标签api开始收费，改为AI提取。

目前使用的AI API：OpenRouter

可选免费AI模型列表：https://openrouter.ai/models?fmt=cards&max_price=0&order=newest&output_modalities=text  

~~建议使用`deepseek/deepseek-chat:free`效果较好，免费~~

目前测试可用模型：`minimax/minimax-m2.5:free`

> 发布文章后发现没有自动提取标签时，请到网站错误日志查看错误记录，如果有类似错误：message＂:＂Provider returned error＂,＂code＂:429,＂metadata＂:{＂raw＂:＂qwen/qwen3-next-80b-a3b-instruct:free is temporarily rate-limited upstream. Please retry shortly, or add your own key to accumulate your rate limits，说明该免费模型使用人数过多，换个模型就行了。

## Features

* 新建及编辑文章时自动提取标签, 默认生成5个
* 当已存在标签或已手动设置标签时不再自动生成


## Features

*   [√] 新建及编辑文章时自动提取标签, 1-5个
*   [√] 当已存在标签或已手动设置标签时不再自动生成

## License

> Copyright © 2016-2025 [DT27](https://dt27.cn)
> License: [GNU General Public License v3.0](http://www.gnu.org/licenses/gpl-3.0.html)

