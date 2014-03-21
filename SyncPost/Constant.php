<?php
    define("TQQ_CLIENT_ID","801489085");
    define("TQQ_CLIENT_SECRET","7133c0e58792a223cdde4bd83e6de8d4");
    define("TQQ_REDIRECT_URI","action/SyncPost?tqq");
    define("TQQ_AUTHORIZATION_CODE_URL","https://open.t.qq.com/cgi-bin/oauth2/authorize");
    define("TQQ_ACCESS_TOKEN_URL","https://open.t.qq.com/cgi-bin/oauth2/access_token");
    define("TQQ_API_URL","https://open.t.qq.com/api/t/add");

    define("SINA_CLIENT_ID","773255307");
    define("SINA_CLIENT_SECRET","8fc87301c160d2e9dba7f124c3d4bd45");
    define("SINA_REDIRECT_URI","action/SyncPost?sina");
    define("SINA_AUTHORIZATION_CODE_URL","https://api.weibo.com/oauth2/authorize");
    define("SINA_ACCESS_TOKEN_URL","https://api.weibo.com/oauth2/access_token");
    define("SINA_API_URL","https://api.weibo.com/2/statuses/update.json");
    
    define("DOUBAN_CLIENT_ID","07132850243491e5153978f06c9db5af");
    define("DOUBAN_CLIENT_SECRET","a2a357aaafc19903");
    define("DOUBAN_REDIRECT_URI","action/SyncPost?douban");
    define("DOUBAN_AUTHORIZATION_CODE_URL","https://www.douban.com/service/auth2/auth");
    define("DOUBAN_ACCESS_TOKEN_URL","https://www.douban.com/service/auth2/token");
    define("DOUBAN_API_URL","https://api.douban.com/shuo/v2/statuses/");
?>