<?php


function getCLProjects($page = 'https://api.github.com/user/repos?type=all&sort=updated&per_page=4&page=1'){
	global $cl_utils;
	require_once dirname(__FILE__)."/../utils.php";
	$user_id = get_current_user_id();
	if($user_id === 0) throw new Exception("failure: need to login");
	$meta = get_user_meta($user_id, "clockin");
	if($meta == array()) throw new Exception("failure: this user needs to verify");

	$href = $page;

	$result = $cl_utils::getURL($href, $user_id,array(),true);
	$result[0] = explode("\n", $result[0]);
	$result[1] = json_decode($result[1]);
	$result[2] = $href;
	return $result;
}

function doCLProjUI($result, $nonce){
		if ( !wp_verify_nonce( $nonce, "clock_in")) {
			throw new Exception("failure: No naughty business please");
		}

		if($nonce == null) 	$nonce = wp_create_nonce("clock_in");
		$page = (isset($_GET['page']))?$_GET["page"]:1;
		$head = $result[0];
		$url = $result[2];
		$result = $result[1];
		$lastlink='';
		$nextlink='';
		foreach($head as $heads) {
			if (stripos($heads, 'Link: ') !== false) {
				$header = explode(",", $heads);
				foreach($header as $link){
					if(stripos($link, "rel=\"next\"") !== false) $nextlink = substr($link,stripos($link,"<")+1,stripos($link,">")-1-stripos($link,"<"));
					else if(stripos($link, "rel=\"last\"") !== false) $lastlink = substr($link,stripos($link,"<")+1,stripos($link,">")-1-stripos($link,"<"));
				}
				break;
			}
		}
?>
<?php	
				foreach($result as $item){ ?>
				<li>
					<h2 class='widget-title'><?php echo $item->name; ?></h2>
			<?php
				if(($project = get_page_by_title( $item->name, "OBJECT", "clockin_project" )) != null){ 
			?>
					<a href='<?php echo get_permalink($project->ID); ?>' >Project Page</a><br />
			<?php
				}
			?>
					<a target="_blank" href='<?php echo $item->html_url; ?>' >Github Page</a><br />
					<a class='clockin_anchor' href='<?php echo admin_url('admin-ajax.php?action=clock_in&nonce='.$nonce)."&proj=".urlencode($item->full_name); ?>'>Clock In</a>
				</li>
				<?php
				}
				if($lastlink != ''){
				$page+=1;
				?>
				<li>
					<a class="view_more bottom" href="<?php echo admin_url('admin-ajax.php').'?action=cl_projects&nonce='.$nonce.'&page='.urlencode($nextlink) ?>">View More</a>
				</li>
				<?php } ?>
<?php
}
?>