<?php

require_once(PHPWS_SOURCE_DIR . "core/Form.php");
require_once(PHPWS_SOURCE_DIR . "core/WizardBag.php");
require_once(PHPWS_SOURCE_DIR . "core/List.php");

class PHPWS_Security {

  var $sec_codes;

  function PHPWS_Security(){
    $this->sec_codes = NULL;
  }

  function show_log(){
    $tags = array();
    $tags['TITLE'] = "Security Log";
    $tags['ID_LABEL'] = $_SESSION["translate"]->it("ID");
    $tags['TIME_LABEL'] = "Time";
    $tags['MODULE_LABEL'] = "Module";
    $tags['USER_LABEL'] = "User";
    $tags['IP_LABEL'] = "IP";
    $tags['OFFENSE_LABEL'] = "Offense";
    $log = new PHPWS_List;
    $log->setModule("security");
    $log->setIdColumn("log_id");	
    $log->setClass("PHPWS_Security_Log");
    $log->setTable("mod_security_log");
    $log->setDbColumns(array("log_id", "timestamp", "ip_address", "sec_mod_name", "offense", "sec_user_id"));
    $log->setListColumns(array("log_id", "timestamp", "ip_address", "sec_mod_name", "offense", "sec_user_id"));
    $log->setName("log");
    $log->setOp("secure_op=admin_ops&amp;sec_admop[view_log]=Manage%20Logs");
    $log->setPaging(array("limit"=>10, "section"=>TRUE, "limits"=>array(5,10,20,50), "back"=>"&#60;&#60;", "forward"=>"&#62;&#62;", "anchor"=>FALSE));
    $log->setExtraListTags($tags);
    $log->setOrder("timestamp DESC");
    $title = $_SESSION["translate"]->it("Security Logs");

    $clear_logs = '<br />' . PHPWS_Text::moduleLink(
		      $_SESSION["translate"]->it("Clear Logs"), 
			   "security", array("module"=>"security", 
					     "secure_op"=>"clear_log"));
    $content = $this->admin_link() . $log->getList() . $clear_logs;
    $_SESSION["OBJ_layout"]->popbox($title, $content, NULL, "CNT_security");
  }

  function clear_logs() {
    $title = NULL;
    if(isset($_POST["yes"])){

      if($GLOBALS["core"]->sqlDelete("mod_security_log")) {
	$content = $_SESSION["translate"]->it("Logs cleared successfully.");
      } else {
	$content = $_SESSION["translate"]->it("Problem clearing logs.");
      }

      $content = '<b>' . $content . '</b>';
      $content .= $this->show_log();
    } elseif (isset($_POST["no"])) {
      $content = '<b>'.$_SESSION["translate"]->it("You have choosen not to clear the logs.") . "</b><br />";
      $content .= $this->show_log();
    } else {
      $elements[0]  = PHPWS_Form::formHidden("module", "security");
      $elements[0] .= PHPWS_Form::formHidden("secure_op", "clear_log");
      $elements[0] .= PHPWS_Form::formSubmit($_SESSION["translate"]->it("Yes"), "yes");
      $elements[0] .= PHPWS_Form::formSubmit($_SESSION["translate"]->it("No"), "no");

      $content = $_SESSION["translate"]->it("Are you sure you wish to clear all logs") . "?<br /><br />";
      $content .= PHPWS_Form::makeForm("log_clear", "index.php", $elements);
      $title = $_SESSION["translate"]->it("Confirmation");
    }
    $_SESSION["OBJ_layout"]->popbox($title, $content, NULL, "CNT_security");
  }

  function log($offense=NULL, $mod=NULL, $ip=NULL, $userid=NULL) {
    if(!$ip) $ip=$_SERVER['REMOTE_ADDR'];
    if(!$userid) $userid=$_SESSION['OBJ_user']->getUserId();
    if(!$mod) $mod=$GLOBALS['core']->current_mod;
    $log['ip_address'] = $ip;
    $log['offense'] = $offense;
    $log['sec_user_id'] = $userid;
    $log['sec_mod_name'] = $mod;
    $GLOBALS['core']->sqlInsert($log, "mod_security_log");
  }
  
