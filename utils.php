<?php


class clock_in_utils{

	static public function getUrl($url, $token, $headers = array()){
		$h = array();
		foreach($headers as $k=>$v){
			array_push($h, $k.": ".$v);
		}
		array_push($h, 'User-Agent: Clock-In-Prep');
		
		if(strpos($url, "?") !== false) $url = $url."&access_token=".$token;
		else $url = $url."?access_token=".$token;

	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		// Set so curl_exec returns the result instead of outputting it.
		curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		// Get the response and close the channel.
		$response = curl_exec($ch);
		$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		if($http_status != 200 && $http_status != 301){
			throw new Exception($http_status);
		}
		return $response;
		
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

$cl_utils = new clock_in_utils();