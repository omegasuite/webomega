<?php

require_once (PHPWS_SOURCE_DIR . "core/Form.php");
require_once (PHPWS_SOURCE_DIR . "core/Text.php");
require_once (PHPWS_SOURCE_DIR . "core/WizardBag.php");

/**
 * Class file for help module
 *
 * @version $1.0$
 * @author Jeremy Agee <jagee@NOSPAM.tux.appstate.edu>
 * @modified Adam Morton <adam@NOSPAM.tux.appstate.edu>
 * @module help
 * @modulegroup core
 * @package phpwebsite
 */
class CLS_help
{
  /* help variables */
  var $help_content;        // Array of help content from database keyed by the labels
  var $reg_id;              // Database registry id of the current module loaded into help
  var $active;              // 

  /**
   * Constructor for help
   *
   * @author Jeremy Agee <jagee@NOSPAM.tux.appstate.edu>
   * @modified Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @return none
   * @param int reg_id : Sets the id for the constructor to one help module
   * @param string reg_id : Sets the id for the constructor to one help module uses the name sent and looks up the id
   * @return none
   */
  function CLS_help($reg_id = NULL) {
    if(is_numeric($reg_id)) {
      $this->reg_id = $reg_id;

      $result = $GLOBALS["core"]->sqlSelect("mod_help", "reg_id", $reg_id);
      if($result)
	foreach($result as $value)
	  $this->help_content[$value["help_id"]] = array(stripslashes($value["label"]), $value["label_name_id"], stripslashes($value["content"]), $value["active"]);
    }
    elseif($reg_id) {
      $result = $GLOBALS["core"]->sqlSelect("mod_help_reg", "mod_name", $reg_id);
      if($result)
	foreach($result as $value) {
     $this->reg_id = $value["reg_id"];
     $this->active = $value["active"];
     $result = $GLOBALS["core"]->sqlSelect("mod_help", "reg_id", $this->reg_id);
     if($result)
	   foreach($result as $value)
	     $this->help_content[$value["help_id"]] = array(stripslashes($value["label"]), $value["label_name_id"], stripslashes($value["content"]), $value["active"]);
    }
   }
  }


  /**
   * main admin menu for help
   *
   * @author Jeremy Agee <jagee@NOSPAM.tux.appstate.edu>
   * @modified Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @return none
   */
  function main_menu() {
    $title = $_SESSION["translate"]->it("Choose a module");
    $user_text = $_SESSION["translate"]->it("Choose a module from the list below to load its help data for editing").":<br />";

    $result = $GLOBALS["core"]->sqlSelect("mod_help_reg");

    foreach($result as $value)
	$opt_array[$value["reg_id"]] = $value["mod_name"];

    $myelements[0] = PHPWS_Form::formHidden("module", "help");
    $myelements[0] .= $user_text . "<table border=\"0\"><tr><td>" . PHPWS_Form::formSelect("reg_id", $opt_array) . "</td><td>";
    $myelements[0] .= PHPWS_Form::formSubmit($_SESSION["translate"]->it("Load Module"), "help_op[load_module]");
    $myelements[0] .= $this->show_link("help", "main_menu") . "<br />";
    $myelements[0] .= PHPWS_Form::formSubmit($_SESSION["translate"]->it("Reload"), "help_op[reload]").$this->show_link("help", "reload")."</td></tr></table><br />";
    $content = PHPWS_Form::makeForm("HELP_main_menu", "index.php", $myelements, "post", 0, 1);

    $myelements[0] = PHPWS_Form::formHidden("module", "help");
    $myelements[0] .= PHPWS_Form::formHidden("help_op", "act_deact_module");
    $myelements[0] .= PHPWS_Form::formSubmit($_SESSION["translate"]->it("Activate/Deactivate"), "bla");
    $myelements[0] .= $this->show_link("help", "act_deact") . "<br /><br />";
    $content .= PHPWS_Form::makeForm("HELP2_main_menu", "index.php", $myelements, "post", 0, 1);

    $myelements[0] = PHPWS_Form::formHidden("module", "help");
    $myelements[0] .= PHPWS_Form::formHidden("help_op", "load_module");
    $myelements[0] .= PHPWS_Form::formTextField("load_module", $_SESSION["translate"]->it("Module Name"),15)."&nbsp;";
    $myelements[0] .= PHPWS_Form::formSubmit($_SESSION["translate"]->it("Load Module"), "bla");
    $myelements[0] .= $this->show_link("help", "load_module");
    $content .= PHPWS_Form::makeForm("HELP3_main_menu", "index.php", $myelements, "post", 0, 1);

    $_SESSION["OBJ_layout"]->popbox($title, $content, NULL, "CNT_help") . "<br />";
  }


