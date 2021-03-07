<?php
$image['name'] = "fatcat.png";
$image['alt'] = "Fatcat Categorizer by Matt McNaney";

$link[] = array("label"=>"Fatcat Categorizer",
		"module"=>"fatcat",
		"description"=>"Categorizes all of the information on your site.",
		"url"=>"index.php?module=fatcat&amp;fatcat[admin]=menu",
		"image"=>$image,
		"admin"=>TRUE,
		"tab"=>"content");

?>