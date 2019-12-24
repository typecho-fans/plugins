# JustFeed
Ver 0.1.1 有一个bug，输出RSS的时候，如果用上的{date}参数，结果只能显示 “年” 和 “月” ，不能显示 “日”。  
如图：  
![](https://github.com/eallion/JustFeed/blob/master/bug.png)

今天发现了，随手把里面的 93 行和 141 行修改了一下
```
$d['year'].'/'.$d['mon'].'/'.$date['mday'],
```
改为：
```
$d['year'].'/'.$d['mon'].'/'.$d['mday'],
```

这个插件是jKey开发的，原作者的网站已经打不开了。  
感谢原作者的付出。  
[http://typecho.jkey.lu/](http://typecho.jkey.lu)
