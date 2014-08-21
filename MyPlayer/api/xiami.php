<?php
/*
 * 虾米音乐
 */
class XIAMI extends API{
    protected function LoadRemote( ) {
        if( TryGetParam( 'id', $id ) ) {
            $xml = GetUrlContent( "http://www.xiami.com/song/playlist/id/{$id}" );
            $xml = simplexml_load_string($xml);
            $this->data = array( );
            foreach( $xml->trackList->track as $index => $track )
            {
                $value = array();
                $location = (string)$track->location;
                $value['title'] = (string)$track->title;
                $value['url'] = $this->GetUrl($location);
                $value['artist'] = (string)$track->artist;
                $value['lyric_url'] = (string)$track->lyric_url;
                array_push($this->data, $value);
                //print_r($track);
            }
        }
    }
    function GetUrl($location){
        $num = substr( $location, 0, 1 );
        $inp = substr( $location, 1 );
        $iLe = strlen( $inp ) % $num;
        $quo = ( strlen( $inp ) - $iLe ) / $num;
        
        $a = 0;
        $ret = '';
        $arr = array();
        for ( $i = 0; $i < $num; $i ++ ) {
            $arr[$i] = ( $iLe > $i ? 1 : 0 ) + $quo;
        }
        for ( $i = 0; $i < $arr[1] ; $i ++) {
            $a = 0;
            for ( $j = 0; $j < $num; $j ++) {
                $ret .= substr( $inp, $a + $i, 1 );
                $a += $arr[$j];
            }
        }
        
        $location = rawurldecode( $ret );
        $location = str_replace( '^', '0', $location );
        $location = str_replace( '+', ' ', $location );
        $location = preg_replace( '/00-0-nul(.*)/', '00-0-null', $location );
        return $location;
    }
}
