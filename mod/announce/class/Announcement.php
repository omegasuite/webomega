<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/WizardBag.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

if($GLOBALS["core"]->moduleExists("comments")) {
  require_once(PHPWS_SOURCE_DIR . "mod/comments/class/CommentManager.php");
}

/**
 * Controls the announcement forms, saving, and display 
 *
 * Patch - 2 Mar 2005 Thomas Gordon - sticky announcements
 * 
 * @version $Id: Announcement.php,v 1.93 2005/05/12 13:01:40 darren Exp $
 * @author Adam Morton
 * @modified Steven Levin
 * @modified Matt McNaney
 */

class PHPWS_Announcement {

  var $_id = NULL;
  var $_subject = NULL;
  var $_summary = NULL;
  var $_body = NULL;
  var $_image = array();
  var $_sticky_id;
  var $_hits = 0;
  var $_approved = 0;
  var $_active = 1;
  var $_comments = 0;
  var $_anonymous = 0;
  var $_new = TRUE;
  var $_user = FALSE;
  var $_poston;
  var $_expiration;
  var $_userCreated;
  var $_userUpdated;
  var $_dateCreated;
  var $_dateUpdated;
  var $_news;
  var $_msgq = false;

  function PHPWS_Announcement($ANN_id = NULL) {
    if($ANN_id === NULL) {
      $this->_new = TRUE;

      if(isset($_REQUEST["ANN_user_op"])) $this->_user = TRUE;
      elseif (isset($_REQUEST["ANN_op"])) $this->_user = FALSE;

      $this->_poston = date("Y-m-d H:i:s");
	  $this->_expiration = date("Y-m-d H:i:s", time() + 3600 * 24 * 7);
      $this->_news = array();
    } else if(is_array($ANN_id)) {
		foreach($ANN_id as $key => $value) {
			$key = "_{$key}";
			$this->$key = $value;
		}
    } elseif ($ANN_id > 0) {
      $result = $GLOBALS["core"]->sqlSelect("mod_announce", "id", $ANN_id);
      
      $this->_new = FALSE;
      $this->_id = $result[0]["id"];
      $this->_subject = $result[0]["subject"];
      $this->_summary = $result[0]["summary"];
      $this->_body = $result[0]["body"];
      $this->_sticky_id = $result[0]["sticky_id"];
      
      if (isset($result[0]["image"])) $this->_image = unserialize($result[0]["image"]);
      else $this->_image = NULL;
 
      $this->_hits = $result[0]["hits"];
      $this->_active = $result[0]["active"];
      $this->_approved = $result[0]["approved"];
      $this->_comments = $result[0]["comments"];
      $this->_anonymous = $result[0]["anonymous"];
      $this->_poston = $result[0]["poston"];
      $this->_expiration = $result[0]["expiration"];
      $this->_userCreated = $result[0]["userCreated"];
      $this->_userUpdated = $result[0]["userUpdated"];
      $this->_dateCreated = $result[0]["dateCreated"];
      $this->_dateUpdated = $result[0]["dateUpdated"];
    } else {
      $result = $GLOBALS["core"]->sqlSelect("msgq", "id", -$ANN_id);
      
      $this->_new = FALSE;
      $this->_id = $result[0]["id"];
      $this->_subject = '微信信息';
      $this->_summary = '';
      $this->_body = $result[0]["textmsg"];
      $this->_sticky_id = 0;
      
      $this->_image = NULL;
 
      $this->_active = true;
      $this->_approved = true;
      $this->_comments = '';
      $this->_anonymous = 0;
      $this->_poston = $result[0]["rcvd"];
	  $this->_msgq = true;
    }
  }

