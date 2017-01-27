<?php
class HermitJson{

	public function __construct(){
	}

	public function song($song_id){

		$response = $this->xiami_http(0, $song_id);

		if ($response && $response["state"] == 0 && $response['data']) {
			$result = $response["data"]["song"];

			$song = array(
				"song_id"     => $result["song_id"],
				"song_title"  => $result["song_name"],
				"song_author" => $result["singers"],
				"song_src"    => $result["listen_file"],
				"song_cover"  => $result['logo']
			);

			return $song;
		}

		return false;
	}

	public function song_list($song_list){

		if( !$song_list ) return false;

		$songs_array = explode(",", $song_list);
		$songs_array = array_unique($songs_array);

		if( !empty($songs_array) ){
			$result = array();
			foreach( $songs_array as $song_id ){
				$result['songs'][]  = $this->song($song_id);
			}

			return $result;
		}

	    return false;
	}

	public function album($album_id){

		$response = $this->xiami_http(1, $album_id);

		if ($response && $response["state"] == 0 && $response["data"]) {
			$result = $response["data"];
			$count  = $result['song_count'];

			if ($count < 1) return false;

			$album = array(
				"album_id"     => $album_id,
				"album_title"  => $result['album_name'],
				"album_author" => $result['artist_name'],
				"album_cover"  => $result['album_logo'],
				"album_count"  => $count,
				"album_type"   => "albums",
			);

			foreach ($result['songs'] as $key => $val) {
				$song_id = $val['song_id'];
				$album["songs"][] = $this->song($song_id);
			}

			return $album;
		}

		return false;
	}

	public function collect($collect_id){

		$response = $this->xiami_http(2, $collect_id);

		if ($response && $response["state"] == 0 && $response["data"]) {
			$result = $response["data"];
			$count  = $result['songs_count'];

			if ($count < 1) return false;

			$collect = array(
				"collect_id"     => $collect_id,
				"collect_title"  => $result['collect_name'],
				"collect_author" => $result['user_name'],
				"collect_cover"  => $result['logo'],
				"collect_type"   => "collects",
				"collect_count"  => $count
			);

			foreach ($result['songs'] as $key => $value) {
				$collect["songs"][] = array(
					"song_id"     => $value["song_id"],
					"song_title"  => $value["song_name"],
					"song_length" => $value["length"],
					"song_src"    => $value["listen_file"],
					"song_author" => $value["singers"],
					"song_cover"  => $value['album_logo']
				);
			}

			return $collect;
		}

		return false;
	}

	private function xiami_http($type, $id)
	{

		switch($type){
			case 0:
				$url = "http://api.xiami.com/web?v=2.0&app_key=1&id={$id}&r=song/detail";
				break;

			case 1:
				$url = "http://api.xiami.com/web?v=2.0&app_key=1&id={$id}&r=album/detail";
				break;

			case 2:
				$url = "http://api.xiami.com/web?v=2.0&app_key=1&id={$id}&type=collectId&r=collect/detail";
				break;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_REFERER, "http://m.xiami.com/");
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (iPhone; CPU iPhone OS 7_1_2 like Mac OS X) AppleWebKit/537.51.2 (KHTML, like Gecko) Version/7.0 Mobile/11D257 Safari/9537.53');
		curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$cexecute = curl_exec($ch);
		@curl_close($ch);

		if ($cexecute) {
			$result = json_decode($cexecute, TRUE);
			return $result;
		} else {
			return false;
		}
	}

}