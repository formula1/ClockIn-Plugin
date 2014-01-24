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
register_deactivation_hook(plugin_dir_path(__FILE__)."/main.php", "deactivate_clockin");

function cl_delete_user($id){

}
function deactivate_clockin(){
	$sql = "DROP TABLE Clock_ins";
	global $wpdb;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}
function activate_clockin(){

	$tablename = $wpdb->prefix ."Clock_ins";
	global $wpdb;
	$sql = "CREATE TABLE ".$tablename." (
	  starttime INT NOT NULL,
	  stoptime INT DEFAULT 0 NOT NULL,
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
	global $cpt_onomies_manager;
	
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
		'supports' => array('title','excerpt', 'editor'),

		)
	);
	/*
	if ( $cpt_onomies_manager ) {

		$cpt_onomies_manager->register_cpt_onomy(
			'clockin_project',
			'clockin_object',
			array(
			'labels' => array(
				'name' => __( 'Developer Projects' ),
				'singular_name' => __( 'Developer Project' )
			),
			'description' => 'Developer project',
			'public'	=> true,
			)
		);
	}

	register_post_type( 'clockin_object',
		array(
			'labels' => array(
				'name' => __( 'Clock Ins' ),
				'singular_name' => __( 'Clock In' )
			),
		'description' => 'Storing the times Developers start and stop working',
		'public' => true,
		'has_archive' => true,
		'show_in_menu' => false,
		'supports' => array('title','excerpt', 'editor', 'author', 'custom-feilds')

		)
	);
*/
	
	add_shortcode( 'clock_in', 'clock_in_shortcode' );


}

function clock_in(){
	global $cl_utils;
	require_once dirname(__file__)."/custom-posts/devproject.php";
	if ( !wp_verify_nonce( $_GET['nonce'], "clock_in")) {
		exit("failure: No naughty business please");
	}
	$user_id = get_current_user_id();
	if($user_id === 0) die("failure: need to login");
	$meta = get_user_meta($user_id, "clockin");
	if($meta == array()) die("failure: this user needs to verify");
	if($meta[0]["clocked"] !== false) die("failure: already clocked in");
	$proj = urldecode($_GET["proj"]);

	$id = dev_project::findOrCreate($proj, $user_id);
	
	global $wpdb;
	
	$wpdb->insert( "Clock_ins", array( 'project' => $id, 'devuser' => $user_id, 'starttime'=>time() ));

	
	$meta[0]["clocked"] = true;
	update_user_meta($user_id, "clockin", $meta[0] );

	die(do_shortcode("[clock_in]"));

}

function clock_out(){
	global $cl_utils;
	if ( !wp_verify_nonce( $_REQUEST['nonce'], "clock_in")) {
		exit("failure: No naughty business please");
	}
	$user_id = get_current_user_id();
	if($user_id === 0) die("failure: need to login");
	$meta = get_user_meta($user_id, "clockin");
	if($meta == array()) die("failure: this user needs to verify");
	if($meta[0]["clocked"] === false) die("failure: already clocked out");

	
	global $wpdb;
	
	$sql = "UPDATE clock_ins 
		SET stoptime=".time()."
		WHERE stoptime=0 AND devuser=".$user_id;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	
	
//	$wpdb->update( "clock_ins", array("stoptime"=>"CURRENT_TIMESTAMP", "duration"=>"TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP,starttime))"), array("duration" => 0, "devuser" => $user_id));

	$meta[0]["clocked"] = false;
	update_user_meta($user_id, "clockin", $meta[0] );

	die(do_shortcode("[clock_in]"));	
}

function getUsersProjects(){
	require plugin_dir_path(__FILE__)."/ui/users_projects.php";
	try{
		$result = getCLProjects(urldecode($_GET["page"]));
		$head = $result[0];
	}catch(Exception $e){
		$json = json_decode(file_get_contents(dirname( __FILE__ )."/../secret.json"));
		$cid = $json->cid;
		$redirect_uri = plugins_url("auth.php", dirname(__FILE__));
		$state = "clock-in_plugin".$current_user->ID;

		$href = "https://github.com/login/oauth/authorize";
		$href .= "?client_id=".$cid;
		$href .= "&redirect_uri=".urlencode($redirect_uri);
		$href .= "&state=".$state;
		
		$message = "Authorize our plugin";
		die('<a href="'.$href.'" >'.$message.'</a>'.$e);
	}
	if(count($result[1]) == 0) die("no more");
	else doCLProjUI($result, $_GET['nonce']);
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