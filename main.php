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

	add_option("clock-in", array("calender-id"=>null));

}
if( is_admin() )
    $clockin_admin = new clock_in_admin();

class clock_in_admin {
	public $options;

	public function __construct(){
		add_action( 'admin_menu', array( $this, 'add_to_menu' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
	}
	
	public function add_to_menu(){
		add_utility_page( "Clock In Settings", "Clockin", "administrator", "clock-in-admin", array($this, "admin_page"));
	}
	public function admin_page(){
		$this->options = get_option( 'clock-in' );
		include "admin_page.php";
	}
	public function page_init(){
		register_setting('clock-in', 'calender-id', array( $this, 'sanitize' ) );

		add_settings_section('clock-in-cal', 'Google Calender Setting', array( $this, 'print_section_info' ),'clock-in-admin');

		add_settings_field('calender-id','Calander ID',array( $this, 'calander_id_input' ),'clock-in-admin','clock-in-cal');
	}
	
	 public function print_section_info()
    {
        echo 'Enter your settings below:';
    }
	
	public function sanitize( $input ){
		$new_input = array();
		if( isset( $input['calander-id'] ) ){
			//need to check if the calender exists and we can't view and edit it
			//if we can't edit it, we need to ask for permission
		}

		return $new_input;
	}
	
	public function calander_id_input()
	{
		printf(
			'<input type="text" id="id_number" name="my_option_name[id_number]" value="%s" />',
			(isset( $this->options['calender-id'] ) && $this->options['calender-id'] != null) ? esc_attr( $this->options['calender-id']) : ''
		);
	}
	

}

 
 ?>