  function view($type, $approveView=FALSE, $fatcatView=FALSE) {
    if(!$this->_active && !$_SESSION["OBJ_user"]->allow_access("announce"))
      return;

    if($approveView || isset($_REQUEST["lay_quiet"])) {
      $approveView = TRUE;
      $layQuiet = "lay_quiet=1&amp;";
    } else
      $layQuiet = "";

    $tags["SUBJECT"] = $this->_subject;
    $tags["SUMMARY"] = PHPWS_Text::parseOutput($this->_summary);
    $tags["BODY"] = PHPWS_Text::parseOutput($this->_body);
    $tags["EXPIRATION"] = $this->viewDate($this->_expiration);
    $tags["POSTED_BY"] = "发布人";
    $tags["UPDATED_BY"] = "修订人";
    $tags["EXPIRES"] = "有效期";
    $tags["ON"]  = "于";

    if (!empty($this->_userCreated))
      $tags["POSTED_USER"] = $this->_userCreated;
    else
      $tags["POSTED_USER"] = $_SESSION["translate"]->it("匿名");

    $tags["POSTED_DATE"] = $this->viewDate($this->_dateCreated);
    $tags["UPDATED_USER"] = $this->_userUpdated;
    $tags["UPDATED_DATE"] = $this->viewDate($this->_dateUpdated);

    $poston = explode(" ", $this->_poston);
    $tags["POSTON_DATE"] = $poston[0];

    if($_SESSION["OBJ_user"]->allow_access("announce", "edit_announcement")) {
      $edit = $_SESSION["translate"]->it("编辑");

      $tags["EDIT"] = "<a href=\"./index.php?module=announce&amp;ANN_op=edit&amp;".$layQuiet."ANN_id=$this->_id\">" . $_SESSION["translate"]->it("编辑") . "</a>";
    }

    if($_SESSION["OBJ_fatcat"]) {
      $tags["CATEGORY_ICON"] = $_SESSION["OBJ_fatcat"]->getIcon($this->_id, FALSE, FALSE, "announce");
      $tags["CATEGORY_IMAGE"] = $_SESSION["OBJ_fatcat"]->getImage($this->_id, FALSE, FALSE, "announce");
    }

    if(isset($this->_image["name"]))
      $tags["IMAGE"] = "<img src=\"images/announce/" . $this->_image["name"] . "\" border=\"0\" width=\"" . $this->_image["width"] . "\" height=\"" . $this->_image["height"] . "\" alt=\"" . $this->_image["alt"] . "\" title=\"" . $this->_image["alt"] . "\" />";


    /* Full view of the announcement */
    if($type == "full") {
      if($GLOBALS["core"]->moduleExists("comments") && $this->_comments) {
	if(!isset($_SESSION["PHPWS_CommentManager"]))
	  $_SESSION["PHPWS_CommentManager"] = new PHPWS_CommentManager;
	$tags["COMMENTS"] = $_SESSION["PHPWS_CommentManager"]->listCurrentComments("announce", $this->_id, $this->_anonymous);
      }

      $content = PHPWS_Template::processTemplate($tags, "announce", "view_full.tpl");

      if($_SESSION["OBJ_fatcat"])
	$_SESSION["OBJ_fatcat"]->whatsRelated($this->_id);

    } elseif ($type == "small") {

      if(!$approveView && (!$this->_active || !$this->_approved))
	return;

	unset($tags["POSTED_BY"]);

      /* Summarized view of announcement, user on home page */
      if($GLOBALS["core"]->moduleExists("comments") && $this->_comments) {
	$numComments = PHPWS_CommentManager::numComments("announce", $this->_id);
	$tags["NUM_COMMENTS"] = "<a href=\"index.php?module=announce&amp;ANN_user_op=view&amp;ANN_id=" . $this->_id .  "\">$numComments " . $_SESSION["translate"]->it("回复") . "</a>";
      }

      if (strlen($this->_body) > 0){
//	$tags["READ_TEXT"] = $_SESSION['translate']->it("Read");
	/* if hits = 0 set it to string "None" or it won't show up in the template */
	if($this->_hits == 0)
	  $tags["HITS"] = $_SESSION["translate"]->it("无");
	else
	  $tags["HITS"] = $this->_hits;

	if ($tags["BODY"])
		$tags["READ_MORE"] = "<a href=\"index.php?module=announce&amp;ANN_user_op=view&amp;".$layQuiet."ANN_id=" . $this->_id .  "\">" . $_SESSION["translate"]->it("更多") . "</a>";
        $content = PHPWS_Template::processTemplate($tags, "announce", "view_small2.tpl");
      }
    else $content = PHPWS_Template::processTemplate($tags, "announce", "view_small3.tpl");

    }

    if($approveView) {
      PHPWS_Approval::viewInApprovalWin($content);
    } else if($fatcatView) {
      return $content;
    }else {
      if(!isset($GLOBALS["CNT_announce"])) {
	$GLOBALS["CNT_announce"] = array("title"=>NULL, "content"=>NULL);
      }

 //     $GLOBALS["CNT_announce"]["title"]    = $_SESSION["translate"]->it("News and Announcements");
      $GLOBALS["CNT_announce"]["title"]    = $_SESSION["translate"]->it("新");
      $GLOBALS["CNT_announce"]["content"] .= $content;
    }
  }

  function wxview($type, $approveView=FALSE, $fatcatView=FALSE) {
	  $h = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
			<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" />
			<meta name="title" content="公告通知" />
			<meta name="language" content="zh" />
			<link rel="stylesheet" type="text/css" media="screen" href="themes/Default/mobile.css" />
			<link rel="stylesheet" type="text/css" media="screen" href="themes/Default/mobile-wap.css" />
			<link href="themes/Default/switch.css" rel="stylesheet" type="text/css" />
			</head><body>';

    echo $h .  '<div class="box"><div class="text_tem pb20"><div class="text_tem_cont">' . $this->_wxview($type, $approveView, $fatcatView) . "</div></div></div></body></html>";
    exit();
  }

