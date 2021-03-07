<?php

/**
 * Visitors module for phpWebSite
 *
 * @author rck <http://www.kiesler.at/>
 */

require_once(PHPWS_SOURCE_DIR . "mod/visitors/class/visitors.php");

$block = new PHPWS_visitors;

$block->storeData();

if ($status = $block->showVisitorsBox()) {
	$CNT_visitors_box["title"] = $_SESSION["translate"]->it("Visitors");
	$CNT_visitors_box["content"] = $status;
}
?>