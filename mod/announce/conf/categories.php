<?php

$listTags                      = array();
$listTags['TITLE']             = $_SESSION["translate"]->it("Current announcements");
$listTags['SUBJECT_LABEL']     = $_SESSION["translate"]->it("Subject");
$listTags['DATECREATED_LABEL'] = $_SESSION["translate"]->it("Date");
$listTags['USERCREATED_LABEL'] = $_SESSION["translate"]->it("Posted by");
$listTags['ACTIONS_LABEL']     = $_SESSION["translate"]->it("Actions");

$class       = "PHPWS_Announcement";
$table       = "mod_announce";
$dbColumns   = array("active", "subject", "dateCreated", "userCreated");
$listColumns = array("Subject", "DateCreated", "UserCreated");
$name        = "categories";
$where       = "approved='1' AND active='1'";
$order       = "dateCreated DESC";

?>