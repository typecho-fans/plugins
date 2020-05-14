# ArticleTemplate
Custom post type for Typecho.

Version V2 is more powerful, but is a paid plugin.

## Usage
Download release and upload to /usr/plugins, be sure folder is `ArticleTemplate`

How to ouput the value of template:
```
<?php $this->template() ?>
```
or
```
<?php $template = $this->template ?>
```
if post type was `standard`, it would output an empty string.

PS:`$this` is a Widget_Archive object
## License
Copyright © 2016 [benzBrake](https://xiamp.net).

License: [The GNU v3 License](https://github.com/benzBrake/ArticleTemplate/raw/master/LICENSE).