<?php
/*
 * 新浪视频
 */
class SINA extends API{
    protected function LoadRemote( ) {
        if( TryGetParam( 'url', $url ) ) {
            $url = $url;
            $html = GetUrlContent( "http://dp.sina.cn/dpool/video/pad/play.php?url={$url}" );
            $count = preg_match_all( '/$SCOPE\[\'vid\'\]\s=\s"(\d+)";/s', $html, $matchs );
            if( $count ){
                $this->data = array( );
                $this->data['url'] = $matchs[1][0];
                $this->data['url'] = 'http://you.video.sina.com.cn/api/sinawebApi/outplayrefer.php/vid={$matchs[1][0]}/s.swf'
            }
        }
    }
}
