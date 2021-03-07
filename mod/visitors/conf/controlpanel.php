<?php

$image["name"] = "visitors.gif";
$image["alt"] = "Author: rck <http://www.kiesler.at>";

$link[] = array ("label"=>"Visitors",
		 "module"=>"visitors",
		 "url"=>"index.php?module=visitors&amp;".
		 		"visitors_op=stats&amp;curr=month",
		 "image"=>$image,
		 "admin"=>TRUE,
		 "description"=>"What do your visitors actually do ".
		 	"on your site at the moment?",
		 "tab"=>"administration");

?>