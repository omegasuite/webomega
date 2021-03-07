<?php

if($_SESSION["OBJ_user"]->allow_access("announce")){
  require_once(PHPWS_SOURCE_DIR.'mod/announce/class/Announcement.php');

  if ($approvalChoice == "yes"){
    PHPWS_Announcement::approve($id);
  } else if ($approvalChoice == "no") {
    PHPWS_Announcement::refuse($id);
  } else if ($approvalChoice == "view") {
    $ann = new PHPWS_Announcement($id);
    echo $ann->view("small", TRUE);
  }
}

?>