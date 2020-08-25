# Typecho_Plugin_CSDN
Typecho插件--将CSDN的博文迁移进Typecho

---

## 使用说明

1. 需要到自己已经登录的CSDN下的任意一个网页，使用开发者模式将三个cookie（UserName，UserInfo，UserToekn）拷贝下来粘贴进插件配置文本框中
2. 点击导入即可


----

## 可能遇到的问题的解决方法
1. PHP版本最好7以上。。。

2. 记得装完整php的插件，像是xml什么的

3. 如果你使用的是nginx代理，那么可能会出现php的pathinfo报404，即点击了上面的导入报404错误，可尝试下面的配置

```      

        if (!-e $request_filename) {
            rewrite ^(.*)$ /index.php$1 last;
        }
        location ~  .*\.php(\/.*)*$ {
            fastcgi_split_path_info ^(.+?\.php)(/.*)$;
            fastcgi_pass   localhost:9000;
            set $path_info "";
            set $real_script_name $fastcgi_script_name;
            if ($fastcgi_script_name ~ "^(.+?\.php)(/.+)$") {
                        set $real_script_name $1;
                        set $path_info $2;
            }
            fastcgi_param SCRIPT_NAME $real_script_name;
            fastcgi_param PATH_INFO $path_info;
            fastcgi_index  index.php;
            fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
            include        fastcgi_params;
        }
        
```
