<?php
/*
 * 爱奇艺
 */
class IQIYI extends API{
    protected function LoadRemote( ) {
        if( TryGetParam( 'id', $id ) ) {
            $html = GetUrlContent( "http://www.iqiyi.com/v_{$id}.html" );
            $count = preg_match_all( '/\"vid\":\"([0-9a-z]+)\"/s', $html, $matchs );
            if( $count ){
                $vid = $matchs[1][0];
                $this->data = array( );
                $this->data['url'] = "http://player.video.qiyi.com/{$vid}/0/0/v_{$id}.swf";
            }
        }
    }
}
