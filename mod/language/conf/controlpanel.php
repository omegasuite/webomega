<?php
$image['name'] = "language.png";
$image['alt'] = "Language Administrator by Matt McNaney";

$image2['name'] = "language.png";
$image2['alt'] = "Set My Language";

$link[] = array("label"=>"Language Administrator",
		"module"=>"language",
		"description"=>"Lets you administrate the language options for your site.",
		"image"=>$image,
		"admin"=>TRUE,
		"url"=>"index.php?module=language&amp;lng_adm_op=admin",
		"tab"=>"administration");

$link[] = array("label"=>"Set My Language",
		"module"=>"language",
		"description"=>"Lets you pick the language to display the site in.",
		"image"=>$image2,
		"admin"=>FALSE,
		"url"=>"index.php?module=language&amp;lng_usr_op=user_admin",
		"tab"=>"my_settings");

?>