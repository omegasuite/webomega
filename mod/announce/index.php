<?php
/**
 * @version $Id: index.php,v 1.14 2004/11/16 19:57:22 rizzo Exp $
 */

if (!isset($GLOBALS['core'])){
  header('location:../../?module=announce&ANN_user_op=list');
  exit();
}


/* Check to see if the manager exists and create it if it doesn't */
if(!isset($_SESSION['SES_ANN_MANAGER'])) {
  $_SESSION['SES_ANN_MANAGER'] = new PHPWS_AnnouncementManager;
}

if($GLOBALS['module'] == 'announce') {
  $GLOBALS['CNT_announce'] = array('title'=>$_SESSION['translate']->it('Announcements'),
				   'content'=>NULL);
}

if(isset($_REQUEST['ANN_op']) && $_SESSION['OBJ_user']->allow_access('announce')) {

  switch($_REQUEST['ANN_op']) {
  case 'menu':
    $_SESSION['SES_ANN_MANAGER']->menu();
    $_SESSION['SES_ANN_MANAGER']->listAnnouncements();
    break;

  case 'pushnews':
	  $_SESSION['SES_ANN'] = new PHPWS_Announcement;
  case 'editnews':
    $_SESSION['SES_ANN_MANAGER']->menu();
    if (!$_SESSION['SES_ANN']) $_SESSION['SES_ANN'] = new PHPWS_Announcement;
    if($_SESSION['OBJ_user']->allow_access('announce', 'edit_announcement')) {
        /* Enable JustBlogIt! usage */
        if(!empty($_REQUEST['subject']))
            $_REQUEST['subject'] = $_SESSION['SES_ANN']->_subject = stripslashes($_REQUEST['subject']);
        else
            $_REQUEST['subject'] = $_SESSION['translate']->it('Click Here');
        if(!empty($_REQUEST['summary']))
            $_SESSION['SES_ANN']->_summary = stripslashes($_REQUEST['summary'])."\n";
        if(!empty($_REQUEST['url']))
            $_SESSION['SES_ANN']->_summary .= "\n" . '<a href="'.$_REQUEST['url'].'">'.$_REQUEST['subject'].'</a>' . "\n";
    }

    $_SESSION['SES_ANN']->editnews();
    break;

  case 'new':
    $_SESSION['SES_ANN_MANAGER']->menu();
    $_SESSION['SES_ANN'] = new PHPWS_Announcement;
    if($_SESSION['OBJ_user']->allow_access('announce', 'edit_announcement')) {
        /* Enable JustBlogIt! usage */
        if(!empty($_REQUEST['subject']))
            $_REQUEST['subject'] = $_SESSION['SES_ANN']->_subject = stripslashes($_REQUEST['subject']);
        else
            $_REQUEST['subject'] = $_SESSION['translate']->it('Click Here');
        if(!empty($_REQUEST['summary']))
            $_SESSION['SES_ANN']->_summary = stripslashes($_REQUEST['summary'])."\n";
        if(!empty($_REQUEST['url']))
            $_SESSION['SES_ANN']->_summary .= "\n" . '<a href="'.$_REQUEST['url'].'">'.$_REQUEST['subject'].'</a>' . "\n";
    }

    $_SESSION['SES_ANN']->edit();
    break;

  case 'template':
    $_SESSION['SES_ANN_MANAGER']->menu();
    $_SESSION['SES_ANN'] = new PHPWS_Announcement;
    if($_SESSION['OBJ_user']->allow_access('announce', 'edit_announcement')) {
        /* Enable JustBlogIt! usage */
        if(!empty($_REQUEST['subject']))
            $_REQUEST['subject'] = $_SESSION['SES_ANN']->_subject = stripslashes($_REQUEST['subject']);
        else
            $_REQUEST['subject'] = $_SESSION['translate']->it('Click Here');
        if(!empty($_REQUEST['summary']))
            $_SESSION['SES_ANN']->_summary = stripslashes($_REQUEST['summary'])."\n";
        if(!empty($_REQUEST['url']))
            $_SESSION['SES_ANN']->_summary .= "\n" . '<a href="'.$_REQUEST['url'].'">'.$_REQUEST['subject'].'</a>' . "\n";
    }

    $_SESSION['SES_ANN']->template();
    break;

  case 'list':
    $_SESSION['SES_ANN_MANAGER']->menu();
    $_SESSION['SES_ANN_MANAGER']->listAnnouncements();
    break;

  case 'settings':
    $_SESSION['SES_ANN_MANAGER']->menu();
    $_SESSION['SES_ANN_MANAGER']->getSettings();
    break;

  case 'save_settings':
    $_SESSION['SES_ANN_MANAGER']->menu();
    $_SESSION['SES_ANN_MANAGER']->setSettings();
    $_SESSION['SES_ANN_MANAGER']->listAnnouncements();
    break;

  case 'save':
    $_SESSION['SES_ANN_MANAGER']->menu();
    if (!$_SESSION['SES_ANN']) $_SESSION['SES_ANN'] = new PHPWS_Announcement;
    $_SESSION['SES_ANN']->save();
    //$_SESSION['SES_ANN_MANAGER']->listAnnouncements();
    break;

  case 'edit':
    $_SESSION['SES_ANN_MANAGER']->menu();
    if(isset($_REQUEST['ANN_id']) && is_numeric($_REQUEST['ANN_id'])) {
      $_SESSION['SES_ANN'] = new PHPWS_Announcement($_REQUEST['ANN_id']);
      $_SESSION['SES_ANN']->edit();
    }
    break;

  case 'delete':
    $_SESSION['SES_ANN_MANAGER']->menu();
    if(isset($_POST['ANN_massdel']) && is_array($_POST['ANN_massdel'])) {
		foreach ($_POST['ANN_massdel'] as $aid) {
	      $GLOBALS["core"]->sqlDelete("mod_announce", "id", $aid);
	      if (class_exists("PHPWS_Fatcat"))
			PHPWS_Fatcat::purge($aid, "announce");

	      if (class_exists("PHPWS_Comment")){
			$where['module'] = "announce";
			$where['itemId'] = $aid;
			$GLOBALS['core']->sqlDelete("mod_comments_data", $where);
	      }
		}
	    unset($_SESSION["SES_ANN_MANAGER"]->_pager);
	}
    else if(isset($_REQUEST['ANN_id']) && is_numeric($_REQUEST['ANN_id'])) {
      $_SESSION['SES_ANN'] = new PHPWS_Announcement($_REQUEST['ANN_id']);
      $_SESSION['SES_ANN']->delete();
    }
	$_SESSION['SES_ANN_MANAGER']->listAnnouncements();
    break;
    
  case 'show':
    $_SESSION['SES_ANN_MANAGER']->menu();
    if(isset($_REQUEST['ANN_id']) && is_numeric($_REQUEST['ANN_id'])) {
      $_SESSION['SES_ANN'] = new PHPWS_Announcement($_REQUEST['ANN_id']);
      $_SESSION['SES_ANN']->showHide();
      $_SESSION['SES_ANN_MANAGER']->listAnnouncements();
    }
    break;

  case 'hide':
    $_SESSION['SES_ANN_MANAGER']->menu();
    if(isset($_REQUEST['ANN_id']) && is_numeric($_REQUEST['ANN_id'])) {
      $_SESSION['SES_ANN'] = new PHPWS_Announcement($_REQUEST['ANN_id']);
      $_SESSION['SES_ANN']->showHide();
      $_SESSION['SES_ANN_MANAGER']->listAnnouncements();
    }
    break;
    
  case 'view':
    $_SESSION['SES_ANN_MANAGER']->menu();
    if(isset($_REQUEST['ANN_id']) && is_numeric($_REQUEST['ANN_id'])) {
      $_SESSION['SES_ANN'] = new PHPWS_Announcement($_REQUEST['ANN_id']);
      $_SESSION['SES_ANN']->view('full');
    }
    break;

  case 'wxview':
    if(isset($_REQUEST['ANN_id']) && is_numeric($_REQUEST['ANN_id'])) {
      $_SESSION['SES_ANN'] = new PHPWS_Announcement($_REQUEST['ANN_id']);
      $_SESSION['SES_ANN']->wxview('full');
    }
    break;
  case 'viewjournal':
      $_SESSION['SES_ANN'] = new PHPWS_Announcement();
      $_SESSION['SES_ANN']->viewjournal();
    break;	
  }
}
elseif (isset($_REQUEST['ANN_op'])) {
  switch($_REQUEST['ANN_op']) {
  case 'wxview':
    if(isset($_REQUEST['ANN_id']) && is_numeric($_REQUEST['ANN_id'])) {
      $_SESSION['SES_ANN'] = new PHPWS_Announcement($_REQUEST['ANN_id']);
      $_SESSION['SES_ANN']->wxview('full');
    }
    break;
  case 'viewjournal':
      $_SESSION['SES_ANN'] = new PHPWS_Announcement();
      $_SESSION['SES_ANN']->viewjournal();
    break;	
  }
}

