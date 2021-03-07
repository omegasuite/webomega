<?php
$image['name'] = "layout.png";
$image['alt'] = "Layout Admin by Matt McNaney";

$image2['name'] = "layout.png";
$image2['alt'] = "Change my View";


$link[] = array("label"=>"Layout Admin",
		"module"=>"layout",
		"description"=>"Lets you control the look and layout of your site.",
		"url"=>"index.php?module=layout&amp;lay_adm_op=admin",
		"image"=>$image,
		"admin"=>TRUE,
		"tab"=>"administration");

$link[] = array("label"=>"Change My View",
		"module"=>"layout",
		"description"=>"Lets you pick a different theme to view the site.",
		"url"=>"index.php?module=layout&amp;layout_user=admin",
		"image"=>$image2,
		"admin"=>FALSE,
		"tab"=>"my_settings");

?>