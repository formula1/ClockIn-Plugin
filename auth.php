<?php

require("../../../wp-load.php");
$json = json_decode(file_get_contents(plugin_dir_path( __FILE__ )."/secret.json"));

$id = get_current_user_id();

/*

We need to check if we are currently in the process of authenticating
-Yes, continue authenticating
	-If success change stored parameters, then continue
	-If failure, continue
-No, continue
-We need to check if the user is authenticated already
	-if not, check if theres an error message
	-If yes, we let the user know "you are authenticated"
	
During every request, we check if we need to get a refresh token
	-Yes, get one
	-If can't send an email to the website owner to reauthenticate


*/
if(!isset($_GET["state"])) die("not right");
if($_GET["state"] != "clock-in_plugin".$id) die($_GET["state"]);
if(!isset($_GET["code"])) die("bad code");

$postdata = array(
        'code' => $_GET["code"],
        'client_id' =>$json->cid,
		'client_secret'=>$json->cs
    );
	
	
	
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://github.com/login/oauth/access_token");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	'Accept: application/json',
));
curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
$result = curl_exec($ch);
$result = json_decode($result);



$url = "https://api.github.com/user?access_token=".$result->access_token;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
// Set so curl_exec returns the result instead of outputting it.
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	'User-Agent: formula1',
));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
// Get the response and close the channel.
$response = curl_exec($ch);
curl_close($ch);

$userinfo = json_decode($response);

update_user_meta( $id, "clockin", array("clocked" => false, "github" => $userinfo->login) );
wp_redirect( home_url() ); exit;
?>