if(isset($_REQUEST['MOD_op'])) $_REQUEST['ANN_user_op'] = $_REQUEST['MOD_op'];

if(isset($_REQUEST['ANN_user_op'])) {

  switch($_REQUEST['ANN_user_op']) {
  case 'list':
    $_SESSION['SES_ANN_MANAGER']->listAnnouncements();
    break;

  case 'delmsg':
	$GLOBALS['core']->sqlDelete('msgq', 'id', -$_REQUEST['msg']);
  case 'messages':
	$_SESSION['OBJ_layout']->extraHead('<script src="ui/web/announce/js/main.js" type="text/javascript"></script>');

    $_SESSION['SES_ANN_MANAGER']->announcementList();
    break;
    
  case 'view':
    if(isset($_REQUEST['ANN_id']) && is_numeric($_REQUEST['ANN_id'])) {
      $_SESSION['SES_ANN'] = new PHPWS_Announcement($_REQUEST['ANN_id']);
      $_SESSION['SES_ANN']->view('full');
      $_SESSION['SES_ANN']->hit();
    }
    break;
    
  case 'submit_announcement':
    $_SESSION['SES_ANN_MANAGER']->userMenu();
    $_SESSION['SES_ANN'] = new PHPWS_Announcement;
    $_SESSION['SES_ANN']->edit();
    break;

  case 'save':
    $_SESSION['SES_ANN']->save();
    break;

  case 'categories':
    $_SESSION['SES_ANN_MANAGER']->categories();
    break;

  case 'show':
    $_SESSION['SES_ANN_MANAGER']->showAnnouncements();
    break;

  case 'getmessage':   
    if(isset($_REQUEST['ANN_id'])) {
      $_SESSION['SES_ANN'] = new PHPWS_Announcement($_REQUEST['ANN_id']);
      exit(json_encode(array('status'=>'OK', 'result'=>$_SESSION['SES_ANN']->_wxview('full'))));
    }
    break;

  case 'wxview':
    if(isset($_REQUEST['ANN_id']) && is_numeric($_REQUEST['ANN_id'])) {
      $_SESSION['SES_ANN'] = new PHPWS_Announcement($_REQUEST['ANN_id']);
      $_SESSION['SES_ANN']->wxview('full');
    }
    break;
  }
}

?>