  function admin_menu(){

    $array1[] = "Default";
    $all_mods = array_merge($array1, $GLOBALS["core"]->listModules());

    $title = $_SESSION["translate"]->it("Apache Settings");

    $content = "
<form action=\"index.php\" method=\"post\">
".PHPWS_Form::formHidden("module", "security")."
".PHPWS_Form::formHidden("secure_op", "admin_ops")."
".PHPWS_Form::formSubmit($_SESSION['translate']->it('Manage Logs'), "sec_admop[view_log]").$_SESSION["OBJ_help"]->show_link("security", "manage_logs")."&nbsp;&nbsp;
".PHPWS_Form::formSubmit($_SESSION['translate']->it('Manage Error Pages'), "sec_admop[error_pages]").$_SESSION["OBJ_help"]->show_link("security", "manage_errorpage")."&nbsp;&nbsp;
".PHPWS_Form::formSubmit($_SESSION['translate']->it('Manage Access'), "sec_admop[manage_access_menu]").$_SESSION["OBJ_help"]->show_link("security", "manage_access")."<br />
</form>";
    $_SESSION["OBJ_layout"]->popbox($title, $content, NULL, "CNT_security");
  }

  function admin_link(){
    return "<div style='text-align:right;'><a href=\"index.php?module=security&amp;secure_op=admin_menu\">".$_SESSION["translate"]->it("Apache Settings")."</a></div>";
  }

  function force_admin(){
    header("location:index.php?module=security&secure_op=admin_menu");
    exit();
  }

  function display_error_page($error) {
    $results = $GLOBALS["core"]->sqlSelect("mod_security_errorpage");
    if ($results){
      foreach($results as $errorpage){
	if($errorpage["error"] == $error) {
	  $title = $errorpage["label"];
	  $content = $errorpage["content"];
	  break;
	}
      }
    } 
    $_SESSION['OBJ_layout']->popbox($title, $content, NULL, "CNT_security");
  }

  function manage_error_page() {
    $results = $GLOBALS["core"]->sqlSelect("mod_security_errorpage");
    $content = $this->admin_link();
    if ($results){
    $content .= "<table border=\"0\"><tr class=\"bg_medium\"><td>".$_SESSION["translate"]->it("Error Number")."</td><td>".$_SESSION["translate"]->it("Label")."</td><td>&#160;</td><td>&#160;</td></tr>";
      $highlight = NULL;
      foreach($results as $errorpage){
	if($highlight)
	  $content .= "<tr class=\"bg_medium\">";
	else
	  $content .= "<tr class=\"bg_light\">";
	$content .= "<td>".$errorpage["error"]."</td><td>".$errorpage["label"]."</td><td>";
	$myelements[0] = PHPWS_Form::formHidden("module", "security");
	$myelements[0] .= PHPWS_Form::formHidden("secure_op", "admin_ops");
	$myelements[0] .= PHPWS_Form::formHidden("sec_admop[edit_error_page]",$errorpage["error"]);
 	$myelements[0] .= PHPWS_Form::formSubmit($_SESSION["translate"]->it("Edit"), "bla");
	$content .= PHPWS_Form::makeForm("security_error_page", "index.php", $myelements, "post", 0, 1);
	$content .= "</td><td>";
	$myelements[0] = PHPWS_Form::formHidden("module", "security");
	$myelements[0] .= PHPWS_Form::formHidden("secure_op", "admin_ops");
	$myelements[0] .= PHPWS_Form::formHidden("sec_admop[delete_error_page]",$errorpage["error"]);
 	$myelements[0] .= PHPWS_Form::formSubmit($_SESSION["translate"]->it("Delete"), "bla");
	$content .= PHPWS_Form::makeForm("security_error_page", "index.php", $myelements, "post", 0, 1);
	$content .= "</td></tr>";
	PHPWS_WizardBag::toggle($highlight);	
      }
      $content .= "</table><br />";
    }
    $title = $_SESSION["translate"]->it("Manage Error Pages");
    $myelements[0] = PHPWS_Form::formHidden("module", "security");
    $myelements[0] .= PHPWS_Form::formHidden("secure_op", "admin_ops");
    $myelements[0] .= PHPWS_Form::formSubmit($_SESSION["translate"]->it("Add"), "sec_admop[add_error_page]");
    $content .= PHPWS_Form::makeForm("security_error_page", "index.php", $myelements, "post", 0, 1);
    $_SESSION["OBJ_layout"]->popbox($title, $content, NULL, "CNT_security"); 
  }

