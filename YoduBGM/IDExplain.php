<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
	<title>网易云音乐id解析</title><meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no"><meta http-equiv="X-UA-Compatible" content="IE=edge, chrome=1"> <meta name="theme-color" content="#FFF"> <meta name="full-screen" content="yes"><meta name="x5-fullscreen" content="true"><meta name="HandheldFriendly" content="true">
  <meta name="viewport" content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no,minimal-ui">
    <style>
        #title {
            padding: 2px;
            margin: 0 0 10px;
        }
        h1 {
            font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
            font-weight: 500;
            width: 300px;
            margin: 5px auto;
        }
        form {
                max-width: 80%;
            padding: 19px 29px 29px;
            margin: 15px auto 20px;
            background-color: rgba(239, 130, 130, 0.11);
            -webkit-border-radius: 10px;
            -moz-border-radius: 10px;
            border-radius: 10px;
            -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
            -moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
            box-shadow: 0 1px 2px rgba(0,0,0,.05);
        }
        body {      background: #FFF;
        }
        #input {
            width: 280px;
            height: 25px;
            font-size: 18px;
            line-height: 1.33;
               background-color: rgba(255, 255, 255, 0.48);
            border:0; 
            padding: 5px;
            outline:none;

        }
        #inputform,#radiogroup{text-align: center;
        }
        #submit {
            height: 30px;
            width: 80px;
            font-weight: 500;
            font-size: 16px;
            font-family: "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
            border-radius: 10px;
            background-color: rgba(255, 87, 172, 0.55);
            color: #FFF;
            border:0;
            padding: 5px 0px;
            outline:none;
        }

        #submit:hover {
            background-color: #4cb0f9;
            cursor:pointer;
        }
        .mgr {
          position: relative;
          width: 16px;
          height: 16px;
          background-clip: border-box;
          -webkit-appearance: none;
             -moz-appearance: none;
                  appearance: none;
          margin: -0.15px 0.6px 0 0;
          vertical-align: text-bottom;
          border-radius: 50%;
          border: 1px solid #d7d7d7;
        }
        .mgr:disabled {
          opacity: 0.65;
        }
        .mgr:before {
          content: '';
          display: block;
          height: 0px;
          width: 0px;
          -webkit-transition: width 0.25s, height 0.25s;
          transition: width 0.25s, height 0.25s;
        }
        .mgr:checked:before {
          height: 8px;
          width: 8px;
          border-radius: 50%;
          margin: 3px 0 0 3px;
        }
        .mgr:focus {
          outline: none;
          box-shadow: inset 0 1px 1px rgba(255,255,255,0.075), 0 0px 2px #38a7ff;
        }
        .mgr:checked {
          border: 1px solid #337ab7;
        }
        .mgr:checked:before {
          background-color: #337ab7;
        }

textarea{
  width:100%;
  height:500px;
  max-width:100%;
  max-height:500px;
}
    </style>
</head>
<body><div style="    color: #000;
    background-color: rgba(255, 255, 255, 0.33);
    border-radius: 5px;
    padding: 8px;
    width: 80%;
    margin: 0 auto;">
<form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
<div id="title"><h1>网易云音乐id解析</h1></div>
<div id="radiogroup">
id类型:
<input type="radio" name="type" class="mgr" value="collect"  <?php if ($_POST['type']=="collect" || $_POST['type'] == null) echo "checked";?>>歌单
<input type="radio" name="type" class="mgr" value="album" <?php if ($_POST['type']=="album") echo "checked";?>>专辑
<input type="radio" name="type" class="mgr" value="artist" <?php if ($_POST['type']=="artist") echo "checked";?>>艺人
<input type="radio" name="type" class="mgr" value="song" <?php if ($_POST['type']=="song") echo "checked";?>>单曲
</div>
<br>
<div id="inputform">
id输入:&nbsp;<input type="text" id="input" placeholder="多个id用英文,分隔开" name="id" value="<?php echo $_POST["id"] ?>">
<input type="submit" id="submit" value="提交">
</div>
</form> 
<br>
<div style="border:5px dotted #9bcd9b;"></div>

