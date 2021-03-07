<?php

$image["name"] = "security.png";
$image["alt"] = "Author: Jeremy Agee";

$link[] = array ("label"=>"Apache Settings",
		 "module"=>"security",
		 "url"=>"index.php?module=security&amp;secure_op=admin_menu",
		 "image"=>$image,
		 "description"=>"Go here to manage access to your web site as well as create custom error pages.",
		 "admin"=>TRUE,
		 "tab"=>"administration");

?>