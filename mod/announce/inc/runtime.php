<?php

if($GLOBALS['module'] == "home") {

  require_once(PHPWS_SOURCE_DIR . "mod/announce/class/Announcement.php");

  function announceRuntime() {

    $result = $GLOBALS["core"]->sqlSelect("mod_announce_settings");

    if(!$result[0]['showCurrent'])
      return;
    
    $sql = "SELECT * FROM {$GLOBALS['core']->tbl_prefix}mod_announce WHERE expiration>'".date("Y-m-d H:i:s").
      "' AND active='1' AND poston<'".date("Y-m-d H:i:s").
      "' AND approved='1' ORDER BY sticky_id DESC, poston DESC, dateCreated DESC LIMIT {$result[0]['numHome']}";

    $resultCur = $GLOBALS["core"]->getAll($sql);
    
    if($resultCur) {
      foreach($resultCur as $row) {
	$current_announcement = new PHPWS_Announcement($row["id"]);
	$current_announcement->view("small");
      }
    }
    
    if(!$result[0]['showPast'])
      return;

    $limit = $result[0]['numHome'] + $result[0]['numPast'];
    $sql = "SELECT * FROM {$GLOBALS['core']->tbl_prefix}mod_announce WHERE expiration>'" . date("Y-m-d H:i:s") .
       "' AND poston<='" . date("Y-m-d H:i:s") . "' AND approved='1' AND active='1' ORDER BY poston DESC, dateCreated DESC LIMIT " . $limit;
    $resultPast = $GLOBALS["core"]->getAll($sql);

    if($resultPast) {
      $content = array();
      for($i=0; $i < $limit; $i++) {
	if($i < $result[0]['numHome']) {
	  continue;
	} elseif(isset($resultPast[$i]["subject"])) {
	  $content[]['ITEM'] = "<a href=\"index.php?module=announce&amp;ANN_user_op=view&amp;ANN_id=". $resultPast[$i]["id"] ."\">" . $resultPast[$i]["subject"] . "</a>";
	}
      }

      if (sizeof($content) > 0) {
	  $GLOBALS["CNT_announce_past"] = array("content"=>NULL);
	  $GLOBALS["CNT_announce_past"]["title"] = $_SESSION["translate"]->it("Past") ." ". $result[0]['numPast'] ." ". $_SESSION["translate"]->it("Announcements");
	  $GLOBALS["CNT_announce_past"]["content"] .= PHPWS_Template::processBlockTemplate($content, 'ITEM', null, 'announce', 'past.tpl');
      }
    }
  }

//  announceRuntime();
}

?>