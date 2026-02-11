# LinkGo ：Typecho 跳转中间页插件

[![Release](https://img.shields.io/github/v/release/lhl77/Typecho-Plugin-LinkGo?label=release)](https://github.com/lhl77/Typecho-Plugin-LinkGo/releases)
[![Stars](https://img.shields.io/github/stars/lhl77/Typecho-Plugin-LinkGo?style=social)](https://github.com/lhl77/Typecho-Plugin-LinkGo/stargazers)
[![Issues](https://img.shields.io/github/issues/lhl77/Typecho-Plugin-LinkGo)](https://github.com/lhl77/Typecho-Plugin-LinkGo/issues)
[![License](https://img.shields.io/github/license/lhl77/Typecho-Plugin-LinkGo)](https://github.com/lhl77/Typecho-Plugin-LinkGo/blob/master/LICENSE)

跳转页模板位于插件目录：
`LinkGo/page/themes/<ThemeName>/template.php` 与 `style.css`。

## 安装与启用
1. 把 `LinkGo` 文件夹放到 Typecho 的 `usr/plugins/` 目录下（或你项目的相应插件目录）。
2. 在 Typecho 管理后台 -> 插件，启用 LinkGo。
3. 启用后插件会尝试注册 `/go/[target]` 路由（不同 Typecho 版本可能差异），启用失败请手动刷新插件或在 Typecho 后台禁用再启用一次。

## 主题目录结构与约定
在插件目录下放置主题：
```
LinkGo/
  page/
    themes/
      Default/
        template.php
        style.css
      MyTheme/
        template.php
        style.css
```

约定：
- `template.php`：必须输出完整的跳转页 HTML（或至少渲染主体），并读取由 Action 或 loader 提供的变量。通常包含 `<head>`、样式、主体与页脚。插件也内置了一个简单 loader (`page/index.php`) 可把通用变量注入到模板。
- `style.css`：主题样式，应使用相对选择器限定在主题容器内，避免污染站点其他风格。

## 模板文件中可用的变量
在 `template.php` 中，以下变量通常可用（由插件 Action 或 loader 提供）：
- `$title` — 跳转页标题（通常为 `LinkGo` 或自定义）
- `$siteTitle` — 站点显示标题（来自配置）
- `$logoUrl` — 配置的 Logo URL
- `$url` — 解码后的目标 URL（完整）
- `$display_url` — 用于前端显示的简短目标（如去掉协议或做截断）
- `$displayYear` — 页脚要显示的年份（从 `startYear` 到当前年）
- `$themeName` — 当前主题名

注意：若你直接包含 `template.php`（例如本地测试），请确保这些变量存在或为其设置默认值，以防 PHP Notice。

## 贡献与支持
- 项目仓库： https://github.com/lhl77/Typecho-Plugin-LinkGo
- 使用文档（作者博客）： https://blog.lhl.one/artical/949.html

欢迎提交 Issue 或 Pull Request，主题模板与样式也非常欢迎贡献示例主题。
