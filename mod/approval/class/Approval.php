<?php

require_once(PHPWS_SOURCE_DIR . "core/EZform.php");
require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/WizardBag.php");
require_once(PHPWS_SOURCE_DIR . "core/Array.php");

require_once(PHPWS_SOURCE_DIR . "mod/help/class/CLS_help.php");

require_once("Mail.php");

class PHPWS_Approval{

  var $id;
  var $mod_title;
  var $mod_id;
  var $display;
  var $error;

  function remove($mod_id=NULL, $mod_title=NULL){
    $mod_title = PHPWS_Approval::checkModName($mod_title);
    if (empty($mod_title))
      return FALSE;

    if (!is_null($mod_id))
      $delete['mod_id'] = (int)$mod_id;

    $delete['mod_title'] = $mod_title;

    return $GLOBALS['core']->sqlDelete("mod_approval_jobs", $delete);
  }

  function checkModName($mod_title){
    if (is_null($mod_title)){
      if (!($mod_title = $GLOBALS["core"]->current_mod) || $mod_title == "approval"){
	$this->error= "Unable to derive module title.";
	return FALSE;
      }
    } elseif (!$GLOBALS["core"]->moduleExists($mod_title)){
      $this->error= "Module does not exist.";
      return FALSE;
    }

    return $mod_title;
  }

  function add($mod_id, $display, $mod_title=NULL){
    $mod_title = PHPWS_Approval::checkModName($mod_title);
    if (empty($mod_title))
      return FALSE;

    /* Mail to admin. Might be an idea to use mime mail for html formatting. */

    extract(PHPWS_User::getSettings());
    $admin_email = $user_contact;

    $from = "\"" . $_SESSION["translate"]->it("Approval Admin") . "\" <$admin_email>";
    $subject = $_SESSION["translate"]->it("Awaiting Approval for module: \"[var1]\"", $mod_title);
    $message = $_SESSION["translate"]->it("You have a submission awaiting approval") . " on " . PHPWS_HOME_HTTP . "\n\n";

    /* Add the short details from the mod but strip tags. */
    $message .= $display;
    $message = str_replace("<br />","\n",$message);
    $message = strip_tags($message);

    $mail_object =& Mail::factory('mail');
    $headers['From'] = $from;
    $headers['Subject'] = $subject;
    $mail_object->send($admin_email, $headers, $message);

    $add["mod_title"] = $mod_title;
    $add["mod_id"]    = $mod_id;
    $add["display"]   = $display;

    return $GLOBALS["core"]->sqlInsert($add, "mod_approval_jobs", 1);
  }

  function process(){
    if ($modList = $_POST["modList"]){
      foreach ($modList as $mod_title=>$info){
	foreach ($info as $id=>$answer){
	  $postApp = new PHPWS_Approval;
	  $postApp->get($id);

	  if (!$postApp->_decision($answer))
	    $GLOBALS["CNT_approval"]["content"] .= "<span class=\"errortext\">" . $postApp->getError() . "</span><br />\n";
	}
      }
    }
  }

  function waitingForApproval($mod_id, $mod_title=NULL){
    if (is_null($mod_title)){
      if (!($mod_title = $GLOBALS["core"]->current_mod) || $mod_title == "approval"){
	$this->error= "Unable to derive module title.";
	return FALSE;
      }
    } elseif (!$GLOBALS["core"]->moduleExists($mod_title)){
	$this->error= "Module does not exist.";
	return FALSE;
    }

    if(PHPWS_Approval::getJob($mod_id, $mod_title))
      return TRUE;
    else
      return FALSE;
  }

  function getJob($mod_id, $mod_title){
    return $GLOBALS["core"]->sqlSelect("mod_approval_jobs", array("mod_title"=>$mod_title, "mod_id"=>$mod_id));
  }

  Function view(){
    $approvalFile = PHPWS_SOURCE_DIR . "mod/" . $GLOBALS["core"]->getModuleDir($this->mod_title) . "/conf/approval.php";
    if (!file_exists($approvalFile)){
      $this->error = "Approval file does not exist for <b>" . $this->mod_title . "</b> module.";
      return FALSE;
    } else {
      $id = $this->mod_id;
      $approvalChoice = "view";
      include ($approvalFile);
    }
  }

  function viewInApprovalWin($content, $title=NULL) {
    if($title == NULL)
      $title = $_SESSION["translate"]->it("Approval");
    else 
      $title = $_SESSION["translate"]->it("Approval") . " - " . $title;

    $tags["JS"]      = PHPWS_WizardBag::load_js_funcs();
    $tags["TITLE"]   = $title;
    $tags["CONTENT"] = $content;    
    echo PHPWS_Template::processTemplate($tags, "approval", "winView.tpl");
  }

