<?php


class clockin_object{
	public $id;
	public $post;

	public function __construct($user_id, $project){
		$project = get_post($project)
		$post = array(
			'post_content'	=> 'No commits yet',
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

	}

	public static function find($id){
	
	
	}
	
	public function finish(){

		$cl = get_post($id, 'ARRAY_A');

		$proj = get_post(get_post_meta($id, "project", true));
		$dev = $meta[0]["github"];
		$date = DateTime::createFromFormat("Y-m-d H:i:s", get_post_meta($id, "cl_start", true));
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
		$cl["content"] = $this->content(null, $response, "Worked on it");
		$cl["excerpt"] = "From ".$date->format("H:i")." to ".$now->format("H:i")." with ".count($response)." commits";
		wp_update_post($cl);
	
	}
	
	private function content($startmessage = null, $commits = array(), $endmessage = null){
	$project = get_post(get_post_meta($id, "project", true));
		ob_start();
?>
	<table class="clockin_table">
	<thead>
		<tr><th>Time</th><th>Type</th><th>Info</th></tr>
	</thead>
	<tbody>
		<tr>
			<td><time datetime="<?php
			if($startmessage != null){
				$start = new DateTime("NOW");
			}else $start = DateTime::createFromFormat(DATE_W3C, get_the_date($id));
			echo $start->format(DATE_W3C); ?>"><?php echo $start->format("H:i"); 
			
			?></time></td>
			<td>Start time</td>
			<td><?php echo $startmessage; ?></td>
		</tr>
<?php
	foreach($commits as $commit){
		$time = DateTime::createFromFormat(DATE_W3C,$commit->commit->committer->date);
?>		
		<tr>
			<td><time datetime="<?php echo $time->format(DATE_W3C); ?>"><?php echo $time->format("H:i"); ?></time><td>
			<td>Commit</td>
			<td><?php echo $commit->message; ?></td>
		</tr>
<?php
		
	}
	if($endtime != null){
		$now = new DateTime('NOW');
?>
		<tr>
			<td><time datetime="<?php echo $now->format(DATE_W3C); ?>"><?php echo $now->format("H:i"); ?></time></td>
			<td>End time</td>
			<td><?php echo $endmessage; ?></td>
		</tr>
	<?php } ?>
	</tbody>
	</table>
<?php
	
	return ob_get_clean();

	}

}