<?php
function MakeDir( $path ) {
    return is_writeable( $path ) || mkdir( $path, 0777, true );
}
function TryGetParam( $key, &$value ){
    if( isset( $_GET[$key] ) && $_GET[$key] ){
        $value = $_GET[$key];
        return true;
    }
    return false;
}
function GetUrlContent( $url ){
    $curl = curl_init( $url );
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION,1);
    curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_0 like Mac OS X; en-us) AppleWebKit/528.18 (KHTML, like Gecko) Version/4.0 Mobile/7A341 Safari/528.16');
    $res=curl_exec($curl);
    curl_close($curl);
    return $res;
}
abstract class API {
    protected $service;
    protected $key;
    protected $data;
    protected $cachetime;
    public function __construct( ){  
        $this->cachetime = 86400;
        $this->service = strtolower( get_class( $this ) );
        $this->data = null;
        $this->LoadData( );
    }
    protected function LoadData( ) {
        $this->LoadRemote( );
    }
    public function GetData( $callback = null ) {
        $data = json_encode( $this->data );
        if( is_null( $callback ) ) {
            return $data;
        } else {
            return "{$callback}({$data})";
        }
    }
    protected abstract function LoadRemote( );
}
if( TryGetParam( 'service', $service ) ) {
    $service = strtoupper( $service );
    $apifile = "api/{$service}.php";
    if( file_exists( $apifile ) ){
        include( $apifile );
        $api = new $service( );
        if( TryGetParam( 'callback', $callback ) ) {
            header( 'Content-type:text/javascript' );
            echo $api->GetData( $callback );
        } else {
            header( 'Content-type:application/json' );
            echo $api->GetData( );
        }
    }
}