  /**
   * Edit content for help
   *
   * @author Jeremy Agee <jagee@NOSPAM.tux.appstate.edu>
   * @return none
   */
  function edit_help_content() {
    $title = $_SESSION["translate"]->it("Edit module");
    $user_text = $_SESSION["translate"]->it("Edit the help data below") . ":<br />";

    $myelements[0] = PHPWS_Form::formHidden("module", "help") . PHPWS_Form::formHidden("reg_id", $this->reg_id) . $user_text;
    $myelements[0] .= $this->show_link("help", "edit_module") ."<br /><table border=\"0\">";

    foreach($this->help_content as $key=>$value) {
      $myelements[0] .= "<tr><td><b>$value[0]:</b>&nbsp;&nbsp;".PHPWS_Form::formCheckBox("active[$key]", '1', "$value[3]", '1', "Active")."<br />";
      $myelements[0] .= PHPWS_Form::formTextArea("$key","$value[2]",6,40)."</tr>";
    }
    $myelements[0] .= "<tr colspan=\"2\"><td align=\"center\"><a href=\"index.php?module=help&amp;help_op=active_on&amp;help_on=1&amp;reg_id=$this->reg_id\">".$_SESSION["translate"]->it("Activate All")."</a> | <a href=\"index.php?module=help&amp;help_op=active_on&amp;help_on=0&amp;reg_id=$this->reg_id\">".$_SESSION["translate"]->it("Deactivate All")."</a></td></tr>";
    $myelements[0] .= "</table>";
    $myelements[0] .= "<br />" . PHPWS_Form::formSubmit($_SESSION["translate"]->it("Save Data"), "help_op[save_data]");
    $content = PHPWS_Form::makeForm("HELP_edit_menu", "index.php", $myelements, "post", 0, 1);
    $_SESSION["OBJ_layout"]->popbox($title, $content, NULL, "CNT_help") . "<br />";
  }



  /**
   * Saves the content for help after editing
   *
   * @author Jeremy Agee <jagee@NOSPAM.tux.appstate.edu>
   * @return none
   */
  function save_help() {
    foreach($_POST as $key=>$value)
      if(is_numeric($key))
	$GLOBALS["core"]->sqlUpdate(array("content"=>$value), "mod_help", "help_id", $key);
    if($_POST["active"]) {
      $GLOBALS["core"]->sqlUpdate(array("active"=>"0"), "mod_help", "reg_id", $_POST["reg_id"]);
      foreach($_POST["active"] as $key=>$value)
	$GLOBALS["core"]->sqlUpdate(array("active"=>$value), "mod_help", "help_id", $key);
    }
     else
       $GLOBALS["core"]->sqlUpdate(array("active"=>"0"), "mod_help", "reg_id", $_POST["reg_id"]);
    $this->main_menu();
  }


  /**
   * activate/deactivate help items
   *
   * @author Jeremy Agee <jagee@NOSPAM.tux.appstate.edu>
   * @return none
   */
  function active_help_set() {
    if($_GET["help_on"])
      $GLOBALS["core"]->sqlUpdate(array("active"=>"1"), "mod_help", "reg_id", $_GET["reg_id"]);
    else
      $GLOBALS["core"]->sqlUpdate(array("active"=>"0"), "mod_help", "reg_id", $_GET["reg_id"]);

    $this->main_menu();
  }

  /**
   * help menu for activating modules
   *
   * @author Jeremy Agee <jagee@NOSPAM.tux.appstate.edu>
   * @return none
   */
  function act_deact_module() {
    $title = $_SESSION["translate"]->it("Edit module");
    $user_text = $_SESSION["translate"]->it("Activate/Deactivate the help data below:<br />");
    $myelements[0] = "<table border=\"0\" cellpading=\"2\" cellspacing=\"2\" bgcolor=\"FFFFFF\">";
    $myelements[0] .= PHPWS_Form::formHidden("module", "help") . $user_text;
    $myelements[0] .= PHPWS_Form::formHidden("help_op", "save_act_deact");
    $highlight = NULL;
    $result = $GLOBALS["core"]->sqlSelect("mod_help_reg");
    foreach($result as $value) {
      if($highlight)
	$myelements[0] .= "<tr class=\"bg_medium\">";
      else
	$myelements[0] .= "<tr class=\"bg_light\">";
      
      $myelements[0] .= "<td>" . $value["mod_name"] . "</td><td>" . PHPWS_Form::formRadio($value["reg_id"], '1', "1", $value["active"], "On" ) . PHPWS_Form::formRadio($value["reg_id"], '0', "0", $value["active"], "Off" ) . "</td>";
      PHPWS_WizardBag::toggle($highlight);
    }
    $myelements[0] .= "</tr></table>";
    $myelements[0] .= PHPWS_Form::formSubmit($_SESSION["translate"]->it("Set Active"), "bla");
    $content = PHPWS_Form::makeForm("HELP_act_deact", "index.php", $myelements, "post", 0, 1);
    $_SESSION["OBJ_layout"]->popbox($title, $content, NULL, "CNT_help") . "<br />";
  }

