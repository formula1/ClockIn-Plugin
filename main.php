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
	flush_rewrite_rules();
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
		'rewrite' => array("slug"=>"projects")

		)
	);

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
	if($meta[0]["clocked"] !== false) die("failure: already clocked in");
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
		  'post_excerpt'   => $result->description // For all your post excerpt needs.
		);
		$id = wp_insert_post($post, $e);
		add_post_meta($id, "github-url", $result->html_url, true);
	}else{ $id = $project->ID;}

	$post = array(
		'post_content'	=> '',
		'post_title'	=> date("H:i")." started on ".$project->Title,
		'post_status'	=> 'draft',
		'post_type'		=> 'clockin_object',
		'post_excerpt'	=> '',
		'post_author'	=> $user_id
	);
	$cl_id = wp_insert_post($post, $e);
	add_post_meta($cl_id, "project", $id, true);
	add_post_meta($cl_id, "cl_start", date("Y-m-d H:i:s"), true);
	$meta[0]["clocked"] = $cl_id;
	

	
//	global $wpdb;
	
//	$wpdb->insert( "Clock_ins", array( 'project' => $id, 'devuser' => $user_id ));

	
//	$meta[0]["clocked"] = true;
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
	if($meta[0]["clocked"] === false) die("failure: already clocked out");

	$cl = get_post($meta[0]["clocked"], 'ARRAY_A');
	$proj = get_post(get_post_meta($meta[0]["clocked"], "project", true));
	$date = DateTime::createFromFormat("Y-m-d H:i:s", get_post_meta($meta[0]["clocked"], "cl_start", true));
	$dev = $meta[0]["github"];
	$url = "https://api.github.com/repos/".$proj->post_title."/commits";
	$url .= "?author=".$dev;
	$url .= "&since=".$date->format('Y-m-d').'T00:00:00Z';
	$date->modify("+1 days");
	$url .= "&until=".$date->format('Y-m-d').'T00:00:00Z';
	$date->modify("-1 days");
	try{
		$response = $cl_utils::getUrl($url, $user_id);
		$response = json_decode($response);
	}catch(Exception $e){
		$response = array();
	}
	
	
	
	ob_start();
?>
	<table class="clockin_table">
	<thead>
		<tr><th>Time</th><th>Type</th><th>Info</th></tr>
	</thead>
	<tbody>
		<tr>
			<td><time datetime="<?php echo $date->format(DATE_W3C); ?>"><?php echo $date->format("H:i"); ?></time></td>
			<td>Start time</td>
			<td><?php echo $project; ?></td>
		</tr>
<?php
	foreach($response as $commit){
		$time = DateTime::createFromFormat(DATE_W3C,$commit->commit->committer->date);
?>		
		<tr>
			<td><time datetime="<?php echo $time->format(DATE_W3C); ?>"><?php echo $time->format("H:i"); ?></time><td>
			<td>Commit</td>
			<td><?php echo $commit->message; ?></td>
		</tr>
<?php
		
	}
	$now = new DateTime('NOW');
?>
		<tr>
			<td><time datetime="<?php echo $now->format(DATE_W3C); ?>"><?php echo $now->format("H:i"); ?></time></td>
			<td>End time</td>
			<td><?php echo $project; ?></td>
		</tr>
	</tbody>
	</table>
<?php
	
	$cl["content"] = ob_get_clean();
	$cl["excerpt"] = "From ".$date->format("H:i")." to ".$now->format("H:i")." with ".count($response)." commits";
	
	wp_update_post($cl);
/*	

	global $wpdb;
	
	$ci = $wpdb->get_var( 
		"
		SELECT TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP,starttime)) 
		FROM clock_ins
		WHERE duration = 0 
		AND devuser = ".$user_id
	);
	
	
	$wpdb->update( "Clock_ins", array("duration"=>$ci), array("duration" => 0, "devuser" => $user_id));
*/	
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