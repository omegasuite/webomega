<?php

$image['name'] = "search.png";
$image['alt'] = "Search";

$link[0] = array ("label"=>"Search Settings",
		  "module"=>"search",
		  "url"=>"index.php?module=search&amp;search_op=settings",
		  "description"=>"Search settings allow you to turn on and off the search block for specific modules and change the title for those blocks.",
		  "image"=>$image,
		  "admin"=>TRUE,
		  "tab"=>"administration");

?>