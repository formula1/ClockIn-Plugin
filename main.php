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

require_once(dirname(__FILE__)."/utils.php");
 
add_action( 'delete_user', 'cl_delete_user' );
add_action( 'wp', 'clockin_register_js' );
add_action( 'init', 'initialize_clockin' );
add_action("wp_ajax_clock_in", "clock_in");
add_action("wp_ajax_clock_out", "clock_out");
add_action("wp_ajax_cl_projects", "getUsersProjects");
add_action( 'widgets_init', function(){
	include plugin_dir_path( __FILE__)."/widget.php";
	register_widget( 'My_Widget' );
});
register_activation_hook( plugin_dir_path(__FILE__)."/main.php", "activate_clockin" );
register_uninstall_hook(plugin_dir_path(__FILE__)."/main.php", "uninstall_clockin");

function cl_delete_user($id){

}

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
	wp_register_script("jquery-xslt", plugins_url("ui/jquery.xslt.js",__FILE__), array("jquery"),false, true);
	wp_register_script ("clock_in", plugins_url("clockin.js", __FILE__), array("jquery"),false,true);
	wp_register_script("clock_in_proj", plugins_url("clockin-proj.js",__FILE__), array("jquery"),false, true);
	wp_register_script("waypoint", plugins_url("waypoints/waypoints.min.js",__FILE__), array("jquery"));
	wp_register_script("waypoint_infinite", plugins_url("waypoints/shortcuts/infinite-scroll/waypoints-infinite.min.js",__FILE__), array("waypoint"));
	wp_register_style( "clock_in_shortcode", plugins_url("ui/clockin.css",__FILE__));
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

	add_shortcode( 'clock_in', 'clock_in_shortcode' );


}

function clock_in(){
	global $cl_utils;
	if ( !wp_verify_nonce( $_GET['nonce'], "clock_in")) {
		exit("failure: No naughty business please");
	}
	$user_id = get_current_user_id();
	if($user_id === 0) die("failure: need to login");
	$meta = get_user_meta($user_id, "clockin");
	if($meta == array()) die("failure: this user needs to verify");
	if($meta[0]["clocked"]) die("failure: already clocked in");
	$proj = urldecode($_GET["proj"]);

	try{
		$result = $cl_utils::getURL('https://api.github.com/repos/'.$proj, $user_id);
	}catch(Exception $e){
		
	}
	if($result == array()) die("failure: doesn't exsist");

	$proja = explode("/",$proj);
	
	
	if(($project = get_page_by_title( $proj, "OBJECT", "clockin_project" )) == null){
	
		try{
			$readme = $cl_utils::getURL('https://api.github.com/repos/'.$proj.'/readme', $user_id, array('Accept'=> 'application/vnd.github.VERSION.raw'));
		}catch(Exception $e){
			
		}
			$wl = array("b", "blockquote", "br", "center", "cite", "code", "col", "colgroup", "div", "dd", "dl", "dt", "em", "font","h1", "h2", 
		"h3", "h4", "h5", "h6", "hr", "i", "img", "li", "ol", "p", "pre", "q", "small", "span", "strike", "strong", "sub", "sup", "table", 
		"tbody", "td", "tfoot", "th", "thread", "tr", "u", "ul"
		);
		$c = $cl_utils::strip_tags_content(base64_decode ($readme->content), "<script><iframe><frame><form>", true);

	
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

function getUsersProjects(){
	require plugin_dir_path(__FILE__)."/ui/users_projects.php";
	getCLProjects($_GET["page"], $_GET['nonce']);
	die();
}

function clock_in_shortcode( $atts, $content=null) {
	wp_enqueue_style("clock_in_shortcode");
	ob_start();
?>
		<aside id="clock_in_widget" class="widget">
<?php	
	require dirname(__FILE__)."/ui/shortcode.php";
?>
		</aside>
<?php
	return ob_get_clean();
}
 
 ?>