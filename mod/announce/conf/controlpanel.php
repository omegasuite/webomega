<?php

$image["name"] = "announcement.png";
$image["alt"] = "Author: Adam Morton";

$link[] = array ("label"=>"Announcements",
		 "module"=>"announce",
		 "url"=>"index.php?module=announce&amp;ANN_op=menu",
		 "image"=>$image,
		 "admin"=>TRUE,
		 "description"=>"Go here to post announcements to your home page about upcoming events or other helpful information.",
		 "tab"=>"content");

?>