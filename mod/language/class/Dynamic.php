<?php

require_once (PHPWS_SOURCE_DIR . "core/Form.php");

require_once (PHPWS_SOURCE_DIR . "core/Text.php");

require_once (PHPWS_SOURCE_DIR . "core/Array.php");

require_once (PHPWS_SOURCE_DIR . "mod/help/class/CLS_help.php");

/**
 * Controls the dynamic language component of the language module
 *
 * @package  phpWebSite
 * @module   language
 * @author   Matthew McNaney <matt@NOSPAMtux.appstate.edu>
 * @version $Id: Dynamic.php,v 1.19 2004/04/23 19:09:02 darren Exp $
 */
class PHPWS_Dynamic {

  /**
   * Creates a dynamic language table in the database
   *
   * The table created contains all the dynamic translations for its language
   * 
   * @package  phpWebSite
   * @module   language
   * @author   Matthew McNaney <matt@NOSPAMtux.appstate.edu>
   * @param    string   Two character language abbrevation
   * @return   boolean  True if successful, False otherwise.
   * @access   public
   */
  function createDynamic($language){
    $regular_languages = $this->getRegularLanguages();

    if (!$regular_languages[$language])
      exit("Cannot create dynamic language table without a matching regular language table");

    $table_name = "mod_dynamic_".strtolower($language);

    if (!preg_match("/[A-Za-z]{2}/", $language))
      return FALSE;

    if ($GLOBALS["core"]->sqlTableExists($table_name, 1))
      return FALSE;
    $columns["dyn_id"]       =  "int unsigned NOT NULL, PRIMARY KEY  (dyn_id)";
    $columns["table_name"]   =  "varchar(50) binary NOT NULL default '',  KEY table_name (table_name)";
    $columns["id"]           =  "VARCHAR( 50 ) NOT NULL";
    $columns["data_column"]  =  "VARCHAR( 50 ) NOT NULL";
    $columns["translation"]  =  "TEXT";
    $columns["last_hash"]    =  "VARCHAR( 32 ) BINARY default NULL";
    $columns["last_updated"] =  "timestamp NOT NULL";
    $columns["active"]       =  "TINYINT DEFAULT '0' NOT NULL";
    $columns["updated"]      =  "TINYINT DEFAULT '0' NOT NULL";

    return $GLOBALS["core"]->sqlCreateTable($table_name, $columns);
  }

  /**
   * Registers an module to be translated
   *
   * @package  phpWebSite
   * @module   languageindex.php
   * @author   Matthew McNaney <matt@NOSPAMtux.appstate.edu>
   * @param    string   Name of module that the element is registered to.
   * @param    string   The id address of the original element
   * @return   boolean  True if successful, False otherwise.
   * @access   public
   */
  function registerModule($module_name, $table_name, $id_column, $data_columns){
    if (!PHPWS_Text::isValidInput($table_name) || !PHPWS_Text::isValidInput($id_column))
      return FALSE;
    else {
      $insert["module_name"] = strip_tags($module_name);
      $insert["table_name"] = $table_name;
      $insert["id_column"] = $id_column;
      if (is_array($data_columns))
	$insert["data_columns"] = implode(":", $data_columns);
      else
	$insert["data_columns"] = $data_columns;

       return $GLOBALS["core"]->sqlInsert($insert, "mod_dyn_modules", 1);
    }
  }

  function unregisterModule($module){
    $GLOBALS["core"]->sqlDelete("mod_dyn_modules", "module_name", $module);
  }


 function registerDyn($table_name, $id, $language=NULL){
    if (!($GLOBALS["core"]->sqlTableExists("mod_dyn_modules", 1)))
      return NULL;
    
    if (!($row = $GLOBALS["core"]->sqlSelect("mod_dyn_modules", "table_name", $table_name)))
      return NULL;

    return $this->createTranslation($table_name, $id, $language);
    
  }


  function getDynamicLanguages(){
    $sql = $GLOBALS["core"]->listTables();
    foreach ($sql as $table){
      if (preg_match("/mod_dynamic_\w\w$/", $table)){
	$abbrev = strtolower(substr($table, -2));
	$languages[$abbrev] = PHPWS_Language::fullname($abbrev);
      }
    }
    if (isset($languages))
      return $languages;
    else
      return NULL;
  }