  function _wxview($type, $approveView=FALSE, $fatcatView=FALSE) {
    $tags["SUBJECT"] = $this->_subject;
    $tags["BODY"] = PHPWS_Text::parseOutput($this->_body);

    if(isset($this->_image["name"]))
      $tags["IMAGE"] = "<img src=\"images/announce/" . $this->_image["name"] . "\" border=\"0\" width=\"" . $this->_image["width"] . "\" height=\"" . $this->_image["height"] . "\" alt=\"" . $this->_image["alt"] . "\" title=\"" . $this->_image["alt"] . "\" />";

    $content = PHPWS_Template::processTemplate($tags, "announce", "wxview_full.tpl");

	extract($_REQUEST);
	extract($_POST);

	$this->hit();

	return '<pre>' . str_replace("[LINK]", $link, $content) . '</pre>';
  }

  function viewDate($date) {
    $info = explode(" ", $date);
    $date = explode("-", $info[0]);
    $time = explode(":", $info[1]);
    return date(PHPWS_DATE_FORMAT . " " . PHPWS_TIME_FORMAT, (mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0])+$GLOBALS['core']->datetime->time_dif));
  }

  function formatDate($date) {
    return date("Ymd", strtotime($date));
  }

  function edit() {
    $tags = array();
    $tags["SUBJECT_LABEL"] = $_SESSION["translate"]->it("标题");
    $tags["SUBJECT"] = PHPWS_Form::formTextField("ANN_subject", $this->_subject, 60, 255);
    $tags["SUMMARY_LABEL"] = $_SESSION["translate"]->it("摘要");
    $tags["SUMMARY"] = PHPWS_WizardBag::js_insert("wysiwyg", "announce_edit", "ANN_summary") . PHPWS_Form::formTextArea("ANN_summary", $this->_summary, 10, 70);
    $tags["BODY_LABEL"] = $_SESSION["translate"]->it("内容");
//    $tags["BODY"] = PHPWS_WizardBag::js_insert("wysiwyg", "announce_edit", "ANN_body") . PHPWS_Form::formTextArea("ANN_body", $this->_body, 10, 70);
    $tags["BODY"] = PHPWS_Form::formTextArea("ANN_body", $this->_body, 10, 70);
    $tags["SET_STICKY_LABEL"] = $_SESSION["translate"]->it("Make announcement sticky?");
    $tags["SET_STICKY"] = PHPWS_Form::formCheckBox("ANN_set_sticky", 1, ($this->_sticky_id > 0) ? 1 : 0);
    $tags['STICKY_HELP'] = CLS_help::show_link('announce', 'sticky');	
    if ($_SESSION['OBJ_user']->allow_access('announce')) {
      $tags["IMAGE_LABEL"] = $_SESSION["translate"]->it("图片");
      $tags["IMAGE"] = PHPWS_Form::formFile("ANN_image");

      if(!empty($this->_image["name"])) {
	$tags["IMAGE"] .= "<br /><img src=\"images/announce/" . $this->_image["name"] . "\" border=\"0\" width=\"" .
	  $this->_image["width"] . "\" height=\"" . $this->_image["height"] . "\" alt=\"" . $this->_image["alt"] .
	  "\" title=\"" . $this->_image["alt"] . "\" />";
	$tags["REMOVE_LABEL"] = $_SESSION["translate"]->it("删除图片");
	$tags["REMOVE_CHECK"] = PHPWS_Form::formCheckBox("ANN_remove_image");
      }
      
      if (isset($this->_image["alt"]))
	$altTag = $this->_image["alt"];
      else
	$altTag = NULL;
      
      $tags["IMAGE_ALT_LABEL"] = $_SESSION["translate"]->it("Short Description");
      $tags["IMAGE_ALT"] = PHPWS_Form::formTextField("ANN_alt", $altTag, 60, 100);
    }

    $tags["POSTON_LABEL"] = $_SESSION["translate"]->it("发布");
    $tags["POSTON"] = PHPWS_Form::formDate("ANN_poston", $this->formatDate($this->_poston), substr($this->formatDate($this->_poston), 0, 4));
    $tags["POSTON"] .= PHPWS_WizardBag::js_insert("popcalendar", NULL,
			                          NULL, FALSE,
			                    array("month"=>"ANN_poston_month", 
				                  "day"=>"ANN_poston_day", 
				                  "year"=>"ANN_poston_year"));
    $tags["EXPIRATION_LABEL"] = $_SESSION["translate"]->it("有效期");
    $tags["EXPIRATION"] = "<input name='ANN_expiration' onfocus='showCalendarControl(this);this.blur();' type='text' value='" . ($this->_expiration?$this->_expiration : date("Y-m-d", strtotime("+1 month"))) . "'>";

    if($GLOBALS["core"]->moduleExists("comments")) {
      $tags["COMMENTS_LABEL"] = $_SESSION["translate"]->it("Allow Comments?");
      $tags["YES_COMMENTS"] = PHPWS_Form::formRadio("ANN_comments", 1, $this->_comments, NULL, "Yes");
      $tags["NO_COMMENTS"] = PHPWS_Form::formRadio("ANN_comments", 0, $this->_comments, NULL, "No");
      $tags["ANON_LABEL"] = $_SESSION["translate"]->it("匿名发布?");
      $tags["ANON_YES"] = PHPWS_Form::formRadio("ANN_anonymous", 1, $this->_anonymous, NULL, "Yes");
      $tags["ANON_NO"] = PHPWS_Form::formRadio("ANN_anonymous", 0, $this->_anonymous, NULL, "No");
    }

    if($_SESSION["OBJ_fatcat"]) {
      $tags["CATEGORIES_LABEL"] = $_SESSION["translate"]->it("类别");
      $tags["CATEGORIES"] = $_SESSION["OBJ_fatcat"]->showSelect($this->_id);
    }

    $tags["SUBMIT_BUTTON"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("发布"));

    if($this->_id)
		$tags["TITLE"] = $_SESSION["translate"]->it("修改公告通知");
	else $tags["TITLE"] = $_SESSION["translate"]->it("新公告通知");

    $elements[0] = PHPWS_Form::formHidden("module", "announce");

    if($this->_user)
      $elements[0] .= PHPWS_Form::formHidden("ANN_user_op", "save");
    else
      $elements[0] .= PHPWS_Form::formHidden("ANN_op", "save");

    if($this->_id)
      $elements[0] .= PHPWS_Form::formHidden("ANN_id", $this->_id);

    if(isset($_REQUEST["lay_quiet"]))
      $elements[0] .= PHPWS_Form::formHidden("lay_quiet", TRUE);

    $elements[0] .= PHPWS_Template::processTemplate($tags, "announce", "edit.tpl");

    $content = PHPWS_Form::makeForm("announce_edit", "index.php", $elements, "post", FALSE, TRUE);

    if(isset($_REQUEST["lay_quiet"])) {
      $content = PHPWS_Text::moduleLink(
			   $_SESSION["translate"]->it("返回"), 
			   "announce", array("ANN_user_op"=>"view", 
					     "lay_quiet"=>1, 
					     "ANN_id"=>$this->_id)) 
	         . "<br />" . $content;

      PHPWS_Approval::viewInApprovalWin($content);
      return;
    }

    $GLOBALS["CNT_announce"]["content"] .= $content;
  }

  function save() {
    if(isset($_REQUEST["ANN_op"]) && $_REQUEST["ANN_op"] == "save" && !$_SESSION["OBJ_user"]->allow_access("announce", "edit_announcement") && !$_SESSION["OBJ_user"]->allow_access("announce", "edit_announcement")) {
      $this->_error("你无权修改此公告通知");
      return;
    }

    if(!isset($_POST["ANN_id"])) {
      $this->_id = NULL;
      $this->_new = TRUE;
    }
    
    $this->_body = PHPWS_Text::parseInput($_POST["ANN_body"]);
    $data["body"] = $this->_body;

	if ($_POST["ANN_poston_year"]) {
	    $this->_poston = $_POST["ANN_poston_year"] . "-" . $_POST["ANN_poston_month"] . "-" . $_POST["ANN_poston_day"] . " 00:00:00";
		$data["poston"] = $this->_poston;
	}

	if ($_POST["ANN_expiration"]) {
	    $this->_expiration = $_POST["ANN_expiration"];
//    $this->_expiration = $_POST["ANN_expiration_year"] . "-" . $_POST["ANN_expiration_month"] . "-" . $_POST["ANN_expiration_day"] . " 00:00:00";
	    $data["expiration"] = $this->_expiration;
	}

    if(isset($_REQUEST["ANN_user_op"]) && $_REQUEST["ANN_user_op"] == "save" || (!$this->_new && !$this->_approved)) {
      $this->_approved = 0;
    } elseif($_REQUEST["ANN_op"] == "save") {
      $this->_approved = 1;
    }

    $data["approved"] = $this->_approved;

    if (isset($_POST["ANN_anonymous"])) {
	$this->_anonymous = $_POST["ANN_anonymous"];
    } else {
	$this->_anonymous = 0;
    }

    $data["anonymous"] = $this->_anonymous;

    if (isset($_POST["ANN_comments"])) {
	$this->_comments = (int)$_POST["ANN_comments"];
    } else {
	$this->_comments = 0;
    }
    $data["comments"] = $this->_comments;

    if($_POST["ANN_subject"]) {
      $this->_subject = PHPWS_Text::parseInput($_POST["ANN_subject"]);
      $data["subject"] = $this->_subject;
    } else {
      $this->_error("no_subject");
      $this->edit();
      return;
    }

/*
    if($_POST["ANN_summary"]) {
      $this->_summary = PHPWS_Text::parseInput($_POST["ANN_summary"]);
      $data["summary"] = $this->_summary;
    } else {
      $this->_error("no_summary");
      $this->edit();
      return;
    }
*/

    if ($_SESSION['OBJ_user']->allow_access('announce')) {
      if(isset($_FILES["ANN_image"]["name"]) && !empty($_FILES["ANN_image"]["name"])) {
	include(PHPWS_SOURCE_DIR . "mod/announce/conf/config.php");

	$image = EZform::saveImage("ANN_image", PHPWS_HOME_DIR . "images/announce/", $max_image_width, $max_image_height, NULL, NULL);

	if (PHPWS_Error::isError($image)){
	  $image->message("CNT_announce");
	  $this->edit();
	  return;
	}
      
	$this->_image = $image;

      } elseif (isset($this->_image["name"]) && isset($_POST["ANN_remove_image"])) {
	unlink(PHPWS_HOME_DIR . "images/announce/" . $this->_image["name"]);
	$this->_image = array();
	$data["image"] = $this->_image;
      }

      if(isset($this->_image["name"]) && isset($_POST["ANN_alt"])) {
	$this->_image["alt"] = $_POST["ANN_alt"];
	$data["image"] = serialize($this->_image);
      } elseif(isset($this->_image["name"])) {
	$this->_error("no_alt");
	$this->edit();
	return;
      }
    }

    if(isset($_POST['ANN_set_sticky'])) {
      if(empty($this->_sticky_id)) {
        $data["sticky_id"] = $GLOBALS["core"]->getOne("SELECT MAX(sticky_id)+1 FROM " . PHPWS_TBL_PREFIX . "mod_announce");

        if(empty($data["sticky_id"]))
	  $data["sticky_id"] = 1;
      }
    } else {
      $data["sticky_id"] = '0';
    }

    if($this->_new == TRUE && $this->_id = $this->refreshCheck())
      $this->_new = FALSE;

    if($this->_new) {
      if($this->add($data)) {
	if($this->_user) {
	  $short = "<b>" . $this->_subject . "</b><br />" . $this->_summary;
	  $info["id"] = $this->_id;
	  PHPWS_Approval::add($this->_id, $short, "announce");
	  PHPWS_Fatcat::deactivate($this->_id, "announce");
	  $content = $_SESSION["translate"]->it("Your announcement was submitted for approval.");
	} else {
	  $content = $_SESSION["translate"]->it("保存成功。");
//	  $this->wxpush();

	  if(strtotime($this->_poston) > time() && isset($_REQUEST["fatSelect"]))
	    $content .= "<br /><br />" . $_SESSION["translate"]->it("If you don't wish to have this announcement appear yet in the 'What's Related' box go to announcements settings and choose the 'Synchronize With Fatcat' option.");
	}
      } else {
	$this->_error("save_failed");
	$this->edit();
	return;
      }
    } elseif ($this->_id) {
      if($this->update($data)) {
	$content = $_SESSION["translate"]->it("完成更改。");

	if(strtotime($this->_poston) > time() && isset($_REQUEST["fatSelect"]))
	    $content .= "<br /><br />".$_SESSION["translate"]->it("If you don't wish to have this announcement appear yet in the 'What's Related' box go to announcements settings and choose the 'Synchronize With Fatcat' option.");

      } else {
	$this->_error("update_failed");
	$this->edit();
	return;
      }
    }

    if(isset($_REQUEST["lay_quiet"])) {
      if(isset($_REQUEST["ANN_op"]) && $_REQUEST["ANN_op"] == "save") {
	$content = $content . "<br /><br />" . PHPWS_Text::moduleLink(
			     $_SESSION["translate"]->it("返回"), 
			     "announce", array("ANN_user_op"=>"view", 
					       "lay_quiet"=>1, 
					       "ANN_id"=>$this->_id));
      }

      PHPWS_Approval::viewInApprovalWin($content);
    }

    $GLOBALS["CNT_announce"]["content"] .= $content;
    if($this->_user) {
      return;
    } else {
      $_SESSION["SES_ANN_MANAGER"]->listAnnouncements();
    }
  }

  function refreshCheck() {
    $compare = array("subject"     => $this->_subject, 
		     "summary"     => $this->_summary,
		     "body"        => $this->_body,
		     "hits"        => $this->_hits,
		     "approved"    => $this->_approved,
		     "active"      => $this->_active,
		     "comments"    => $this->_comments,
		     "anonymous"   => $this->_anonymous,
		     "poston"      => $this->_poston,
		     "expiration"  => $this->_expiration);

    if($result = $GLOBALS["core"]->sqlSelect("mod_announce", $compare))
      return $result[0]["id"];
    else
      return false;
  }

  function delete() {
    if(!$_SESSION["OBJ_user"]->allow_access("announce", "delete_announcement")) {
      $this->_error("你无权删除此公告通知");
      return;
    }

    if(isset($_POST["yes"])){
      if(!isset($this->_id)) {
	$this->_error("delete_failed");
	return;
      }

      $GLOBALS["core"]->sqlDelete("mod_announce", "id", $this->_id);
      if (class_exists("PHPWS_Fatcat"))
	PHPWS_Fatcat::purge($this->_id, "announce");

      if (class_exists("PHPWS_Comment")){
	$where['module'] = "announce";
	$where['itemId'] = $this->_id;
	$GLOBALS['core']->sqlDelete("mod_comments_data", $where);
      }

      unset($_SESSION["SES_ANN_MANAGER"]->_pager);
      $content = $_SESSION["translate"]->it("公告通知已删除。") . "<br />";
    } elseif (isset($_POST["no"])) {
      $content = $_SESSION["translate"]->it("You have chosen <b>not</b> to delete the announcement.") . "<br />";
    } else {
      $GLOBALS["core"]->sqlDelete("mod_announce", "id", $this->_id);

      if (class_exists("PHPWS_Fatcat"))	PHPWS_Fatcat::purge($this->_id, "announce");

      if (class_exists("PHPWS_Comment")){
		$where['module'] = "announce";
		$where['itemId'] = $this->_id;
		$GLOBALS['core']->sqlDelete("mod_comments_data", $where);
      }
      unset($_SESSION["SES_ANN_MANAGER"]->_pager);
      $content = $_SESSION["translate"]->it("公告通知已删除。") . "<br />";
    }

    $GLOBALS["CNT_announce"]["content"] .= $content;
  }

  function add($data) {
    if((isset($_REQUEST["ANN_op"]) && $_REQUEST["ANN_op"] == $_SESSION["translate"]->it("保存")) && !$_SESSION["OBJ_user"]->allow_access("announce", "edit_announcement")) {
      $this->_error("access_denied");
      return;
    }

    

    if (!empty($_SESSION["OBJ_user"]->username)){
      $data["userCreated"] = $_SESSION["OBJ_user"]->username;
      $data["userUpdated"] = $_SESSION["OBJ_user"]->username;
    } else {
      $data["userCreated"] = $_SESSION['translate']->it("匿名");
      $data["userUpdated"] = $_SESSION['translate']->it("匿名");
    }

    $data["dateCreated"] = date("Y-m-d H:i:s");
    $data["dateUpdated"] = date("Y-m-d H:i:s");

    $this->_id = $GLOBALS["core"]->sqlInsert($data, "mod_announce", FALSE, TRUE);

    if($_SESSION["OBJ_fatcat"])
      $_SESSION["OBJ_fatcat"]->saveSelect($this->_subject, "index.php?module=announce&amp;ANN_user_op=view&amp;ANN_id=" . $this->_id, $this->_id);

    if($this->_id)
      return TRUE;
    else
      return FALSE;
  }

  function update($data) {
    if(!$_SESSION["OBJ_user"]->allow_access("announce", "edit_announcement") && empty($data["hits"])) {
      $this->_error("access_denied");
      return;
    }

    if(empty($data["hits"])) {
      $data["userUpdated"] = $_SESSION["OBJ_user"]->username;
      $data["dateUpdated"] = date("Y-m-d H:i:s");
    }

    if($GLOBALS["core"]->sqlUpdate($data, "mod_announce", "id", $this->_id)){
      if($_SESSION["OBJ_fatcat"])
	$_SESSION["OBJ_fatcat"]->saveSelect($this->_subject, "index.php?module=announce&amp;ANN_user_op=view&amp;ANN_id=" . $this->_id, $this->_id);
      return TRUE;
    } else
      return FALSE;
  }

  function hit() {
    $this->_hits++;
	if ($this->_msgq) return;

    $data["hits"] = $this->_hits;
    $this->update($data);
  }

  function showHide() {
    if(!$_SESSION["OBJ_user"]->allow_access("announce", "activate_announcement")) {
      $this->_error("access_denied");
      return;
    }

    PHPWS_WizardBag::toggle($this->_active);
    if($this->_active) {
      PHPWS_Fatcat::activate($this->_id, "announce");
    } else {
      PHPWS_Fatcat::deactivate($this->_id, "announce");
    }

    $data["active"] = $this->_active;
    $this->update($data);
  }

  /**
   * Returns an indexed array of all the current groups in the database
   *
   * @return array $users An array of all groups
   * @access private
   * @see    edit()
   */
  function _getGroups() {
    /* Grab all groups from database */
    $result = $GLOBALS["core"]->sqlSelect("mod_user_groups", NULL, NULL, "group_name");

    /* Add blank group */
    $groups[] = " ";

    /* Create groups array */
    if($result)
    foreach($result as $resultRow)
      $groups[] = $resultRow["group_name"];

    return $groups;
  }// END FUNC _getGroups()

  function approve($id) {
    $data["approved"] = 1;
    $data["dateCreated"] = date("Y-m-d H:i:s");
    $data["dateUpdated"] = date("Y-m-d H:i:s");

    $GLOBALS["core"]->sqlUpdate($data, "mod_announce", "id", $id);
    PHPWS_Fatcat::activate($id, "announce");
  }

  function refuse($id) {
    $GLOBALS["core"]->sqlDelete("mod_announce", "id", $id);
    PHPWS_Fatcat::purge($id, "announce");
  }

  function fatView($id) {
    $announce = new PHPWS_Announcement($id);
    return $announce->view("small", FALSE, TRUE);
  }
  
  /**
   * Provides a simple form for other modules to post announcements.
   *
   * Use extModSave to save the announcement.
   * 
   * @author                   Darren Greene <dg49379@NOSPAM.tux.appstate.edu>
   */
  function extModForm() {
    $tags["POST_DATE"]   = PHPWS_Form::formDate("ext_ann_postDate");
    $tags["POST_DATE"] .= PHPWS_WizardBag::js_insert("popcalendar", 
			       NULL, NULL, FALSE,
			       array("month"=>"ext_ann_postDate_month", 
				     "day"=>"ext_ann_postDate_day", 
				     "year"=>"ext_ann_postDate_year"));
    $tags["POST_DATE_LBL"]   = $_SESSION["translate"]->it("发布日期");
    $tags["EXPIRE_DATE"] = PHPWS_Form::formDate("ext_ann_expireDate", date('Y')+10 . date('md'));
    $tags["EXPIRE_DATE"] .= PHPWS_WizardBag::js_insert("popcalendar", 
			       NULL, NULL, FALSE,
			       array("month"=>"ext_ann_expireDate_month", 
				     "day"=>"ext_ann_expireDate_day", 
				     "year"=>"ext_ann_expireDate_year"));

    $tags["EXPIRE_DATE_LBL"] = $_SESSION["translate"]->it("有效期");

    if($GLOBALS["core"]->moduleExists("comments")) { 
      $tags["COMMENTS_LBL"] = $_SESSION["translate"]->it("Allow Comments?");
      $tags["ANON_POSTS_LBL"] = $_SESSION["translate"]->it("Allow Anonymous Posts?");
      $tags["COMMENTS_FLD"]   = PHPWS_Form::formCheckBox("ext_ann_comments_fld");
      $tags["ANON_POSTS_FLD"] = PHPWS_Form::formCheckBox("ext_ann_anon_posts_fld");
    }
    return PHPWS_Template::processTemplate($tags, "announce", "extEdit.tpl");
  }

  /**
   * Saves an announcement created in another module
   *
   * This function is used to save the announcement created in a module other
   * than announce.  The data array should be filled with the external 
   * module's customizations such as the announcement title and the 
   * summary data.  The keys in the array correspond to the database 
   * attributes in the mod_announce table.
   *
   * @author                   Darren Greene <dg49379@NOSPAM.tux.appstate.edu>
   * @param array   data       Announcement save data
   * @param string  mod_name   Name of module creating announcement
   */
  function extModSave($data, $mod_name) {
    $error = FALSE;

    if(isset($_REQUEST["ext_ann_postDate_year"])) {
      $data["poston"] = $_REQUEST["ext_ann_postDate_year"] . '-'  . 
	$_REQUEST["ext_ann_postDate_month"] . '-' . 
	$_REQUEST["ext_ann_postDate_day"] . ' 00:00:00';
    } else {
      $error = TRUE;
    }

    if(isset($_REQUEST["ext_ann_expireDate_year"])) {
      $data["expiration"] = $_REQUEST["ext_ann_expireDate_year"] . '-'  . 
	$_REQUEST["ext_ann_expireDate_month"] . '-' . 
	$_REQUEST["ext_ann_expireDate_day"];
    } else {
      $error = TRUE;
    }

    if(isset($_REQUEST["ext_ann_comments_fld"]))
      $data["comments"] = 1;
    else
      $data["comments"] = 0;

    if(isset($_REQUEST["ext_ann_anon_posts_fld"]))
      $data["anonymous"]= 1;
    else
      $data["anonymous"]= 0;

    if(!isset($data["body"]))
      $data["body"] = NULL;

    if(!isset($data["subject"]))
      $error = TRUE;
    
    if(!isset($data["image"]))
      $data["image"] = NULL;

    if(!isset($data["approved"]))
      $data["approved"] = 1;

    if(!isset($data["active"]))
      $data["active"] = 1;

    if(!isset($data["summary"]))
      $data["summary"] = NULL;

    $data["hits"] = 0;
    $data["userCreated"] = $_SESSION["OBJ_user"]->username;
    $data["userUpdated"] = $_SESSION["OBJ_user"]->username;
    $data["dateCreated"] = date("Y-m-d H:i:s");
    $data["dateUpdated"] = date("Y-m-d H:i:s");

    if($error == FALSE) {
      if($id = $GLOBALS["core"]->sqlInsert($data, "mod_announce", FALSE, TRUE)) {
	$link = "index.php?module=announce&amp;ANN_id=$id&amp;ANN_op=view";
	$_POST["fatSelect"]["announce"] = $_POST["fatSelect"][$mod_name];

	$_SESSION["OBJ_fatcat"]->saveSelect($data["subject"], $link, $id, NULL, "announce");
	return TRUE;
	
      } else {
	return FALSE;
      }

    } else {
      return FALSE;
    }
  }

  function getListSubject() {
    if($_SESSION['OBJ_user']->allow_access("announce")) {
      return "<a href=\"index.php?module=announce&amp;ANN_id=$this->_id&amp;ANN_op=view\">$this->_subject</a>";
    } else {
      return "<a href=\"index.php?module=announce&amp;ANN_id=$this->_id&amp;ANN_user_op=view\">$this->_subject</a>";
    }
  }

  function getListActions() {
    $actions = array();

    if($_SESSION["OBJ_user"]->allow_access("announce", "activate_announcement")) {
      if($this->_active) {
	$actions[] = "<a href=\"./index.php?module=announce&amp;ANN_op=hide&amp;ANN_id={$this->_id}\">" . $_SESSION["translate"]->it("隐藏") . "</a>";
      } else {
	$actions[] = "<a href=\"./index.php?module=announce&amp;ANN_op=show&amp;ANN_id={$this->_id}\">" . $_SESSION["translate"]->it("显示") . "</a>";
      }
    }
    
    if($_SESSION['OBJ_user']->allow_access("announce")) {
      $actions[] = "<a href=\"index.php?module=announce&amp;ANN_id={$this->_id}&amp;ANN_op=view\">" . $_SESSION["translate"]->it("阅读") . "</a>";
    } else {
      $actions[] = "<a href=\"index.php?module=announce&amp;ANN_id={$this->_id}&amp;ANN_user_op=view\">" . $_SESSION["translate"]->it("阅读") . "</a>";
    }

    if($_SESSION["OBJ_user"]->allow_access("announce", "edit_announcement")) {
      $actions[] = "<a href=\"./index.php?module=announce&amp;ANN_op=edit&amp;ANN_id={$this->_id}\">" . $_SESSION["translate"]->it("修改") . "</a>";
    }
    
    if($_SESSION["OBJ_user"]->allow_access("announce", "delete_announcement")) {
      $actions[] = "<a href=\"./index.php?module=announce&amp;ANN_op=delete&amp;ANN_id={$this->_id}\">" . $_SESSION["translate"]->it("删除") . "</a>" . PHPWS_Form::formCheckBox("ANN_massdel[]", $this->_id, NULL, NULL, NULL);
    }

    return implode("&#160;|&#160;", $actions);
  }

  function getListDateCreated() {
    return $this->viewDate($this->_dateCreated);
  }

  function getListUserCreated() {
    return $this->_userCreated;
  }

  function _error($type) {
    $content = "<b><span class=\"errortext\">" . $_SESSION["translate"]->it("ERROR!") . "</span></b><br /><br />";
    switch($type) {
      case "no_summary":
      $content .= $_SESSION["translate"]->it("You did not provide a summary for your announcement.");
      break;

      case "no_subject":
      $content .= $_SESSION["translate"]->it("You did not provide a subject for your announcement.");
      break;

      case "no_alt":
      $content .= $_SESSION["translate"]->it("You must provide a short description for the image you supplied.");
      break;

      case "image_upload":
      $content .= $_SESSION["translate"]->it("There was a problem uploading the image you specified.  Check your permissions.");
      break;

      case "not_allowed_type":
      include(PHPWS_SOURCE_DIR . "conf/config.php");
      $content .= $_SESSION["translate"]->it("The file you uploaded is not an allowed type on this server") . ": <b>" . $_FILES["ANN_image"]["type"] . "</b><br />" .
      $_SESSION["translate"]->it("The allowed types are") . ": <b>" . implode(", ", $allowedImageTypes) . "</b>";
      break;

      case "save_failed":
      $content .= $_SESSION["translate"]->it("There was a problem saving your announcement.") . " " . $_SESSION["translate"]->it("If the problem persists contact your system administrator.");
      break;

      case "update_failed":
      $content .= $_SESSION["translate"]->it("There was a problem updating your announcement.") . " " . $_SESSION["translate"]->it("If the problem persists contact your system administrator.");
      break;

      case "delete_failed":
      $content .= $_SESSION["translate"]->it("There was a problem deleting your announcement.") . " " . $_SESSION["translate"]->it("If the problem persists contact your system administrator.");
      break;

      case "access_denied":
      $content .= "<b>" . $_SESSION["translate"]->it("ACCESS DENIED!") . "</b>";
      break;
    }

    $GLOBALS["CNT_announce"]["content"] .= $content;
  }

}// END CLASS PHPWS_Announcement

?>
