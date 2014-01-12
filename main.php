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
add_action( 'widgets_init', function(){
	include plugin_dir_path( __FILE__)."/widget.php";
	register_widget( 'My_Widget' );
});
register_activation_hook( plugin_dir_path(__FILE__)."/main.php", "activate_clockin" );
register_uninstall_hook(plugin_dir_path(__FILE__)."/main.php", "uninstall_clockin");

function activate_clockin(){

	$tablename = $wpdb->prefix ."Clock_ins";
	global $wpdb;
	$sql = "CREATE TABLE ".$tablename." (
	  starttime TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
	  duration INT DEFAULT 0 NOT NULL,
	  devuser TINYTEXT NOT NULL,
	  project TINYTEXT NOT NULL
	 );";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}


function uninstall_clockin(){

$mycustomposts = get_pages( array( 'post_type' => 'clockin_project', 'number' => 500) );
   foreach( $mycustomposts as $mypost ) {
     // Delete's each post.
     wp_delete_post( $mypost->ID, true);
    // Set to False if you want to send them to Trash.
   }

	$sql = "DROP TABLE Clock_ins";
	global $wpdb;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

}



function clockin_register_js(){
	wp_register_script ("clock_in", plugins_url("clockin.js", __FILE__), array("jquery"),false,true);
	wp_register_script("clock_in_proj", plugins_url("clockin-proj.js",__FILE__), array("jquery"),false, true);
}

function initialize_clockin() {
//	delete_user_meta(get_current_user_id(), "clockin");
	register_post_type( 'clockin_project',
		array(
			'labels' => array(
				'name' => __( 'Github Projects' ),
				'singular_name' => __( 'Github Project' )
			),
		'description' => 'Associated to Github Project',
		'public' => true,
		'has_archive' => true,
		'show_in_menu' => true,
		'supports' => array('title','excerpt', 'editor')

		)
	);

	add_shortcode( 'clock_in', 'clock_in_setup' );


}

function clock_in(){
	if ( !wp_verify_nonce( $_GET['nonce'], "clock_in")) {
		exit("failure: No naughty business please");
	}
	$user_id = get_current_user_id();
	if($user_id === 0) die("failure: need to login");
	$meta = get_user_meta($user_id, "clockin");
	if($meta == array()) die("failure: this user needs to verify");
	if($meta[0]["clocked"]) die("failure: already clocked in");
	$proj = $_GET["proj"];
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/'.$proj.'?access_token='.$meta[0]["token"]);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'User-Agent: Clock-In-Prep'
	));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$result = json_decode(curl_exec($ch));
	if($result == array()) die("failure: doesn't exsist");

	$proja = explode("/",$proj);
	

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/repos/'.$proj.'/readme'.'?access_token='.$meta[0]["token"]);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Accept: application/vnd.github.VERSION.raw'
		,'User-Agent: Clock-In-Prep'
    ));
	
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	$readme = json_decode(curl_exec($ch));
	$wl = array("b", "blockquote", "br", "center", "cite", "code", "col", "colgroup", "div", "dd", "dl", "dt", "em", "font","h1", "h2", 
"h3", "h4", "h5", "h6", "hr", "i", "img", "li", "ol", "p", "pre", "q", "small", "span", "strike", "strong", "sub", "sup", "table", 
"tbody", "td", "tfoot", "th", "thread", "tr", "u", "ul"
);
	$c = strip_tags_content(base64_decode ($readme->content), "<script><iframe><frame><form>", true);
	
	if(($project = get_page_by_title( $proj, "OBJECT", "clockin_project" )) == null){
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
	}else{ $id = $project->ID;}
	
	global $wpdb;
	
	$wpdb->insert( "Clock_ins", array( 'project' => $id, 'devuser' => $user_id ));

	
	$meta[0]["clocked"] = true;
	update_user_meta($user_id, "clockin", $meta[0] );

	die(do_shortcode("[clock_in]"));

}

function clock_out(){
	if ( !wp_verify_nonce( $_REQUEST['nonce'], "clock_in")) {
		exit("failure: No naughty business please");
	}
	$user_id = get_current_user_id();
	if($user_id === 0) die("failure: need to login");
	$meta = get_user_meta($user_id, "clockin");
	if($meta == array()) die("failure: this user needs to verify");
	if(!$meta[0]["clocked"]) die("failure: already clocked out");

	global $wpdb;
	
	$ci = $wpdb->get_var( 
		"
		SELECT TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP,starttime)) 
		FROM clock_ins
		WHERE duration = 0 
		AND devuser = ".$user_id
	);
	
	
	$wpdb->update( "Clock_ins", array("duration"=>$ci), array("duration" => 0, "devuser" => $user_id));
	
	$meta[0]["clocked"] = false;
	update_user_meta($user_id, "clockin", $meta[0] );

	die(do_shortcode("[clock_in]"));	
}

function clock_in_setup( $atts, $content=null) {
	$current_user = wp_get_current_user();
	$message;
	$href;
		
	if (  !is_user_logged_in() || !($current_user instanceof WP_User) ){
		$message = "Please Login First";
		$href = wp_login_url( get_permalink() );
	}else if(($meta = get_user_meta($current_user->ID, "clockin")) == array()){
		$json = json_decode(file_get_contents(plugin_dir_path( __FILE__ )."/secret.json"));
		$cid = $json->cid;
		$redirect_uri = plugins_url("auth.php", __FILE__);
		$state = "clock-in_plugin".$current_user->ID;
	
		$href = "https://github.com/login/oauth/authorize";
		$href .= "?client_id=".$cid;
		$href .= "&redirect_uri=".urlencode($redirect_uri);
		$href .= "&state=".$state;
		
		$message = "Authorize our plugin";
	}else if(isset($atts["cur_project"])){
		$nonce = wp_create_nonce("clock_in");
		$href = admin_url('admin-ajax.php?action=clock_in&proj='.$atts["curproject"].'&nonce='.$nonce);
		$message = "Clock in!";
	$type = "ajax";
	}else if($meta[0]["clocked"] == true){
		$nonce = wp_create_nonce("clock_in");
		$href = admin_url('admin-ajax.php?action=clock_out&nonce='.$nonce);
		$message = "Clock out!";
	$type = "ajax";
	}else{
		$href = plugins_url("clocked.php", __FILE__)."?action=in";
		$nonce = wp_create_nonce("clock_in");
		wp_enqueue_script ('clock_in');

		wp_enqueue_script ('clock_in_proj');
		wp_localize_script('clock_in_proj', 'clock_in_vars', array("github_user"=>$meta[0]["github"], "token"=>$meta[0]["token"],"clockin_uri"=>admin_url('admin-ajax.php?action=clock_in&nonce='.$nonce)));

		ob_start();
?>
		
		<div class="clockin-wrap">
			Choose a project to Clock Into
			<a href="#" style="display:block;background-color:#000;">Up a Page</a>
			<div class="clockin_projects" style="display:inline-block;height:128px;overflow-y:scroll;width:100%">
			</div>
			<a href="#" style="display:block;background-color:#000;">Down a Page</a>
		</div>
		<script type="text/javascript">
			jQuery(function($){clock_in_proj($)});
		</script>
<?php
		return ob_get_clean();
	}
	ob_start();
	?>
	<div class="clockin-wrap">
	<a class="clockin_anchor" href="<?php echo $href; ?>" ><?php echo $message ?></a>
	</div>
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