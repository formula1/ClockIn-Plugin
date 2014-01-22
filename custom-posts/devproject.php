<?php

require_once dirname(__FILE__)."/../utils.php";

class dev_project{

	public static function findOrCreate($proj, $user_id){
		global $cl_utils;
		$result = json_decode($cl_utils::getURL('https://api.github.com/repos/'.$proj, $user_id));
		if($result == array()) throw new Exception("failure: doesn't exsist");

		$proja = explode("/",$proj);
		
		if(($project = get_page_by_title( $result->name, "OBJECT", "clockin_project" )) == null){
			try{
			$readme = $cl_utils::getURL('https://api.github.com/repos/'.$proj.'/readme', $user_id, array('Accept'=> 'application/vnd.github.VERSION.raw'));
			}catch(Exception $e){
			$readme = '';
			}
			
				$wl = array("b", "blockquote", "br", "center", "cite", "code", "col", "colgroup", "div", "dd", "dl", "dt", "em", "font","h1", "h2", 
			"h3", "h4", "h5", "h6", "hr", "i", "img", "li", "ol", "p", "pre", "q", "small", "span", "strike", "strong", "sub", "sup", "table", 
			"tbody", "td", "tfoot", "th", "thread", "tr", "u", "ul"
			);
			$c = $cl_utils::strip_tags_content(base64_decode ($readme->content), "<script><iframe><frame><form>", true);

			if($c == '')$c = "The doesn't appear to be a readme file here";
			$post = array(
			  'post_content'   => $c, // The full text of the post.
			  'post_name'      => implode("-", $proja), // The name (slug) for your post
			  'post_title'     => $result->name, // The title of your post.
			  'post_status'    => 'publish',
			  'post_type'      => "clockin_project", // Default 'post'.
			  'post_excerpt'   => $result->description // For all your post excerpt needs.
			);
			$id = wp_insert_post($post, $e);
			add_post_meta($id, "github-url", $result->html_url, true);
			add_post_meta($id, "full_name", $proj, true);
		}else{ $id = $project->ID;}
	
		return $id;
	
	}

}



?>