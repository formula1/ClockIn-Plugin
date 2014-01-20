<?php
start:
$current_user = wp_get_current_user();
$message;
$href;
	
if (  !is_user_logged_in() || !($current_user instanceof WP_User) ){
	$message = "Please Login First";
	$href = wp_login_url( get_permalink() );
?>
<div class="clockin-wrap">
<a class="clockin_anchor" href="<?php echo $href; ?>" ><?php echo $message ?></a>
</div>
<?php
}else if(($meta = get_user_meta($current_user->ID, "clockin")) == array()){
	$json = json_decode(file_get_contents(dirname( __FILE__ )."/../secret.json"));
	$cid = $json->cid;
	$redirect_uri = plugins_url("auth.php", dirname(__FILE__));
	$state = "clock-in_plugin".$current_user->ID;

	$href = "https://github.com/login/oauth/authorize";
	$href .= "?client_id=".$cid;
	$href .= "&redirect_uri=".urlencode($redirect_uri);
	$href .= "&state=".$state;
	
	$message = "Authorize our plugin";
?>
<div class="clockin-wrap">
<a href="<?php echo $href; ?>" ><?php echo $message ?></a>
</div>
<?php
}else{
	$nonce = wp_create_nonce("clock_in");
	wp_enqueue_script ('clock_in');
	wp_enqueue_script ('clock_in_proj');
	wp_enqueue_script ('waypoint');
	wp_enqueue_script ('waypoint_infinite');
	wp_localize_script('clock_in_proj', 'clock_in_vars', array("clockin_uri"=>admin_url('admin-ajax.php?action=clock_in&nonce='.$nonce)));
	if(isset($atts["cur_project"])){
		$href = admin_url('admin-ajax.php?action=clock_in&proj='.urlencode($atts["curproject"]).'&nonce='.$nonce);
		$message = "Clock in!";
?>
<div class="clockin-wrap">
<a class="clockin_anchor" href="<?php echo $href; ?>" ><?php echo $message ?></a>
</div>
<?php
	}else if($meta[0]["clocked"] == true){
		$href = admin_url('admin-ajax.php?action=clock_out&nonce='.$nonce);
		$message = "Clock out!";
?>
<div class="clockin-wrap">
<a class="clockin_anchor" href="<?php echo $href; ?>" ><?php echo $message ?></a>
</div>
<?php
	}else{
		include_once plugin_dir_path(__FILE__)."/users_projects.php"; 
		$href = plugins_url("clocked.php", __FILE__)."?action=in";
		try{	
			$result = getCLProjects();
		}catch(Exception $e){
			goto start;
		}
?>
		<div class="clockin-wrap">
<?php if(count($result[1]) == 0) echo "You don't have any projects, feel free to start one on <a href='http://github.com'>github!</a>";
	else{ ?>
			
			Choose a project to Clock Into
			<div class="clockin_projects_window window">
			<ul class="clockin_projects">
			<?php doCLProjUI($result, $nonce)?>
			</ul>
			</div>
		</div>
		<script type="text/javascript">
			jQuery(function($){new clock_in_proj(".clockin_projects_window")});
		</script>
	<?php } ?>
<?php
	}
}
?>