  function loadModule($table_name){
    $mod_info["table_name"] = $select["table_name"]  = $table_name;

    if ($row = $GLOBALS["core"]->sqlSelect("mod_dyn_modules", $select)){
      $mod_info["module_name"]  = stripslashes($row[0]["module_name"]);
      $mod_info["id_column"]    = $row[0]["id_column"];
      $mod_info["data_columns"] = explode(":", $row[0]["data_columns"]);
      return $mod_info;
    } else
      return FALSE;
  }

  /**
   * Registers an element to be translated
   *
   * @package  phpWebSite
   * @module   language
   * @author   Matthew McNaney <matt@NOSPAMtux.appstate.edu>
   * @param    string   Name of module or process that the element is registered to.
   * @param    string   The id address of the original element
   * @return   boolean  True if successful, False otherwise.
   * @access   public
   */
  function createTranslation($table_name, $id, $language=NULL){
    $error_free = TRUE;

    if (!PHPWS_Text::isValidInput($table_name))
      exit("Error: createTranslation received an invalid table_name");

    if (!($module_info = $this->loadModule($table_name)))
      exit("Error in: createTranslation. No registration for table <b>$table_name</b>");	

    $insert["table_name"] = $table_name;
    $insert["id"]         = $id;

    if ($language)
      $dynamic_languages[$language] = $this->fullname($language);
    else
      $dynamic_languages = $this->getDynamicLanguages();

    if ($dynamic_languages){
      foreach ($dynamic_languages as $abbr=>$language){
	if ($error_free){
	  foreach ($module_info["data_columns"] as $column_name){
	    $insert["data_column"] = $column_name;
	    $error_free = $GLOBALS["core"]->sqlInsert($insert, $this->dynamicTable($abbr), 1);
	  }
	}
	else
	  continue;
      }
    }
    return $error_free;
  }

  function dropDynamicLanguage($language){
    if (!($GLOBALS["core"]->sqlTableExists($this->dynamicTable($language), 1)))
      exit("Error in dropDynamicLanguage: Dynamic language file does not exist");
    
    $GLOBALS["core"]->sqlDropTable($this->dynamicTable($language));
  }

  function getUpdated($language, $table_name=NULL){
    if ($table_name && PHPWS_Text::isValidInput($table_name))
      $select["table_name"] = $table_name;

    $select["updated"] = 1;

    if ($GLOBALS["core"]->sqlTableExists($this->dynamicTable($language), 1)){
      if ($row = $GLOBALS["core"]->sqlSelect($this->dynamicTable($language), $select, NULL, array("table_name", "id", "last_updated")))
	return $row;
      else 
	return 0;
    } else
      return FALSE;
  }

  function getUntranslated($language, $table_name=NULL){
    if ($table_name && PHPWS_Text::isValidInput($table_name))
      $select["table_name"] = $table_name;

    $select["last_hash"] = NULL;

    $compare["last_hash"] = "is";
    if ($GLOBALS["core"]->sqlTableExists($this->dynamicTable($language), 1)){
      if ($row = $GLOBALS["core"]->sqlSelect($this->dynamicTable($language), $select, NULL, array("table_name", "id", "last_updated"), $compare))
	return $row;
      else 
	return 0;
    } else
      return FALSE;
  }

  function getTranslationOriginal($language, $table_name, $original_id){
    return $GLOBALS["core"]->sqlSelect($this->dynamicTable($language), array("id"=>$original_id, "table_name"=>$table_name), NULL, "dyn_id");
  }

  function getAllTranslations($language, $translation_id){
    return $GLOBALS["core"]->sqlSelect("mod_dynamic_".strtolower($language));
  }

  function getOriginal($table_name, $original_id){
   $row = $GLOBALS["core"]->sqlSelect($table_name,$this->getOriginalIDColumn($table_name), $original_id);
   return $row[0];
  }