  function admin(){
    $approvetpl["MODULES"] = NULL;
    $content = NULL;
    if (!($row = $GLOBALS["core"]->sqlSelect("mod_approval_jobs"))){
      $content .= $_SESSION["translate"]->it("No entries to approve"). ".";
      return $content;
    }

    foreach ($row as $jobs)
      $elements[$jobs["mod_title"]][] = $jobs;

    $form1 = new EZform;
    $form1->add("module", "hidden", "approval");
    $form1->add("approval_op", "hidden", "process");
    $form1->add("submit", "submit", $_SESSION["translate"]->it("Go"));
    $approvetpl = $form1->getTemplate();
    $approvetpl["MODULES"] = NULL;
    foreach ($elements as $mod_title=>$info){
      if (!$_SESSION["OBJ_user"]->allow_access($mod_title))
	continue;
      $sectiontpl["JOBS"] = NULL;

      $modInfo = $GLOBALS["core"]->getModuleInfo($mod_title);
      $sectiontpl["MOD_NAME"] = $modInfo["mod_pname"];
      $sectiontpl["MOD_TITLE"] = $mod_title;
      $sectiontpl["INFO_LBL"] = $_SESSION["translate"]->it("Information");
      $sectiontpl["CHOICE_LBL"] = $_SESSION["translate"]->it("Choice");
      $sectiontpl["ID_LBL"] = $_SESSION["translate"]->it("ID");

      foreach ($info as $data){
	$elementtpl = array();
	$form2 = new EZform;

	$name = "modList[" . $mod_title . "][" . $data["id"] ."]";
	$form2->add($name, "radio", array("yes", "no", "ignore"));
	$form2->setMatch($name, "ignore");
	$elementtpl = $form2->getTemplate(FALSE, FALSE);
	$elementtpl["ID"] = $data["mod_id"];

	if($_SESSION["OBJ_user"]->js_on)
	  $elementtpl["VIEW"]  = PHPWS_Approval::viewLink($data["id"], $_SESSION["translate"]->it("View"));
	else
	  $elementtpl["VIEW"] = PHPWS_Text::moduleLink($_SESSION["translate"]->it("View"), "approval", array("approval_op"=>"view", "id"=>$data["id"], "lay_quiet"=>1), "index");


	$elementtpl["DISPLAY"] = $data["display"];
	$elementtpl["YES"]     = $elementtpl[strtoupper($name)."_1"];
	$elementtpl["NO"]      = $elementtpl[strtoupper($name)."_2"];
	$elementtpl["IGNORE"]  = $elementtpl[strtoupper($name)."_3"];
	$elementtpl["YES_LBL"] = $_SESSION["translate"]->it("Yes");
	$elementtpl["NO_LBL"]  = $_SESSION["translate"]->it("No");
	$elementtpl["IGNORE_LBL"] = $_SESSION["translate"]->it("Ignore");
	$sectiontpl["JOBS"]   .= PHPWS_Template::processTemplate($elementtpl, "approval", "jobRows.tpl");
      }

      $approvetpl["MODULES"] .= PHPWS_Template::processTemplate($sectiontpl, "approval", "section.tpl");
    }

    if(empty($approvetpl["MODULES"])) {
      $content .= $_SESSION["translate"]->it("No entries to approve"). ".";
      return $content;
    } else {       
      $content .= PHPWS_Template::processTemplate($approvetpl, "approval", "approval.tpl");
      return $content;
    }
  }


  function viewLink($id, $mod_id){
    $window_array = array("type"=>"link",
			  "url"=>"./index.php?module=approval&amp;approval_op=view&amp;id=". $id,
			  "label"=>$mod_id,
			  "window_title"=>$_SESSION["translate"]->it("Approval View"),
			  "scrollbars"=>"yes",
			  "width"=>"640",
			  "height"=>"480",
			  "toolbar"=>"no"
			  );
    return PHPWS_WizardBag::js_insert("window", NULL, NULL, NULL, $window_array);
  }

  function get($id){
    if ($row = $GLOBALS["core"]->sqlSelect("mod_approval_jobs", "id", $id))
      PHPWS_Array::arrayToObject($row[0], $this);
    else
      $this->error = "Job id <b>$id</b> not found.";
  }


  function _decision($approvalChoice){
    if ($approvalChoice == "ignore")
      return TRUE;
    $approvalFile = PHPWS_SOURCE_DIR . "mod/" . $GLOBALS["core"]->getModuleDir($this->mod_title) . "/conf/approval.php";

    if (!file_exists($approvalFile)){
      $this->error = "Approval file does not exist for <b>" . $this->mod_title . "</b> module.";
      return FALSE;
    } else {
      $id = $this->mod_id;
      include ($approvalFile);

      if ($approvalChoice != "view"){
	$where["mod_title"] = $this->mod_title;
	$where["mod_id"]    = $this->mod_id;

	$GLOBALS["core"]->sqlDelete("mod_approval_jobs", $where);
      }

    }
    return TRUE;
  }


  function getError(){
    return $this->error;
  }
}

?>