  /**
   * saves moduel active status.
   *
   * @author Jeremy Agee <jagee@NOSPAM.tux.appstate.edu>
   * @return none
   */
  function save_act_deact_module() {
    
    foreach($_POST as $key=>$value)
    if(is_numeric($key))
      $GLOBALS["core"]->sqlUpdate(array("active"=>$value), "mod_help_reg", "reg_id", $key);
    $this->main_menu();
  }
  
  /**
   * Shows the popwindow link in amy module that calls help
   *
   * @author Jeremy Agee <jagee@NOSPAM.tux.appstate.edu>
   * @param string module_name : name of the module you need the help item from
   * @param string label_name_id : This is the individual id the auther uses to access the help item
   * @return none
   */
  function show_link($module_name, $label_name_id)
  {
    $sql = "select reg_id from mod_help_reg where mod_name = '$module_name' and active = '1';";    
    $row_reg = $GLOBALS["core"]->quickFetch($sql, TRUE);
    if($row_reg) {
      $sql = "select * from mod_help where reg_id = '".$row_reg["reg_id"]."' and label_name_id = '$label_name_id';";

      $row_h = $GLOBALS["core"]->quickFetch($sql, TRUE);
      if (!isset($row_h) || $row_h['label_name_id'] != $label_name_id){
	echo("show_link could not locate a help label '<b>$label_name_id</b>' for the '<b>$module_name</b>' module.");
	return NULL;
      }
      if($row_h["active"] == 1) {
	include(PHPWS_SOURCE_DIR."mod/help/conf/help_config.php");
	if($_SESSION["OBJ_user"]->js_on) {
	  $window_array = array(
				"type"=>"link",
				"url"=>"./index.php?module=help&amp;help_op=show_help&amp;module_name=$module_name&amp;label_name_id=$label_name_id&amp;hreg_id=".$row_reg["reg_id"]."&amp;lay_quiet=1",
				"label"=>"$help_graphic",
				"window_title"=>"help",
				"scrollbars"=>"yes",
				"width"=>"400",
				"height"=>"300",
				"toolbar"=>"no"
				);
	}
	else {
	  return "<a href=\"./index.php?module=help&amp;help_op=show_help&amp;module_name=$module_name&amp;label_name_id=$label_name_id&amp;hreg_id=".$row_reg["reg_id"]."&amp;lay_quiet=1\" target=\"_blank\">$help_graphic</a>";
	}
	$help_link = PHPWS_WizardBag::js_insert("window", NULL, NULL, NULL, $window_array);
	return $help_link;
      }
    }
  }


  /**
   * Shows the popwindow link in amy module that calls help
   *
   * @author Jeremy Agee <jagee@NOSPAM.tux.appstate.edu>
   * @param string label_name_id : This is the individual id the auther uses to access the help item
   * @return none
   */
  function show_popup($label_name_id) {
      $tag_array = array();
      $tag_array["HELP_TITLE"] = $_SESSION['translate']->it("Help System");
	  
    foreach($this->help_content as $key=>$value)
      if($value[1] == $label_name_id) {
	if ($result = $_SESSION["translate"]->dyn("mod_help", $key)){
	  $tag_array["LABEL"]=$result["label"];
	  $tag_array["CONTENT"]=PHPWS_Text::breaker($result["content"]);
	  $tag_array["STYLESHEET"]="./".$_SESSION["OBJ_layout"]->theme_address."style.css";
	} else {
	  $tag_array["LABEL"]=$value[0];
	  $tag_array["CONTENT"]=PHPWS_Text::breaker($value[2]);
	  $tag_array["STYLESHEET"]="./".$_SESSION["OBJ_layout"]->theme_address."style.css";
	}
	if($_SESSION["OBJ_user"]->js_on)
	  $tag_array["CONTENT"] .= "<br /><br /><div align=\"center\"><input type=\"button\" name=\"close\" value=\"".$_SESSION["translate"]->it("Close this Window")."\" onclick=\"parent.close()\"></div>";
      }

    $tag_array['CHARSET'] = $_SESSION['OBJ_layout']->meta_content;

    echo PHPWS_Template::processTemplate($tag_array, "help", "default.tpl");
  }


