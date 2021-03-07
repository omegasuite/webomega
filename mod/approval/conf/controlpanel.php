<?php

$image['name'] = "approve.png";
$image['alt'] = "Approval by Matt McNaney";

$link[] = array("label"=>"Approval",
		"module"=>"approval",
		"description"=>"Allows you to approve elements from different modules in one convenient place.",
		"image"=>$image,
		"url"=>"index.php?module=approval&amp;approval_op=admin",
		"admin"=>TRUE,
		"tab"=>"administration");

?>