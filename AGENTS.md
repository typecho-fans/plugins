# 约定

如果 `AGENTS.local.md` 文件存在，请优先遵循该文件中的约定。

## Typecho 插件开发

- 新增或修改的插件以 Typecho 1.2.1+ 为兼容目标，并尽量保持插件原有代码风格与兼容范围。
- 插件使用独立目录，入口文件为 `Plugin.php`；目录名、`@package` 与插件类名前缀保持一致。
- 插件类实现 `Typecho_Plugin_Interface`，按需提供 `activate`、`deactivate`、`config` 和 `personalConfig` 方法。
- PHP 入口文件应阻止脱离 Typecho 环境直接访问：`if (!defined('__TYPECHO_ROOT_DIR__')) exit;`
- 优先使用 Typecho 提供的插件钩子、路由、表单、数据库和 URL API，不硬编码安装路径、站点地址或数据表前缀。
- 激活时注册的路由、面板等资源，应在停用时对应清理；未经明确要求，不在停用或卸载时删除用户数据。
- 对外部输入进行验证，对页面输出进行转义；后台操作应检查用户权限及请求来源。
- 修改插件版本或发布信息时，先更新插件头信息及插件目录内的 README；根目录 `README.md`、`TESTORE.md` 和部分 `ZIP_CDN` 数据由 `AUTO-UPDATE.php` 及 GitHub Actions 自动维护，除新增或修正收录信息外无需顺手修改。
- 不对无关插件、第三方依赖或生成数据进行顺手重构。