  function edit_error_page($error_number) {
    $results = $GLOBALS["core"]->sqlSelect("mod_security_errorpage","error", $error_number);
    if ($results){
      foreach($results as $errorpage)
	$myelements[0] = PHPWS_Form::formHidden("module", "security")
	. PHPWS_Form::formHidden("secure_op", "admin_ops")
	. PHPWS_Form::formHidden("sec_admop[save_error_page]",$error_number)
	. "<table border=\"0\"><tr><td>".$_SESSION["translate"]->it("Error Lable")."</td><td>"
	. PHPWS_Form::formTextField("label",$errorpage["label"])."</td></tr><tr><td>"
	. $_SESSION["translate"]->it("Content")."</td><td>".PHPWS_Form::formTextArea("content",$errorpage["content"],6)
	. "</td></tr></table>".PHPWS_Form::formSubmit($_SESSION["translate"]->it("Save"), "bla");
      $content = "<span style=\"text-align:center;\">".$this->admin_link()."</span><br />"; 
      $content .= PHPWS_Form::makeForm("security_error_page", "index.php", $myelements, "post", 0, 1);
      $title = "Edit Error Page";
      $_SESSION["OBJ_layout"]->popbox($title, $content, NULL, "CNT_security"); 
    }
  }

  function save_error_page($error_number) {
    $GLOBALS["core"]->sqlUpdate(array("label"=>$_REQUEST["label"], "content"=>$_REQUEST["content"]), "mod_security_errorpage", "error", $error_number);
    $this->manage_error_page();
  }

  function add_error_page() {

    $dropbox = array(""=>"",401=>"401",402=>"402",403=>"403",404=>"404",500=>"500",501=>"501");
    $myelements[0] = PHPWS_Form::formHidden("module", "security")
      . PHPWS_Form::formHidden("secure_op", "admin_ops")."<table border=\"0\"><tr><td>"
      . $_SESSION["translate"]->it("Error Number")."</td><td>".PHPWS_Form::formSelect("error2", $dropbox)
      . "&nbsp;".$_SESSION["translate"]->it("Or")."&nbsp;".PHPWS_Form::formTextField("error","",3)
      . "</td></tr><tr><td>".$_SESSION["translate"]->it("Error Label")."</td><td>".PHPWS_Form::formTextField("label","")
      . "</td></tr><tr><td>".$_SESSION["translate"]->it("Content")."</td><td>".PHPWS_Form::formTextArea("content","",6)
      . "</td></tr></table>".PHPWS_Form::formSubmit($_SESSION["translate"]->it("Save"), "sec_admop[add_save_error_page]");
      $content = $this->admin_link(); 
      $content .= PHPWS_Form::makeForm("security_error_page", "index.php", $myelements, "post", 0, 1);
      $title = $_SESSION["translate"]->it("Add Error Page");
      $_SESSION["OBJ_layout"]->popbox($title, $content, NULL, "CNT_security");   
  }

