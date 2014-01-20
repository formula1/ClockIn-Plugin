<?php


class clock_in_utils{

	static public function getUrl($url, $user_id, $headers = array(), $getheaders=false){
		if($user_id === 0) throw new Exception("failure: need to login");
		$meta = get_user_meta($user_id, "clockin");
		if($meta == array()) throw new Exception("failure: this user needs to verify");
		$token = $meta[0]["token"];

	
		$h = array();
		foreach($headers as $k=>$v){
			array_push($h, $k.": ".$v);
		}
		array_push($h, 'User-Agent: Clock-In-Prep');
		array_push($h, 'Accept: application/vnd.github.v3+json');
		array_push($h, 'Authorization: token '.$token);
	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		// Set so curl_exec returns the result instead of outputting it.
		curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_HEADER, true);


		// Get the response and close the channel.
		$response = curl_exec($ch);
		$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($response, 0, $header_size);
		$response = substr($response, $header_size);
		curl_close($ch);
				
		if($http_status == 401 || $http_status == 403 || $http_status == 404){
			delete_user_meta($user_id, "clockin");
			throw new Exception("failure: no access ".$url);
		}
		if($getheaders) return array($header, $response);
		else return $response;
		
	}
	
	static public function strip_tags_content($text, $tags = '', $invert = FALSE) { 
	  preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags); 
	  $tags = array_unique($tags[1]); 
		
	  if(is_array($tags) AND count($tags) > 0) { 
		if($invert == FALSE) { 
		  return preg_replace('@<(?!(?:'. implode('|', $tags) .')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
		} 
		else { 
		  return preg_replace('@<('. implode('|', $tags) .')\b.*?>.*?</\1>@si', '', $text); 
		} 
	  } 
	  elseif($invert == FALSE) { 
		return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text); 
	  } 
	  return $text; 
	}
}
global $cl_utils;
$cl_utils = new clock_in_utils();