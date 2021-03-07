<?php

$image["name"] = "help.png";
$image["alt"] = "Author: Jeremy Agee";

$link[0] = array ("label"=>"Absolute Help",
		  "module"=>"help",
		  "url"=>"index.php?module=help&amp;help_op=main_menu",
		  "description"=>"The Help module allows you to manage help information shown in key areas on your site.",
		  "image"=>$image,
		  "admin"=>TRUE,
		  "tab"=>"administration");

?>