  function add_save_error_page() {
    if($_REQUEST["error"] || $_REQUEST["error2"]) {
      if(!$_REQUEST["error"])
	$error_page["error"] = $_REQUEST["error2"];
      else
	$error_page["error"] = $_REQUEST["error"];
      $sql = "select error from ".PHPWS_TBL_PREFIX."mod_security_errorpage where error=".$error_page["error"];    
      if(!$GLOBALS["core"]->quickFetch($sql)) {
	$error_page["label"] = $_REQUEST["label"];
	$error_page["content"] = $_REQUEST["content"];
	$GLOBALS["core"]->sqlInsert($error_page, "mod_security_errorpage");
	$this->make_htaccess();
      }
    }
    $this->manage_error_page();
  }

  function delete_error_page_conf() {
    $GLOBALS["core"]->sqlDelete("mod_security_errorpage","error", $_REQUEST["sec_admop"]["delete_error_page_conf"]);
    $this->make_htaccess();
    $this->manage_error_page();
  }

  function delete_error_page() {
 
    $title = $_SESSION["translate"]->it("Delete Error Page");
    $content = $_SESSION["translate"]->it("Are you sure you want to delete the")." ".$_REQUEST["sec_admop"]["delete_error_page"]." ".$_SESSION["translate"]->it("error page")."?"." <a href=\"./index.php?module=security&amp;secure_op=admin_ops&amp;sec_admop[delete_error_page_conf]=".$_REQUEST["sec_admop"]["delete_error_page"]."\">".$_SESSION["translate"]->it("Yes")."</a> | <a href=\"./index.php?module=security&amp;secure_op=admin_menu\">".$_SESSION["translate"]->it("No")."</a>";

    $_SESSION["OBJ_layout"]->popbox($title, $content, NULL, "CNT_security") . "<br />";
  }

  function manage_access_menu() {
    $sql = "select data from ".PHPWS_TBL_PREFIX."mod_security_settings where name='access_default'";    
    $row_reg = $GLOBALS["core"]->quickFetch($sql);

    $title = $_SESSION["translate"]->it("Manage Access");
    $content = $this->admin_link(); 
    $content .= "<form action=\"index.php\" method=\"post\"><table border=\"0\" width=\"100%\" cellpading=\"7\"><tr><td>".PHPWS_Form::formHidden("module", "security").PHPWS_Form::formHidden("secure_op", "admin_ops").PHPWS_Form::formRadio('access_set_add', '1',$row_reg["data"],NULL,$_SESSION["translate"]->it("Allow")).PHPWS_Form::formRadio('access_set_add', '0',$row_reg["data"],NULL,$_SESSION["translate"]->it("Deny"))."<br />".PHPWS_Form::formTextField("ipaddress",NULL,15,15)."<br />".PHPWS_Form::formSubmit($_SESSION["translate"]->it("Add"), "sec_admop[add_this_ipban]").$_SESSION["OBJ_help"]->show_link("security", "ip_add_ex")."</td><td align=\"center\">";   

    $content .= PHPWS_Form::formRadio('access_default', '1',$row_reg["data"],NULL,$_SESSION["translate"]->it("Allow")).PHPWS_Form::formRadio('access_default', '0',$row_reg["data"],NULL,$_SESSION["translate"]->it("Deny"))."<br />".$_SESSION["translate"]->it("The public by default.")."<br />".PHPWS_Form::formSubmit($_SESSION["translate"]->it("Save"), "sec_admop[set_access]")."</td><td>".PHPWS_Form::formSubmit($_SESSION["translate"]->it("htaccess extra"),"sec_admop[edit_htaccess_extra]")."</td></tr><tr><td colspan=\"3\">";

    if($row_reg["data"])
      $allow = "0";
    else
      $allow = "1";

    $results = $GLOBALS["core"]->sqlSelect("mod_security_ipinfo","allow",$allow,"timestamp");
    if ($results){
	 if($row_reg["data"])
	   $content .= "<span style=\"text-align:center;\"><h3>".$_SESSION["translate"]->it("Deny List");
	 else
	   $content .= "<span style=\"text-align:center;\"><h3>".$_SESSION["translate"]->it("Allow List");
      $content .= "</h3></span><table border=\"0\" width=\"100%\"><tr class=\"bg_medium\"><td>".$_SESSION["translate"]->it("IP Address")."</td><td>".$_SESSION["translate"]->it("Time")."</td><td>&#160;</td></tr>";
    $highlight = NULL;
     foreach($results as $ipban){
       if($highlight)
	 $content .= "<tr class=\"bg_medium\">";
       else
	 $content .= "<tr class=\"bg_light\">";
       $date = $GLOBALS["core"]->datetime->date($ipban["timestamp"], 1);
       $content .= "<td>".$ipban["ipaddress"]."</td><td>".$date["full"]." - ".$date["time"]."</td><td>".PHPWS_Form::formCheckBox("ban_allow_id[]", $ipban["ban_allow_id"])."</td></tr>";
       PHPWS_WizardBag::toggle($highlight);
     }
     $content .= "</table>";
    $content .= PHPWS_Form::formSubmit($_SESSION["translate"]->it("Delete"), "sec_admop[delete_this_ipban]");
    }
    $content .= "</td></tr></table></form>";
    $_SESSION["OBJ_layout"]->popbox($title, $content, NULL, "CNT_security") . "<br />";
  }

