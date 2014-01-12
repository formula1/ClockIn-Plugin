<?php

class My_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct(
			'clock_in_widget', // Base ID
			__('Clock In', 'text_domain'), // Name
			array( 'description' => __( 'Easy to use Clock In widget to use easily in your website', 'text_domain' ), ) // Args
		);
	}

	public function widget( $args, $instance ) {
		// outputs the content of the widget
		echo do_shortcode("[clock_in]");
	}

	public function form( $instance ) {
		// outputs the options form on admin
	}

	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
	}
}
?>