  /**
   * This function is for the module programmers to help install help items.
   *
   * @author Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @param string mod_name : Defines the module name to install the help items for your module
   * @return none
   */
  function setup_help($mod_name, $helpFile = NULL) {
    if($helpFile === NULL) {
      if(file_exists(PHPWS_SOURCE_DIR . "mod/" . $mod_name . "/conf/help.php"))
        $helpFile = PHPWS_SOURCE_DIR . "mod/" . $mod_name . "/conf/help.php";
      else
        return FALSE;
    } elseif(!file_exists($helpFile)) {
	return FALSE;
    }

    $reg_data["mod_name"] = $mod_name;
    $reg_data["active"] = 1;

    if(!$reg_id = $GLOBALS["core"]->sqlInsert($reg_data, "mod_help_reg", TRUE, 1)) {
      $sql_result = $GLOBALS["core"]->sqlSelect("mod_help_reg", "mod_name", $mod_name);
      $reg_id = $sql_result[0]["reg_id"];
    }

    $string = PHPWS_File::readFile($helpFile);

    $array = array();
    $token = strtok($string, "\$");

    while($token) {
      $temp = explode("=", $token);
      if(isset($temp[0]) && isset($temp[1])) {
	$temp2 = explode("_content", $temp[0]);
	array_push($array, trim($temp2[0]));
      }
      $token = strtok("\$");
    }
    $array = array_unique($array);

    include($helpFile);

    foreach($array as $var_name) {
      $content = $var_name . "_content";
      $help_data["label_name_id"] = $var_name;
      $help_data["label"] = $$var_name;
      $help_data["reg_id"] = $reg_id;
      $help_data["content"] = $$content;
      $help_data["active"] = 1;

      if($id = $GLOBALS["core"]->sqlInsert($help_data, "mod_help", TRUE, TRUE))
	$_SESSION["translate"]->registerDyn("mod_help", $id);
    }
    return TRUE;
  }

  /**
   * for module programmers who need to uninstall help.
   *
   * @author Jeremy Agee <jagee@NOSPAM.tux.appstate.edu>
   * @param string modname: This is the module name to uninstall
   * @return none
   */
  function uninstall_help($modname) {
    $result = $GLOBALS["core"]->sqlSelect("mod_help_reg", "mod_name", $modname);
    $reg_id = NULL;
    if($result)
      foreach($result as $value)
	$reg_id = $value["reg_id"];

    $GLOBALS["core"]->sqlDelete("mod_help_reg", "reg_id", $reg_id);
    $GLOBALS["core"]->sqlDelete("mod_help", "reg_id", $reg_id);
  }

  /**
   * Conformation message about reloading help data.
   *
   * @author Jeremy Agee <jagee@NOSPAM.tux.appstate.edu>
   * @param string reg_id: The module reg_id to reload
   * @return none
   */
  function reload_conf($reg_id) {
    $result = $GLOBALS["core"]->sqlSelect("mod_help_reg", "reg_id", $reg_id);
    if($result)
      foreach($result as $value)
	$modname = $value["mod_name"];
    $title = $_SESSION["translate"]->it("Reload Help Item");
    $content = $_SESSION["translate"]->it("Are you sure you want to reload the [var1] item", $modname) . "?"
      . " <a href=\"index.php?module=help&amp;help_op=reload_conf&amp;mod_name=$modname\">"
      . $_SESSION["translate"]->it("Yes")."</a> | <a href=\"index.php?module=help&amp;help_op=main_menu\">".$_SESSION["translate"]->it("No")."</a>";

    $_SESSION["OBJ_layout"]->popbox($title, $content, NULL, "CNT_help") . "<br />";
  }

  /**
   * Reloads help info from a module.
   *
   * @author Jeremy Agee <jagee@NOSPAM.tux.appstate.edu>
   * @param string modname: This is the module name to reload
   * @return none
   */
  function reload_help($modname) {
    $this->uninstall_help($modname);
    $this->setup_help($modname);
    $this->main_menu();
  }
}

?>