  function set_allow_deny($set) {
    $GLOBALS["core"]->sqlUpdate(array("data"=>$set), "mod_security_settings" , "name", "access_default");
    $this->make_htaccess();
    $this->manage_access_menu();
  }

  function delete_this_ipban() {
    foreach($_REQUEST["ban_allow_id"] as $key=>$value)
      $GLOBALS["core"]->sqlDelete("mod_security_ipinfo","ban_allow_id", $value);
    $this->make_htaccess();
    $this->manage_access_menu();
  }

  function add_this_ipban() {
    if($_REQUEST["ipaddress"]){
      $insert["ipaddress"] = $_REQUEST["ipaddress"];
      $insert["timestamp"] = NULL;
      if($_REQUEST["access_set_add"])
	$insert["allow"] = 1;
      else
	$insert["allow"] = 0;
      $GLOBALS["core"]->sqlInsert($insert,"mod_security_ipinfo");
      $this->make_htaccess();
      $this->manage_access_menu();
    } else
      $this->manage_access_menu();
 }

  function edit_htaccess_extra() {
    $sql = "select data from ".PHPWS_TBL_PREFIX."mod_security_settings where name='htaccess_extra'";    
    $row_reg = $GLOBALS["core"]->quickFetch($sql);
    $myelements[0] = PHPWS_Form::formHidden("module", "security").PHPWS_Form::formHidden("secure_op", "admin_ops").$_SESSION["translate"]->it(".htaccess extra info")."<br />".PHPWS_Form::formTextArea("extrainfo",$row_reg["data"],6)."<br />".PHPWS_Form::formSubmit($_SESSION["translate"]->it("Save"), "sec_admop[save_htaccess_extra]");
    $content = $this->admin_link(); 
    $content .= PHPWS_Form::makeForm("security_htaccess_edit", "index.php", $myelements, "post", 0, 1);
    $title = $_SESSION["translate"]->it("Edit htacces info");
    $_SESSION["OBJ_layout"]->popbox($title, $content, NULL, "CNT_security"); 
  }

  function save_htaccess_extra() {
   $GLOBALS["core"]->sqlUpdate(array("data"=>$_REQUEST["extrainfo"]), "mod_security_settings" , "name", "htaccess_extra");
    $this->make_htaccess();
    $this->manage_access_menu();
  }

  function custom_htaccess_error_page() {
    $results = $GLOBALS["core"]->sqlSelect("mod_security_errorpage");
    $error_page = NULL;

    if(isset($_SERVER['HTTPS'])) $http = "https://";
    else                         $http = "http://";

    if ($results){
      foreach($results as $errorpage){
	$error_page .= "ErrorDocument ".$errorpage["error"]." $http".PHPWS_HOME_HTTP."index.php?module=security&page=".$errorpage["error"]."\n";
      }
    }
    return $error_page;
  }

