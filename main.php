<?php
/**
 * Plugin Name: Clock In
 * Plugin URI: http://samtobia.com/clock-in
 * Description: Gives you an interface to allow users to clockin to their github projects and keep track of time
 * Version: 0.1
 * Author: Sam Tobia
 * Author URI: http://samtobia.com
 * License: GPL2
 */
add_action( 'wp', 'clockin_register_js' );
add_action( 'init', 'initialize_clockin' );
add_action("wp_ajax_clock_in", "clock_in");
add_action("wp_ajax_clock_out", "clock_out");


function clockin_register_js(){
	wp_register_script ("clock_in", plugins_url("clockin.js", __FILE__), array("jquery"),false,true);
	wp_enqueue_script("clock_in_proj", plugins_url("clockin-proj.js",__FILE__), array("jquery"),false, true);
}

function initialize_clockin() {
	register_post_type( 'clockin_project',
		array(
			'labels' => array(
				'name' => __( 'Github Projects' ),
				'singular_name' => __( 'Github Project' )
			),
		'description' => 'Associated to Github Project',
		'public' => true,
		'has_archive' => true,
		'show_in_menu' => false,
		'supports' => array('title','excerpt')

		)
	);
	
	global $wpdb;
	$sql = "CREATE TABLE Clock_ins (
	  time datetime DEFAULT NOW() NOT NULL,
	  duration int DEFAULT 0 NOT NULL,
	  user tinytext NOT NULL,
	  project tinytext NOT NULL,
	);";

	global $wpdb;

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	delete_option("clock-in");
	add_shortcode( 'clock_in', 'clock_in_setup' );


}

function clock_in(){
	if ( !wp_verify_nonce( $_REQUEST['nonce'], "clock_in")) {
		exit("No naughty business please");
	}
	$user_id = get_current_user_id();
	if($user_id === 0) die("need to login");
	$meta = get_user_meta($user_id, "clockin");
	if($meta == array()) die("this user needs to verify");
	if($meta["clocked"]) die("already clocked in");
	$proj = $_REQUEST["proj"];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/'.$proj);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$result = json_decode(curl_exec($ch));
	if($result == array()) die("doesn't exsist");

	$proja = explode("/",$proj);
	

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/'.$proj.'/readme');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Accept: application/vnd.github.VERSION.raw'
    ));
	
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$readme = json_decode(curl_exec($ch));
	$wl = array("b", "blockquote", "br", "center", "cite", "code", "col", "colgroup", "div", "dd", "dl", "dt", "em", "font","h1", "h2", 
"h3", "h4", "h5", "h6", "hr", "i", "img", "li", "ol", "p", "pre", "q", "small", "span", "strike", "strong", "sub", "sup", "table", 
"tbody", "td", "tfoot", "th", "thread", "tr", "u", "ul"
);
	$c = strip_tags_content(base64_decode ($readme->content), "<script><iframe><frame><form>", true);
	
	if(($proj = get_page_by_title( $proj, "OBJECT", "clockin_project" )) == null){
		$post = array(
		  'post_content'   => $c, // The full text of the post.
		  'post_name'      => implode("-", $proja), // The name (slug) for your post
		  'post_title'     => $proj, // The title of your post.
		  'post_status'    => 'publish',
		  'post_type'      => "clockin_project", // Default 'post'.
		  'post_excerpt'   => $result->description, // For all your post excerpt needs.
		  'comment_status' => 'closed' // Default is the option 'default_comment_status', or 'closed'.
		);
		$id = wp_insert_post($post, $e);
		add_post_meta($id, "github-url", $result->html_url, true);
	}
	
	global $wpdb;
	
	$wpdb->insert( "Clock_ins", array( 'project' => $id, 'user' => $user_id ));

	
	$meta["clocked"] = true;
	update_user_meta($user_id, $meta_key, $meta_value, $prev_value );

	die();

}

function clock_out(){
	if ( !wp_verify_nonce( $_REQUEST['nonce'], "clock_in")) {
		exit("No naughty business please");
	}
	$user_id = get_current_user_id();
	if($user_id === 0) die("need to login");
	$meta = get_user_meta($user_id, "clockin");
	if($meta == array()) die("this user needs to verify");
	if(!$meta["clocked"]) die("already clocked out");

	global $wpdb;
	
	$ci = $wpdb->get_results( 
		"
		SELECT time 
		FROM Clock_ins
		WHERE duration = 0 
		AND user = ".$user_id
	);
	
	if(count($ci) > 1) die("we have a problem");
	$ci = $ci[0];
	
	$wpdb->update( "Clock_ins", array("duration"=>"TIME_TO_SEC(TIMEDIFF(NOW(),start))"), array("duration" => 0, "user" => $user_id));
	
	$meta["clocked"] = false;
	update_user_meta($user_id, $meta_key, $meta_value, $prev_value );

	die();	
}

function clock_in_setup( $atts, $content=null) {
	$current_user = wp_get_current_user();
	$message;
	$href;
	$type = "ajax";
	
	if ( !($current_user instanceof WP_User) ){
		$message = "Please Login First";
		$href = wp_login_url( get_permalink() );
		$type="self";
	}else if(($meta = get_user_meta($current_user->ID, "clockin")) == array()){
		$json = json_decode(file_get_contents("secret.json"));
		$cid = $json["cid"];
		$redirect_uri = plugins_url("auth.php", __FILE__);
		$state = "clock-in_plugin".$current_user->ID;
	
		$href = "https://github.com/login/oauth/authorize";
		$href .= "?client_id=".$cid;
		$href .= "&redirect_uri=".$redirect_uri;
		$href .= "&state=".$state;
		
		$message = "Authorize our plugin";
		$type="blank";
	}else if(isset($atts["cur_project"])){
		$nonce = wp_create_nonce("clock_in");
		$href = admin_url('admin-ajax.php?action=clock_in&proj='.$atts["curproject"].'&nonce='.$nonce);
		$message = "Clock in!";
	}else if($meta["clocked"] == true){
		$nonce = wp_create_nonce("clock_in");
		$href = admin_url('admin-ajax.php?action=clock_out&nonce='.$nonce);
		$message = "Clock out!";
	}else{
		$href = plugins_url("clocked.php", __FILE__)."?action=in";
		$nonce = wp_create_nonce("clock_in");

		ob_start();
?>
		Choose a project to Clock Into
		<div class="clockin-wrap">
			<a style="display:inline-block;height:144px;width:64px;background-color:#000;"></a>
			<div class="clockin_projects" style="display:inline-block;height:144px;width:144px;">
			</div>
			<a style="display:inline-block;height:144px;width:64px;background-color:#000;">
			</a>
		</div>
<?php
		wp_enqueue_script ('clock_in_proj');
		wp_localize_script('clock_in_proj', 'clock_in_vars', array("github_user"=>$meta["github"], "clockin_uri"=>admin_url('admin-ajax.php?action=clock_in&&nonce='.$nonce)));
		return ob_get_clean();
	}
	ob_start();
	?>
	<div class="clockin-wrap">
	<a class="clockin_anchor" href=<?php echo $href; echo ($type != "ajax")?" target=".$type.'"':''; ?> ><?php echo $message ?></a>
	<?php 
	if($type == "ajax"){
		wp_enqueue_script ('clock_in');
	}
	return ob_get_clean();
}


function strip_tags_content($text, $tags = '', $invert = FALSE) { 

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



 
 ?>