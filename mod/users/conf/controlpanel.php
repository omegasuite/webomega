<?php
$image['name'] = "users.png";
$image['alt'] = "Users Administration by Matt McNaney";

$image2['name'] = "password.png";
$image2['alt'] = "Change my Password";

$link[] = array("label"=>"Users Administration",
		"module"=>"users",
		"description"=>"Lets you create and edit users and groups.",
		"url"=>"index.php?module=users&amp;user_op=admin",
		"image"=>$image,
		"admin"=>TRUE,
		"tab"=>"administration");

$link[] = array("label"=>"Change My Password",
		"module"=>"users",
		"description"=>"Allows you to change your email address and password.",
		"url"=>"index.php?module=users&amp;norm_user_op=user_options",
		"image"=>$image2,
		"admin"=>FALSE,
		"tab"=>"my_settings");

?>