  function ban_ip() {
    $sql = "select data from ".PHPWS_TBL_PREFIX."mod_security_settings where name='access_default'";    
    $row_reg = $GLOBALS["core"]->quickFetch($sql);

    if($row_reg["data"])
      $allow = "0";
    else
      $allow = "1";

    $write_data = NULL;
    $results = $GLOBALS["core"]->sqlSelect("mod_security_ipinfo","allow",$allow);
    if ($results){
      $write_data .= "<Limit GET PUT POST>\n";
      if($row_reg["data"]) {
	$write_data .= "order allow,deny\nAllow from all\n";
	$access = "deny";
      }
      else {
	$write_data .= "order deny,allow\nDeny from all\n";
	$access = "allow";
      }
      foreach($results as $baninfo){
	$write_data .= "$access from ".$baninfo["ipaddress"]."\n";
      }
      $write_data .= "</Limit>";
    }
    return $write_data;
  }

  function make_htaccess($path = NULL) {
    $sql = "select data from ".PHPWS_TBL_PREFIX."mod_security_settings where name='htaccess_extra'";    
    $row_reg = $GLOBALS["core"]->quickFetch($sql);
    $write_data = $row_reg["data"]."\n\n";
    $write_data .= $this->custom_htaccess_error_page();
    $write_data .= $this->ban_ip();
    if($path)
      $this->write_htaccess($path, $write_data);
    else
      $this->write_htaccess(PHPWS_HOME_DIR, $write_data);
  }

  function write_htaccess($save_path, $write_data) {
    $fp = @fopen($save_path.".htaccess", "w");
    @fputs($fp, $write_data);
    @fclose($fp);
  }

}

class PHPWS_Security_Log {
      /**
     * Database id of this log
     * @var    integer
     * @access private
     */
    var $log_id;

    /**
     * Timestamp of this log
     * @var    string
     * @access private
     */
    var $timestamp;

     /**
     * Module of this log
     * @var    string
     * @access private
     */
    var $sec_mod_name;

    /**
     * User ID of this log
     * @var    string
     * @access private
     */
    var $sec_user_id;

    /**
     * IP of this log
     * @var    string
     * @access private
     */
    var $ip_address;

    /**
     * Title of this log
     * @var    string
     * @access private
     */
    var $offense;

    function PHPWS_Security_Log($log=NULL) {
        if (is_numeric($log)) {
            if ($result = $GLOBALS["core"]->sqlSelect("mod_security_log", "log_id", $log)) {
                $this->log_id = $log;
                $this->timestamp = $result[0]['timestamp'];
                $this->sec_mod_name = $result[0]['sec_mod_name'];
                $this->sec_user_id = $result[0]['sec_user_id'];
                $this->ip_address = $result[0]['ip_address'];
                $this->offense = $result[0]['offense'];
            }
        } else if (is_array($log)) {
            $this->log_id = $log['log_id'];
            $this->timestamp = $log['timestamp'];
            $this->sec_mod_name = $log['sec_mod_name'];
            $this->sec_user_id = $log['sec_user_id'];
            $this->ip_address = $log['ip_address'];
            $this->offense = $log['offense'];
        }
    }

    function getListLog_Id() {
        return $this->log_id;
    }

    function getListTimestamp() {
        $date = $GLOBALS["core"]->datetime->date($this->timestamp, 1);
        return $date["full"]." - ".$date["time"];
    }

    function getListSec_Mod_Name() {
        return $this->sec_mod_name;
    }

    function getListSec_User_Id() {
	if($GLOBALS["core"]->sqlSelect('mod_users', 'user_id', $this->sec_user_id)) { 
        return $_SESSION['OBJ_user']->getUsername($this->sec_user_id);
     } else {
	return $_SESSION['translate']->it('Unknown User'); 
     }
    }

    function getListIp_Address() {
        return $this->ip_address;
    }

    function getListOffense() {
        return $this->offense;
    }
}

?>