<div class="wrap">
<?php screen_icon(); ?>
<h2>Clock In Settings</h2>
<form method="post" action="options.php"> 
<?php

settings_fields( 'clock-in' );
do_settings_sections( 'clock-in-admin' );


?>

<?php submit_button(); ?>
</form>
</div>