  function getOriginalHash($table_name, $original_id, $column_name){
    if (!$GLOBALS["core"]->sqlTableExists($table_name)){
      $this->dynDrop($table_name, $original_id);
      return NULL;
    }

    if (!($row = $GLOBALS["core"]->sqlSelect($table_name,$this->getOriginalIDColumn($table_name), $original_id))){
      $this->dynDrop($table_name, $original_id);
      return NULL;
    }

    return md5($row[0][$column_name]);
  }

  function getOriginalIDColumn($table_name=NULL){
    $registered = $this->getRegistered($table_name);
    return $registered[0]["id_column"];
  }

  /**
   * Returns an array of modules currently registered to the Dynamic library
   * @author Matthew McNaney
   */
  function getRegistered($table_name=NULL){
    $select = NULL;
    if ($table_name)
      $select["table_name"] = $table_name;

    if (!($row = $GLOBALS["core"]->sqlSelect("mod_dyn_modules", $select)))
      return NULL;
    $temp = $row;
    $count = 0;
    foreach ($temp as $tempRow){
      $temp[$count] = $tempRow;
      $temp[$count]["data_columns"] = explode(":", $tempRow["data_columns"]);
      $count++;
    }
    return $temp;
  }


  function viewTranslateList($language, $listmode, $table_name){
    $content = NULL;
    $big_loop = 0;

    $reg_row = $this->getRegistered($table_name);

    $module_name = $reg_row[0]["module_name"];

    $content .= 
       "\n<form action=\"index.php\" method=\"post\">\n"
       .PHPWS_Form::formHidden(array("module"=>"language", "lng_adm_op"=>"updateDynamicTranslationsForm", "language"=>$language, "listmode"=>$listmode, "table_name"=>$table_name));
    
    if ($listmode == "untranslated"){
      $GLOBALS["CNT_lang"]["title"] = $_SESSION["translate"]->it("Untranslated Dynamic Sections").": ".$this->fullname($language);
      if (!($translateList = $this->getUntranslated($language, $table_name))){
	$GLOBALS["CNT_lang"]["content"] =  $this->link_to_admin() ."<br /><br />" . $_SESSION["translate"]->it("This module does not contain untranslated phrases").".";
	return;
      }
    } elseif ($listmode == "updated") {
      $GLOBALS["CNT_lang"]["title"] = $_SESSION["translate"]->it("Updated Dynamic Sections").": ".$this->fullname($language);
      if (!($translateList = $this->getUpdated($language, $table_name))){
	$GLOBALS["CNT_lang"]["content"] = $this->link_to_admin() ."<br /><br />" . $_SESSION["translate"]->it("This module does not contain updated phrases").".";
	return;
      }
    }

    $GLOBALS["CNT_lang"]["title"] .= CLS_help::show_link("language", "dynForm");

    foreach ($translateList as $section)
      $translateArray[$section["id"]][] = $section;
    $count = 0;
    foreach ($translateArray as $original_id=>$original_info){
      $count++;	
      $loop = 0;
      $rowTemp["ID"] = $original_id;
      $rowTemp["RADIO"] = PHPWS_Form::formRadio("original_translation_id", $original_id); 

      $original_content = $this->getOriginal($table_name, $original_id);

      $updated = $col_content = $column = NULL;
      foreach ($original_info as $row){
	if ($loop){
	  $column .= "\n<hr />\n";
	  $updated .= "\n<hr />\n";
	  $col_content .= "\n<hr />\n";
	}
	$column .= $row["data_column"];
	
	$temp_content = $original_content[$row["data_column"]];
	
	if (strlen($temp_content) > 80)
	  $temp_content = substr($temp_content, 0, 80)."...";
	
	$col_content .= $temp_content;
	
	$date = $GLOBALS["core"]->datetime->date($row["last_updated"], 1);
	$updated .= $date["full"]." ".$date["time"];
	$loop = 1;
      }
	$original_info = NULL;
      
      $rowTemp["COLUMNS"] = $column;
      $rowTemp["INFO"] = strip_tags($col_content, "<hr>");
      $rowTemp["UPDATED"] = $updated;

      if ($count%2)
	$rowTemp["HIGHLIGHT"] = "class=\"bg_light\"";
      else
	$rowTemp["HIGHLIGHT"] = "class=\"white\"";

      $dynRows[] = PHPWS_Template::processTemplate($rowTemp, "language", "dynRow.tpl");
      $rowTemp = $original_id = $updated = $column = $col_content = NULL;
    }

    $data = PHPWS_Array::paginateDataArray($dynRows, "index.php?module=language&amp;lng_adm_op=".$_REQUEST["lng_adm_op"]."&amp;language=$language&amp;table_name=".$table_name, 10, 1, array("<b>[ ", " ]</b>"), NULL, 10);    
    $finalTemp["DYNROWS"]       = $data[0];
    $finalTemp["PAGES"]         = $data[1];
    $finalTemp["COUNT"]         = $data[2];

    $finalTemp["ID_DESC"]       = $_SESSION["translate"]->it("ID");
    $finalTemp["RADIO_DESC"]    = $_SESSION["translate"]->it("Pick");
    $finalTemp["COLUMNS_DESC"]  = $_SESSION["translate"]->it("Column");
    $finalTemp["INFO_DESC"]     = $_SESSION["translate"]->it("Phrase");
    $finalTemp["UPDATED_DESC"]  = $_SESSION["translate"]->it("Updated");
    $finalTemp["UPDATE_BUTTON"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Update [var1]", $module_name));
    $finalTemp["MODULE_NAME"]   = "Module: $module_name";
    $finalTemp["TABLE_NAME"]    = "Table: $table_name";
    $content .= PHPWS_Template::processTemplate($finalTemp, "language", "dynList.tpl");

    $GLOBALS["CNT_lang"]["content"] = $this->link_to_admin()."<br />". $content;

  }


  function addDynamicModules($language){
    if (!($registered_mods = $this->getRegistered()))
      return FALSE;

    foreach ($registered_mods as $mod){
      if (!($originals = $GLOBALS["core"]->sqlSelect($mod["table_name"])))
	continue;
      foreach ($originals as $orig_row){
	$id = $orig_row[$mod["id_column"]];
	$this->createTranslation($mod["table_name"], $id, $language);
      }
    }
    return TRUE;
  }

  function updateDynamicTranslationAction(){
    $status = TRUE;

    // extract should pull $language, $table_name, $dynamic_translation, $original_translation_id, $data_column
    extract($_POST);
    $original_data = $this->getOriginal($table_name, $original_translation_id);
    if (!($dynamic_translation))
      return FALSE;
    $original_data = $this->getOriginal($table_name, $original_translation_id);
    foreach ($dynamic_translation as $dyn_id=>$translation){
      if (!$status)
	continue;
      $translation = PHPWS_Text::parseInput($translation);
      $update["translation"] = $translation;
      $update["last_hash"] = md5($original_data[$data_column[$dyn_id]]);
      $update["updated"] = 0;
      $update["active"] = 1;
      $status = $GLOBALS["core"]->sqlUpdate($update, $this->dynamicTable($language), "dyn_id", $dyn_id);
    }
    return $status;
    
  }

  function dynDrop($table_name, $id){
    if (!($languages = $this->getDynamicLanguages()))
      return;

    $where["table_name"] = $table_name;
    $where["id"]         = $id;
	 
    foreach ($languages as $abbr=>$fullname)
      $GLOBALS["core"]->sqlDelete($this->dynamicTable($abbr), $where);
  }

  function dynUpdate($table_name, $id){
    if (!($languages = $this->getDynamicLanguages()))
      return;

    $update["updated"]   = 1;
    $match["table_name"] = $table_name;
    $match["id"]         = $id;
	 
    foreach ($languages as $abbr=>$fullname)
      $GLOBALS["core"]->sqlUpdate($update, $this->dynamicTable($abbr), $match);
  }

  function getDynamic($table_name, $id, $language=NULL){
    if (!$language)
      $language = $this->current_language;

    $select["table_name"] = $table_name;
    $select["id"] = $id;
    $select["active"] = 1;

    if (!($row = $GLOBALS["core"]->sqlSelect($this->dynamicTable($language), $select)))
      return NULL;

    foreach ($row as $info)
      $translation[$info["data_column"]] = stripslashes($info["translation"]);

    return $translation;

  }

  function updateDynamicTranslationsForm($listmode){
    extract($_POST);
    $content = NULL;

    if (!isset($original_translation_id))
      $_SESSION["OBJ_lang"]->force_to_admin("lng_adm_op=viewUntranslatedList&amp;language=" . $language . "&table_name=" . $table_name);

    $translation_rows = $this->getTranslationOriginal($language, $table_name, $original_translation_id);
    $original_data = $this->getOriginal($table_name, $original_translation_id);

    $content .= 
       "<form action=\"index.php\" method=\"post\">"
       . PHPWS_Form::formHidden(array("module"=>"language",
				   "lng_adm_op"=>"updateDynamicTranslationAction",
				   "language"=>$language, 
				   "table_name"=>$table_name,
				   "listmode"=>$listmode,
				   "original_translation_id"=>$original_translation_id));      

    foreach ($translation_rows as $text_fields){
      $box = NULL;
      $original_text = stripslashes($original_data[$text_fields["data_column"]]);

      if (empty($text_fields["translation"]))
	$translation = $original_text;
      else $translation = $text_fields["translation"];

      $dyn_id = $text_fields["dyn_id"];
      $content .= PHPWS_Form::formHidden("data_column[$dyn_id]", $text_fields["data_column"]);
      $box .= PHPWS_Text::breaker($original_text) . "<br />";
      if (strlen($original_text) < 30)
	$box .= PHPWS_Form::formTextField("dynamic_translation[$dyn_id]", $translation, 30);
      else
	$box .= PHPWS_Form::formTextArea("dynamic_translation[$dyn_id]", $translation, 10, 70);

      $content .= $_SESSION["OBJ_layout"]->popbox($_SESSION["translate"]->it("Column Name").": ".$text_fields["data_column"], $box)."<br />";

    }

      $content .=  "<br /><br />".PHPWS_Form::formSubmit($_SESSION["translate"]->it("Update")) .
       "</form>";

    $GLOBALS["CNT_lang"]["title"] = $_SESSION["translate"]->it("Update Dynamic Translation for [var1]", $this->fullname($language)) . CLS_help::show_link("language", "updateDyn");
    $GLOBALS["CNT_lang"]["content"] = $this->back_to_list($language, $listmode, $table_name) . "<br /><br />" . $content;
 
}

  function back_to_list($language, $listmode, $table_name){
    if ($listmode == "untranslated")
      $lng_op = "viewUntranslatedList";
    else
      $lng_op =  "viewUpdatedList";
    
    $values = array ("module"=>"language", "lng_adm_op"=>$lng_op, "language"=>$language, "table_name"=>$table_name);

    return PHPWS_Text::link("index.php", $_SESSION["translate"]->it("Back to List"), "index", $values);
  }

  function refreshDynamic($language, $table_name){
    $where["last_hash"] = NULL;
    $where["table_name"] = $table_name;

    $compare["last_hash"] = "is not";
    if(!($update_list = $GLOBALS["core"]->sqlSelect($this->dynamicTable($language), $where, NULL, NULL, $compare,null,null,1)))
      return;

    foreach ($update_list as $update){
      extract($update);
      if (!($original_hash = $this->getOriginalHash($table_name, $id, $data_column)))
	return;

      $update_where["dyn_id"] = $dyn_id;

      if ($original_hash != $last_hash)
	$update_value["updated"] = 1;
      else
	$update_value["updated"] = 0;

      $GLOBALS["core"]->sqlUpdate($update_value, $this->dynamicTable($language), $update_where);
    }
  }


  function countUntranslated($language, $modTable=NULL){
    if($row = $this->getUntranslated($language, $modTable))
      return count($row);
    else
      return 0;
  }

  function countUpdated($language, $modTable=NULL){
    $row = $this->getUpdated($language, $modTable);
    if (is_array($row))
      return count($row);
    else
      return 0;
  }

  function dynamicTable($abbr){
    return "mod_dynamic_".strtolower($abbr);
  }

  // End of PHPWS_Dynamic class
}
?>