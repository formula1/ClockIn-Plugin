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
 
 
add_action( 'init', 'initialize_clockin' );
function initialize_clockin() {
	register_post_type( 'clockin_dev',
		array(
			'labels' => array(
				'name' => __( 'Developers' ),
				'singular_name' => __( 'Developer' )
			),
		'description' => 'The user object associated with the wordpress user. stores the guthub username as well',
		'public' => true,
		'has_archive' => true,
		'show_ui' => false,
		'show_in_menu' => false,
		'supports' => false
		)
	);
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
		'supports' => false

		)
	);

	add_option("Calander ID", null);

}
$clockin = new clock_in_plugin();
add_utility_page( "Clock In Settings", "Clockin", "admin", "clock-in", array($clockin, "admin_page"));


class clock_in_plugin {

	public function init(){
	
	}

	public function admin_page(){
		include "admin_page.php"
	}

}

 
 ?>