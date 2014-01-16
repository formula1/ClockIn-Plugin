<?php


function getCLProjects($page, $nonce = null){
	global $cl_utils;
	require_once dirname(__FILE__)."/../utils.php";

	if ( !wp_verify_nonce( $nonce, "clock_in")) {
		exit("failure: No naughty business please");
	}
	$user_id = get_current_user_id();
	if($user_id === 0) die("failure: need to login");
	$meta = get_user_meta($user_id, "clockin");
	if($meta == array()) die("failure: this user needs to verify");

	$href = 'https://api.github.com/users/'.$meta[0]["github"].'/repos?type=all&sort=updated&per_page=4&page='.$page;

	try{
		$result = $cl_utils::getURL($href, $meta[0]["token"]);
	}catch(Exception $e){
		echo $href;
		die($e);
	}

	if($nonce == null) 	$nonce = wp_create_nonce("clock_in");
	$result = json_decode($result);
	if(count($result) == 0) die();
	foreach($result as $item){ ?>
		<li>
			<h2 class='widget-title'><?php echo $item->name; ?></h2>
	<?php
		if(($project = get_page_by_title( $item->full_name, "OBJECT", "clockin_project" )) != null){ 
	?>
			<a href='<?php echo get_permalink($project->ID); ?>' >Project Page</a><br />
	<?php
		}
	?>
			<a target="_blank" href='<?php echo $item->html_url; ?>' >Github Page</a><br />
			<a class='clockin_anchor' href='<?php echo admin_url('admin-ajax.php?action=clock_in&nonce='.$nonce)."&proj=".$item->full_name; ?>'>Clock In</a>
		</li>
	<?php
	}
	$page += 1;
	?>
	<li>
		<a class="view_more bottom" href="<?php echo admin_url('admin-ajax.php').'?action=cl_projects&nonce='.$nonce.'&page='.$page; ?>">View More</a>
	</li>
<?php
}
?>