<?php 
    /**
     * 从netease中获取歌曲信息
     * 
     * @link https://github.com/webjyh/WP-Player/blob/master/include/player.php
     * @param unknown $id 
     * @param unknown $type 获取的id的类型，song:歌曲,album:专辑,artist:艺人,collect:歌单
     */
	function get_mp3_url($id,$name){
        $url = "http://music.163.com/api/album/$id?id=$id";
		$key = 'album';
        if (!function_exists('curl_init')) return false;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Cookie: appver=2.0.2' ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, 'http://music.163.com/;');
        $cexecute = curl_exec($ch);
        curl_close($ch);
        if ( $cexecute ) {
            $result = json_decode($cexecute, true);
            if ( $result['code'] == 200 && $result[$key] ){
				$data = $result[$key]['songs'];
                //列表
                $list = array();
                foreach ( $data as $keys => $data ){
					if ($data['name'] == $name)
					{
						return str_replace('http://m', '//p', $data['mp3Url']);
					}
                }
            }
        } else {
            $return = array('status' =>  false, 'message' =>  '非法请求');
        }
        return $return;
    }
    function get_netease_music($id, $type = 'song'){
        $return = false;
        switch ( $type ) {
            case 'song': $url = "http://music.163.com/api/song/detail/?ids=[$id]"; $key = 'songs'; break;
            case 'album': $url = "http://music.163.com/api/album/$id?id=$id"; $key = 'album'; break;
            case 'artist': $url = "http://music.163.com/api/artist/$id?id=$id"; $key = 'artist'; break;
            case 'collect': $url = "http://music.163.com/api/playlist/detail?id=$id"; $key = 'result'; break;
            default: $url = "http://music.163.com/api/song/detail/?ids=[$id]"; $key = 'songs';
        }

        if (!function_exists('curl_init')) return false;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Cookie: appver=2.0.2' ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_REFERER, 'http://music.163.com/;');
        $cexecute = curl_exec($ch);
        curl_close($ch);

        if ( $cexecute ) {
            $result = json_decode($cexecute, true);
            if ( $result['code'] == 200 && $result[$key] ){

                switch ( $key ){
                    case 'songs' : $data = $result[$key]; break;
                    case 'album' : $data = $result[$key]['songs']; break;
                    case 'artist' : $data = $result['hotSongs']; break;
                    case 'result' : $data = $result[$key]['tracks']; break;
                    default : $data = $result[$key]; break;
                }

                //列表
                $list = array();
                foreach ( $data as $keys => $data ){
					if ($type == 'album' || $type == 'artist')
					{$mp3Url = str_replace('http://m', '//p', $data['mp3Url']);$n=666;}
					else
					{$mp3Url = get_mp3_url($data['album']['id'],$data['name']);}
                    $list[$data['id']] = array(
                            'title' => $data['name'],
                            'artist' => $data['artists'][0]['name'],
                            'location' => $mp3Url,
                      
                    );
                }
                //修复一次添加多个id的乱序问题
                if ($type = 'song' && strpos($id, ',')) {
                    $ids = explode(',', $id);
                    $r = array();
                    foreach ($ids as $v) {
                        if (!empty($list[$v])) {
                            $r[] = $list[$v];
                        }
                    }
                    $list = $r;
                }
                //最终播放列表
                $return = $list;
            }
        } else {
            $return = array('status' =>  false, 'message' =>  '非法请求');
        }
        return $return;
    }


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = $_POST["id"];
    $type = $_POST["type"];
    $resultList = explode(",", $input);
    $result="";
    foreach ($resultList as $key => $value) {
        $musicList = get_netease_music($value,$type);
        foreach($musicList as $x=>$x_value) {
            $result .= "{";
            foreach ($x_value as $key => $value) {
                if ($key == 'location') {
                    $key = 'mp3';
                }
                if ($key == 'pic') {
                    $key = 'cover';
                }
                if (strpos($value, '"') !== false) {
                    $value = addcslashes($value, '"');
                }
                $result .= "$key:\"". $value."\",";
            }
            $result .= "},<br>";
        }
    }
    echo "<br><div style='word-wrap: break-word;word-break: normal; '>".$result."</div>";
    //echo "<textarea id='text'".$result."</textarea>";

}
?>
</div>

</body>
</html>
