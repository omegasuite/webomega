<?php
$image['name'] = "boost.png";
$image['alt'] = "Boost by Matt McNaney";

$link[] = array("label"=>"Boost",
		"module"=>"boost",
		"image"=>$image,
		"description"=>"Interface for installing, uninstalling, and updating modules.",
		"admin"=>TRUE,
		"url"=>"index.php?module=boost&amp;boost_op=adminMenu",
		"tab"=>"administration");

?>