<?php

require_once (PHPWS_SOURCE_DIR . "core/Form.php");

require_once (PHPWS_SOURCE_DIR . "core/Text.php");

require_once (PHPWS_SOURCE_DIR . "core/File.php");

require_once (PHPWS_SOURCE_DIR . "core/Array.php");

require_once (PHPWS_SOURCE_DIR."mod/language/class/Dynamic.php");

require_once (PHPWS_SOURCE_DIR."mod/help/class/CLS_help.php");

class PHPWS_Language extends PHPWS_Dynamic{
  
  var $dictionary;
  var $indexed_dictionary;
  var $current_language;
  var $langActive;
  var $dynActive;
  var $ignoreDefault;
  var $mark;
  var $auto_up;
  var $default_language;
  var $last_search;
  var $available_languages;
  var $phrases;


  /**
   * Loads the default settings into the language object
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function loadSettings(){
    if (!(list($settings) =  $GLOBALS["core"]->sqlSelect("mod_language_settings")))
      exit("ERROR: Cannot load language settings.");

    extract($settings);
    $this->langActive = $langActive;
    $this->dynActive = $dynActive;
    $this->ignoreDefault = $ignoreDefault;
    $this->mark = $mark;
    $this->auto_up = $auto_up;
    $this->default_language = $default_language;
    $this->current_language = $default_language;
   }

  /**
   * Updates the default language settings from the admin screen
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function updateLanguageSettings(){
    $update["dynActive"] = (int)$_POST["dynActive_switch"];
    $update["langActive"] = (int)$_POST["langActive_switch"];
    $update["ignoreDefault"] = (int)$_POST["ignoreDefault_switch"];
    $update["mark"] = (int)$_POST["mark_switch"];
    $update["auto_up"] = (int)$_POST["auto_up_switch"];
    $result = $GLOBALS["core"]->sqlUpdate($update, "mod_language_settings");
    
    $_SESSION["OBJ_lang"]->loadSettings();
    $_SESSION["translate"]->loadSettings();

    return $result;
  }

  /**
   * Grabs the language.txt file and returns the array of entries
   *
   * The language file contains a listing of many languages and their two 
   * letter abbreviation.
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function getLanguageFile(){
    return file(PHPWS_SOURCE_DIR."mod/language/languages.txt");
  }


  /**
   * Parses the language file array for use by the system
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function getCodeArray(){
    $code_file = PHPWS_Language::getLanguageFile();

    foreach ($code_file as $languages){
      $temp = explode(":", $languages);
      $code_array[$temp[0]] = trim($temp[1]);
    }
    natcasesort($code_array);
    return $code_array;
  }

  /**
   * Returns the fullname of a language abbreviation
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function fullname($language_abbr){
    $code_array = PHPWS_Language::getCodeArray();
    return $code_array[$language_abbr];
  }

  /**
   * Returns the table name of a language abbreviation
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function tableName($language_abbr){
    return "mod_lang_".strtolower($language_abbr);
  }

  /**
   * Returns an array of languages based on the current language tables
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function getRegularLanguages(){
    $sql = $GLOBALS["core"]->listTables();
    if (empty($sql))
      return NULL;

    foreach ($sql as $table){
      if (preg_match("/" . $GLOBALS["core"]->getTablePrefix() . "mod_lang_\w\w$/", $table)){
	$abbrev = strtolower(substr($table, -2));
	$languages[$abbrev] = PHPWS_Language::fullname($abbrev);
      }
    }
    
    return $languages;
  }


  /**
   * Puts requested phrases and translations into the "dictionary" variable
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function createDictionary($match="loaded_mods", $find=NULL, $indexed=NULL, $language=NULL){
    $this->indexed_dictionary = $this->dictionary = $this->phrases = NULL;

    if (!is_null($language))
      $this->current_language = $language;
    
    if (!$this->current_language){
      $this->current_language = $this->default_language;
    }

    $lang_table = $this->tableName($this->current_language);
    
    switch ($match){
    case "phrase":
      $match_col = "phrase";
    $result = $GLOBALS["core"]->sqlSelect($lang_table, "phrase", $find, "phrase", "regexp"); 
    if ($result){
      foreach ($result as $value){
	extract($value);
	$indexed_dictionary[$phrase_id] = array("module"=>$module, "phrase"=>$phrase, "translation"=>$translation);       
      }
    }
    break;

    case "translation":
      $match_col = "translation";
    $result = $GLOBALS["core"]->sqlSelect($lang_table, "translation", $find, "translation", "regexp"); 
    if ($result){
      foreach ($result as $value){
	extract($value);
	$indexed_dictionary[$phrase_id] = array("module"=>$module, "phrase"=>$phrase, "translation"=>$translation);       
      }
    }
    break;

    case "missing":
      $result = $GLOBALS["core"]->sqlSelect($lang_table, "translation", "\?%", array("module", "phrase"), "like");

    if ($result){
      foreach ($result as $value){
	extract($value);
	$translation = substr($translation, 1);
	$indexed_dictionary[$phrase_id] = array("module"=>$module, "phrase"=>$phrase, "translation"=>$translation);       
      }
    }
    break;

    case "all_mods":
      $match_col = "module";
    $result = $GLOBALS["core"]->sqlSelect($lang_table); 
    if ($result){
      foreach ($result as $value){
	extract($value);
	$indexed_dictionary[$phrase_id] = array("module"=>$module, "phrase"=>$phrase, "translation"=>$translation);       
      }
    }
    break;

    case "one_mod":
      $match_col = "module";
    $result = $GLOBALS["core"]->sqlSelect($lang_table, "module", $find); 
    if ($result){
      foreach ($result as $value){
	extract($value);
	$indexed_dictionary[$phrase_id] = array("module"=>$module, "phrase"=>$phrase, "translation"=>$translation);       
      }
    }
    break;
    
    case "loaded_mods":
      $match_col = "module";
      $mod_list = $GLOBALS["core"]->mods_loaded;
    $mod_list[] = "core";
    foreach ($mod_list as $mod_title){
      $result = $GLOBALS["core"]->sqlSelect($lang_table, "module", $mod_title); 
      if ($result){
	foreach ($result as $value){
	  extract($value);
	  $indexed_dictionary[$phrase_id] = array("module"=>$module, "phrase"=>$phrase, "translation"=>$translation);       
	}
      }
    }
    break;
    }


    if (isset($indexed_dictionary)){
      if ($indexed)
	$this->indexed_dictionary = $indexed_dictionary;
      else {
	unset($this->dictionary);
	foreach ($indexed_dictionary as $info){
	  extract($info);
	  $this->dictionary[$phrase] = $translation;
	}
      }
    }
  }


  /**
   * Exports a language text file for import in phpwebsite
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function export($language, $destination, $module="all"){
    $finalFile = NULL;
    $lang_table = $this->tableName($language);

    if ($module=="all")
      $result = $GLOBALS["core"]->sqlSelect($lang_table);
    else
      $result = $GLOBALS["core"]->sqlSelect($lang_table, "module", $module);
    $header = $language.":|:".$module."\n";

    if ($result){
      foreach($result as $row){
	if (!preg_match("/^\?/", $row["translation"]) && !isset($trackPhrases[$row['phrase']])){
	  $file[] = "a:|:".$row["phrase"].":|:".$row["translation"]."\n";
	  $trackPhrases[$row['phrase']] = 1;
	}
      }

      if (!is_array($file))
	return FALSE;

      sort($file);
      array_unshift($file, $header);
      foreach ($file as $phrases)
	$finalFile .= $phrases;

      if ($module != "all"){
	if (PHPWS_File::writeFile($destination, $finalFile, 1, 1)){
	  return TRUE;
	}
	else
	  return FALSE;
      }
    } else
      return FALSE;
  }


  function quickDict($filename){
    if ($language_array = file($filename)){
      foreach ($language_array as $translation){
	if (!preg_match("/^[#\/]/", $translation)){
	  $phrase = explode(":|:", $translation);
	  $this->dictionary[trim($phrase[0])] = trim($phrase[1]);
	}
      }
    }
  }

  /**
   * Imports a language file into phpwebsite
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function import($filename, $dumpDups=TRUE){
    $available_languages = PHPWS_Language::getRegularLanguages();

    $stats["add"] = $stats["dup"] = $stats["total"] = $stats["drop"] = $stats["nodrop"] = 0;
    if ($language_array = file($filename)){
      PHPWS_Array::dropNulls($language_array);
      $file_info = explode(":|:", trim($language_array[0]));
      $language = $file_info[0];
      if (!isset($available_languages[strtolower($language)]))
	return FALSE;

      $dup["module"] = $module = $file_info[1];
      unset($language_array[0]);
      foreach($language_array as $row){
	$row = trim($row);
	$info = explode(":|:", $row);
	if (!count($info) || !isset($info[0]) || !isset($info[1]) || !isset($info[2]))
	  continue;
	$process = $info[0];
	$phrase = $info[1];
	$translation = $info[2];
	$dup["phrase"] = $phrase;
	$dup["translation"] = "?" . $translation;
	
	switch ($process){
	case "a":
	  if ($dumpDups)
	    $GLOBALS["core"]->sqlDelete(PHPWS_Language::tableName($language), $dup);

	  if(PHPWS_Language::insert_phrase($language, $phrase, $translation, $module))
	    $stats["add"]++;
	  else
	    $stats["dup"]++;
	  
	  $stats["total"]++;
	  break;
	  
	case "d":
	  $search["phrase"] = $phrase;
	  $search["module"] = $module;
	  if($get_id = $GLOBALS["core"]->sqlSelect(PHPWS_Language::tableName($language), $search)){
	    foreach($get_id as $id_row){
	      PHPWS_Language::drop_phrase($id_row["phrase_id"], $language);
	      $stats["drop"]++;
	    }
	  } else
	    $stats["nodrop"]++;
	  $stats["total"]++;
	  break;
	}
      }
      return $stats;
    } else
      return FALSE;
  }
    
  /**
   * Search and replaces variables indicators with the proper variables
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function replaceVars($sentence, $var1=NULL, $var2=NULL, $var3=NULL){
    if ($var1)
      $sentence = str_replace("[var1]", $var1, $sentence);
    if ($var2)
      $sentence = str_replace("[var2]", $var2, $sentence);
    if ($var3)
      $sentence = str_replace("[var3]", $var3, $sentence);

    return preg_replace("/\[var.\]/", "", $sentence);
  }

  /**
   * Sets up the object with the correct current language
   *
   * It will look for the user's cookies for which language they specified.
   * Otherwise, it uses the default language
   */
  function init(){
    if ($cookie_language = $_SESSION["OBJ_user"]->cookie_read("language", "current_language")){
      $test_lang = $this->getRegularLanguages();
      if ($test_lang[$cookie_language])
	$this->current_language = $cookie_language;
      else
	$this->current_language = $this->default_language;
    } else
      $this->current_language = $this->default_language;
  }

  function dyn($table_name, $id){
    if (!$this->current_language)
      $this->init();

    if (!($GLOBALS["core"]->sqlTableExists($this->dynamicTable($this->current_language), 1)))
      return NULL;

    $translation = $this->getDynamic($table_name, $id, $this->current_language);
    if (empty($translation))
      $this->createTranslation($table_name, $id);
    return $translation;
  }


  /**
   * Returns the translation of a phrase
   *
   * This function is the workhorse of the translation system. Normally it just translates
   * the phrase with what is currently in the dictionary. If the phrase is not in the dictionary
   * the system checks to see if the admin wants to auto-update (auto_up variable). If TRUE
   * then the function looks to see if the phrase is already in another module. If it is, then it
   * changes that translation to a core translation (which are loaded by all modules). If it is
   * not, then it enters it into the language table for later translation.
   * 
   * If the phrase is entered into the language file or auto_up is not TRUE, then the phrase is 
   * returned as the translation. If "mark" is TRUE, then a question mark is prefixed to the translation.
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function it($phrase, $var1=NULL, $var2=NULL, $var3=NULL){
    if (!$this->langActive)
      return $this->replaceVars($phrase, $var1, $var2, $var3);

    if ($this->ignoreDefault && ($this->current_language == $this->default_language)) 
      return $this->replaceVars($phrase, $var1, $var2, $var3);
    
    $lang_table = $this->tableName($this->current_language);
    if (isset($this->dictionary[$phrase])){

      // The phrase is currently in the dictionary, so the translation is returned
      $translation = $this->dictionary[$phrase];

      if (substr($translation, 0, 1)=="?" && !$this->mark)
	$translation = substr($translation, 1);
      return $this->replaceVars($translation, $var1, $var2, $var3);
    } elseif($this->auto_up == 1 && $GLOBALS["core"]->sqlTableExists($lang_table, 1)){

      // The phrase was NOT in the dictionary, and the admin wants to automatically update
      // the language table
      $sql = $GLOBALS["core"]->sqlSelect($lang_table, "phrase", $phrase);
      if (!count($sql)){
	// No matches in other modules were found, add it
	$insert["phrase"] = $phrase;
	if (!empty($GLOBALS["core"]->current_mod))
	  $insert["module"] = $GLOBALS["core"]->current_mod;
	elseif(isset($GLOBALS["module"]))
	  $insert["module"] = $GLOBALS["module"];
	else
	  $insert["module"] = "unknown";

	$insert["translation"] = "?" . $phrase;
	$GLOBALS["core"]->sqlInsert($insert, $lang_table);
      } elseif (substr($sql[0]["translation"], 0, 1) != "?") {
	// A match was found in another module and it is not awaiting translation itself
	// Enter the phrase and translation as the module's translation
	if (!empty($GLOBALS["core"]->current_mod))
	  $insert["module"] = $GLOBALS["core"]->current_mod;
	elseif(isset($GLOBALS["module"]))
	  $insert["module"] = $GLOBALS["module"];
	else
	  $insert["module"] = "unknown";

	$insert["phrase"] = $phrase;
	$insert["translation"] = $sql[0]["translation"];
	$GLOBALS["core"]->sqlInsert($insert, $lang_table);
	return $this->replaceVars($phrase, $var1, $var2, $var3);
      }

    }
    // Returns just the phrase as a translation
    // Happens if auto_up is not on or currently translated
    if ($this->mark)
      return "?". $this->replaceVars($phrase, $var1, $var2, $var3);
    else
      return $this->replaceVars($phrase, $var1, $var2, $var3);
  }

  /**
   * Converts old 8.2 file, needs revisit
   *
   * This was written a while ago. It is kept in the code incase it is needed later
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function convert_language_file($filename){
    if (file_exists($filename)){
      $file = PHPWS_File::readFile($filename, 1);
      $lang_array = explode("\n", $file);
      foreach ($lang_array as $key=>$value){
	if (preg_match("/^\[\[/", $value)){
	  $new_value = preg_replace("/\[\[|\]\]/", "", $value);
	  $temp_array = explode ("=", $new_value);
	  $new_array[trim($temp_array[0])] = trim($temp_array[1]);
	}
      }
      return $new_array;
    }

  }

  /**
   * Exports a specific modules translations for a specific language
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function export_lang($language, $module){
    $mod_dir = $GLOBALS["core"]->getModuleDir($module);
    $GLOBALS["CNT_lang"]["content"] = $this->link_to_admin()."<br /><br />";
    $destination = PHPWS_SOURCE_DIR."mod/$mod_dir/lang/$module.$language.lng";
    $GLOBALS["CNT_lang"]["title"] = $_SESSION["translate"]->it("Export [var1] Language File", $module);

    if ($GLOBALS["core"]->sqlSelect($this->tableName($language), "module", $module)){
      if($this->export($language, $destination, $module))
	$GLOBALS["CNT_lang"]["content"] .= $_SESSION["translate"]->it("Export to [var1] successful", $destination).".";
      else
	$GLOBALS["CNT_lang"]["content"] .= $_SESSION["translate"]->it("Export to [var1] not successful", $destination).".";
    } else {
      $GLOBALS["CNT_lang"]["content"] .= $_SESSION["translate"]->it("No translations to export").".";
    }
  }

  /**
   * Imports a specific language file for a specific module
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function import_lang($language, $module){
    $mod_dir = $GLOBALS["core"]->getModuleDir($module);
    $GLOBALS["CNT_lang"]["content"] .= $this->link_to_admin()."<br /><br />";
    $file = PHPWS_SOURCE_DIR."mod/$mod_dir/lang/$module.$language.lng";
    if (file_exists($file)){
      if ($stats = $this->import($file)){
	$GLOBALS["CNT_lang"]["content"] .= "<h4>".$_SESSION["translate"]->it("Import Successful")."!</h4><hr />";
	$GLOBALS["CNT_lang"]["content"] .= "<b>".$_SESSION["translate"]->it("Results").":</b><br />";
	$table[] = array($_SESSION["translate"]->it("Total Inserts").":", $stats["add"]);
	$table[] = array($_SESSION["translate"]->it("Total Duplicates").":", $stats["dup"]);
	$table[] = array($_SESSION["translate"]->it("Total Removals").":", $stats["drop"]);
	$table[] = array($_SESSION["translate"]->it("Missed Removals").":", $stats["nodrop"]);
	$table[] = array("<b>".$_SESSION["translate"]->it("Total Entries").":</b>", "<b>".$stats["total"]."</b>");
	$GLOBALS["CNT_lang"]["content"] .= PHPWS_Text::ezTable($table, 5);
      } else
	$GLOBALS["CNT_lang"]["content"] .= $_SESSION["translate"]->it("A problem occurred while trying to import the language file")."."; 
    } else 
      $GLOBALS["CNT_lang"]["content"] .= $_SESSION["translate"]->it("This module does not have a import file for this language").".";
  }

  /**
   * Returns a link string that returns the user to the admin menu
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function link_to_admin(){
    $vars["module"] = "language";
    $vars["lng_adm_op"] = "admin";
    return PHPWS_Text::link("index.php", $_SESSION["translate"]->it("Back to Admin"), "index", $vars);
  }

  /**
   * Writes a user's language choice to their cookie
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function setUserLanguage($language){
    $this->current_language = $language;
    $_SESSION["OBJ_user"]->cookie_write("language", "current_language", $language);
    $this->createDictionary();
    $GLOBALS["CNT_lang"]["title"] = $_SESSION["translate"]->it("Your Default Language is Saved");
    $GLOBALS["CNT_lang"]["content"] = PHPWS_Text::link("index.php", $_SESSION["translate"]->it("Return to the user menu"), "index", array("module"=>"language","lng_usr_op"=>"user_admin"));
  }

  /**
   * The user administration page
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function user_admin(){
    $title = $_SESSION["translate"]->it("Language User Menu");
    if (!$this->langActive)
      $content = "The Language Module is turned off.";
    else {
      $content  = "<form action=\"index.php\" method=\"post\">\n";
      $content .= PHPWS_Form::formHidden(array("module"=>"language", "lng_usr_op"=>"setUserLanguage"))."\n";
      $content .= PHPWS_Form::formSelect("user_language", $this->getRegularLanguages(), $_SESSION["translate"]->current_language, null, 1)." \n";
      $content .= PHPWS_Form::formSubmit($_SESSION["translate"]->it("Set My Default Language"));
      $content .= "</form>";
    }

    $GLOBALS["CNT_lang"]["title"] = $title;
    $GLOBALS["CNT_lang"]["content"] = $content;
  }

  /**
   * The administrative form for controlling the language module
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   */
  function admin(){
    $this->indexed_dictionary = $this->dictionary = $phrases = NULL;
    $this->loadSettings();
    $this->available_languages = $this->getRegularLanguages();
    $mods = $GLOBALS["core"]->listModules();

    $dyn_list = $this->getDynamicLanguages();

    $template["SET_DEFAULT"]      = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Set Default Language"), "set_default_lang");
    $template["EDIT"]             = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Edit Language"), "editLanguage");
    $template["CREATE"]           = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Create Language Table"), "createLanguageTable");
    $template["REMOVE"]           = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Remove Language Table"), "removeLanguageTableForm");
    $template["IMPORT_BUTTON"]    = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Import Language File"), "import_lang_file");
    $template["EXPORT_BUTTON"]    = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Export Language File"), "export_lang_file");
    $template["UPDATE_BUTTON"]    = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Update Language Settings"), "updateLangSettings");
    $template["MISSING"]          = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Search for Missing Translations"), "langMissing");

    $template["LANG_SELECT"]      = PHPWS_Form::formSelect("language", $this->available_languages, $this->fullname($this->default_language));
    $template["MODULE"]           = PHPWS_Form::formSelect("lng_module", $mods, NULL, 1);

    $template["LANG_ACTIVE_OFF"]  = PHPWS_Form::formRadio("langActive_switch", 0, $this->langActive)." ".$_SESSION["translate"]->it("Off");
    $template["LANG_ACTIVE_ON"]   = PHPWS_Form::formRadio("langActive_switch", 1, $this->langActive)." ".$_SESSION["translate"]->it("On");
    $template["DYN_ACTIVE_OFF"]   = PHPWS_Form::formRadio("dynActive_switch", 0, $this->dynActive)." ". $_SESSION["translate"]->it("Off");
    $template["DYN_ACTIVE_ON"]    = PHPWS_Form::formRadio("dynActive_switch", 1, $this->dynActive). " ". $_SESSION["translate"]->it("On");
    $template["MARK_OFF"]         = PHPWS_Form::formRadio("mark_switch", 0, $this->mark). " " . $_SESSION["translate"]->it("Off");
    $template["MARK_ON"]          = PHPWS_Form::formRadio("mark_switch", 1, $this->mark). " ".$_SESSION["translate"]->it("On");
    $template["AUTO_OFF"]         = PHPWS_Form::formRadio("auto_up_switch", 0, $this->auto_up) . " ". $_SESSION["translate"]->it("Off");
    $template["AUTO_ON"]          = PHPWS_Form::formRadio("auto_up_switch", 1, $this->auto_up) . " ". $_SESSION["translate"]->it("On");
    $template["IGNORE_OFF"]       = PHPWS_Form::formRadio("ignoreDefault_switch", 0, $this->ignoreDefault) . " " . $_SESSION["translate"]->it("Off");
    $template["IGNORE_ON"]        = PHPWS_Form::formRadio("ignoreDefault_switch", 1, $this->ignoreDefault) . " " . $_SESSION["translate"]->it("On");
  
    $template["LANG_ACTIVE"]      = $_SESSION["translate"]->it("Language Active");
    $template["DYN_ACTIVE"]       = $_SESSION["translate"]->it("Dynamic Active");
    $template["MARK"]             = $_SESSION["translate"]->it("Mark Untranslated");
    $template["AUTO"]             = $_SESSION["translate"]->it("Auto Update");
    $template["IGNORE"]           = $_SESSION["translate"]->it("Ignore Default Language");
    $template["LANGUAGE"]         = $_SESSION["translate"]->it("Language");
    $template["ADMINISTRATE"]     = $_SESSION["translate"]->it("Administrate");
    $template["IMPORT_EXPORT"]    = $_SESSION["translate"]->it("Import/Export");

    $template["LANG_ACTIVE_HELP"] = CLS_help::show_link("language", "langActive");
    $template["DYN_ACTIVE_HELP"]  = CLS_help::show_link("language", "dynActive");
    $template["MARK_HELP"]        = CLS_help::show_link("language", "mark");
    $template["AUTO_HELP"]        = CLS_help::show_link("language", "auto");
    $template["IGNORE_HELP"]      = CLS_help::show_link("language", "ignore");
    $template["LANG_HELP"]        = CLS_help::show_link("language", "languageOptions");
    $template["IMP_EXP_HELP"]     = CLS_help::show_link("language", "importExport");

    if ($dyn_list){
      $template["DYN_HELP"]         = CLS_help::show_link("language", "dynAdmin");
      $table2[] = array("<b>".$_SESSION["translate"]->it("Language")."</b>",
			"<b>".$_SESSION["translate"]->it("Module")."</b>",
			"<b>".$_SESSION["translate"]->it("Untranslated")."</b>",
			"<b>".$_SESSION["translate"]->it("Updated")."</b>",
			"<b>".$_SESSION["translate"]->it("Refresh")."</b>");
      
      $dynModules = $this->getRegistered();

      foreach ($dyn_list as $abbr=>$fullname){
	foreach ($dynModules as $modInfo){

	  $moduleName = $modInfo["module_name"];
	  $moduleTable = $modInfo["table_name"];
	  $untrans_link = array("lng_adm_op"=>"viewUntranslatedList", "language"=>$abbr, "table_name"=>$moduleTable);
	  $updated_link = array("lng_adm_op"=>"viewUpdatedList", "language"=>$abbr, "table_name"=>$moduleTable);
	  
	  if ($count = $this->countUntranslated($abbr, $moduleTable))
	    $untranslated_info = PHPWS_Text::moduleLink($count, "language", $untrans_link);
	  else
	    $untranslated_info = "0";
	  
	  if ($count = $this->countUpdated($abbr, $moduleTable))
	    $updated_info = PHPWS_Text::moduleLink($count, "language", $updated_link);
	  else
	    $updated_info = "0";
	  
	  $refresh = PHPWS_Text::moduleLink(PHPWS_Text::imageTag(PHPWS_SOURCE_HTTP."mod/language/check.gif"), "language", array("module"=>"language", "lng_adm_op"=>"refreshDynamic", "language"=>$abbr, "table_name"=>$moduleTable));
	  
	  $table2[] = array($fullname, $moduleName, $untranslated_info, $updated_info, $refresh);
	}
      }
      $template["DYN_TITLE"] = $_SESSION["translate"]->it("Dynamic Translations");
      $template["DYN_LIST"] = PHPWS_Text::ezTable($table2, 6, 0, 0, "100%", NULL, 1, "top");
    }

    $GLOBALS["CNT_lang"]["title"] = $_SESSION["translate"]->it("Language Administration") . CLS_help::show_link("language", "langAdmin");
    $content = 
       "\n<form action=\"index.php\" method=\"post\">"
       . PHPWS_Form::formHidden(array("module"=>"language", "lng_adm_op"=>"adminMenuSelect"))
       . PHPWS_Template::processTemplate($template, "language", "adminMenu.tpl")
       . "</form>\n";
    return $content;
  }




  /**
   * Form for removal of language tables
   *
   * There are two check boxes to double check if the user REALLY wants to remove
   * the language tables. One must be checked while the other is unchecked.
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function removeLanguageTableForm($language){
    $content = NULL;
    $content .= $this->link_to_admin()."<br /><br />";
    $content .= $_SESSION["translate"]->it("Are you sure you want to remove these language tables and ALL the translations associated with them")."?<br />\n";
    $content .= "<form action=\"index.php\" method=\"post\">\n";
    $content .= PHPWS_Form::formHidden(array("module"=>"language", "lng_adm_op"=>"removeLanguageTableAction", "language"=>$language));
    $content .= PHPWS_Form::formCheckBox("confirm_language_deletion") ." " . $_SESSION["translate"]->it("Check this box to confirm the deletion").".<br />\n";
    $content .= PHPWS_Form::formCheckBox("deny_language_deletion", 1, 1) ." " . $_SESSION["translate"]->it("Uncheck this box to confirm the deletion").".<br /><br />\n";
    $content .= PHPWS_Form::formSubmit("Yes, remove ".$this->fullname($language)." language table")."\n";
    $content .= "</form>";

    $GLOBALS["CNT_lang"]["title"] .= "Language Removal Confirmation";
    $GLOBALS["CNT_lang"]["content"] .= $content;

  }

  /**
   * Removes both the standard and dynamic tables for a specific language
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function removeLanguageTableAction($language){
    if($GLOBALS["core"]->sqlTableExists($this->tableName($language), 1))
            $GLOBALS["core"]->sqlDropTable($this->tableName($language));

    if($GLOBALS["core"]->sqlTableExists($this->dynamicTable($language), 1))
      $GLOBALS["core"]->sqlDropTable($this->dynamicTable($language));

    if($language == $this->default_language) {
      $save = array();
      $save['default_language'] = $this->default_language = $this->current_language = $_SESSION["translate"]->default_language = $_SESSION['translate']->current_language = "en";
      $GLOBALS['core']->sqlUpdate($save, "mod_language_settings");
    }
  }

  /**
   * Form used to create both a standard and dynamic language table.
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function createLanguageTable(){
    $hidden = PHPWS_Form::formHidden(array("module"=>"language", "lng_adm_op"=>"createLanguage")); 
    $languages = $this->getCodeArray();

    if ($current_languages = $this->getRegularLanguages())
      foreach ($current_languages as $del_abbr=>$devNull)
	unset($languages[$del_abbr]);
    
    if ($dyn_languages = $this->getDynamicLanguages())
      foreach ($dyn_languages as $del_abbr=>$devNull)
	unset($current_languages[$del_abbr]);

    $title = $_SESSION["translate"]->it("Create Language Table") . CLS_help::show_link("language", "createTable");

    $content = "
<form action=\"index.php\" method=\"post\">
$hidden";
    $content .= PHPWS_Form::formSelect("reg_opt_language", $languages)." ";
    $content .= PHPWS_Form::formSubmit($_SESSION["translate"]->it("Create Language Table"), "regLanguage")."<br /><br />";

    if (count($current_languages)){    
      $content .= PHPWS_Form::formSelect("dyn_opt_language", $current_languages)." ";
      $content .= PHPWS_Form::formSubmit($_SESSION["translate"]->it("Create Dynamic Table"), "dynLanguage")."<br /><br />";
    }
$content .= "</form>";

    $GLOBALS["CNT_lang"]["title"] = $title;
    $GLOBALS["CNT_lang"]["content"] = $this->link_to_admin()."<br /><br />";
    $GLOBALS["CNT_lang"]["content"] .= $content;
  }

  /**
   * Sets the default language for the system
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function set_default($lang_default){
    $this->default_language = $_SESSION["translate"]->default_language = $update["default_language"] = $lang_default;
    $GLOBALS["core"]->sqlUpdate($update, "mod_language_settings");
  }

  /**
   * Creates the standard language table
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function createLanguage($language){
    $table_name = "mod_lang_".strtolower($language);
    
    if (!preg_match("/[A-Za-z]{2}/", $language))
      return FALSE;

    if ($GLOBALS["core"]->sqlTableExists($table_name, 1))
      return FALSE;

    $columns["phrase_id"] = "int unsigned NOT NULL, PRIMARY KEY  (phrase_id)";
    $columns["module"] = "varchar(30) NOT NULL default '', KEY module (module)";
    $columns["phrase"] = "text NOT NULL";
    $columns["translation"] = "text NOT NULL";

    if($GLOBALS["core"]->sqlCreateTable($table_name, $columns)){
      $row = $GLOBALS["core"]->sqlSelect("modules");
      foreach ($row as $modInfo)
	PHPWS_Language::installLanguages($modInfo["mod_directory"]);
      return TRUE;
    } else
      return FALSE;
  }

  /**
   * Form for editting a language
   *
   * There are few things you can do in this form. You can search for missing translations.
   * You can edit translations already in the system and you can create new translations
   * (though I imagine this won't be used since the auto_up option exists
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function editLanguage($language){
    $this->current_language = $language;
    $all_mods = $GLOBALS["core"]->listModules();
    array_unshift($all_mods, "core");

    $GLOBALS["CNT_lang"]["title"] = $_SESSION["translate"]->it("Update [var1]", $this->fullname($language)) . CLS_help::show_link("language", "editLanguage");

    $content = $this->link_to_admin()."<br />
<form action=\"index.php\" method=\"post\">
".PHPWS_Form::formSubmit($_SESSION["translate"]->it("Search for Missing Translations"), "lang_edit_com[search_for_missing]")."<br /><br />
".PHPWS_Form::formTextField("lng_search_phrase", NULL, 30)."
".PHPWS_Form::formSubmit($_SESSION["translate"]->it("Search for Phrase"), "lang_edit_com[search_phrase]")."<br /><br />
".PHPWS_Form::formTextField("lng_search_translation", NULL, 30)."
".PHPWS_Form::formSubmit($_SESSION["translate"]->it("Search for Translation"), "lang_edit_com[search_translation]")."<br /><br />";

$content .= PHPWS_Form::formHidden(array("lng_adm_op"=>"edit_lang_action", "module"=>"language", "language"=>$language))."<hr />
<b>".$_SESSION["translate"]->it("Create New Entry")."</b><br />
<table cellpadding=\"4\">
  <tr>
    <th>".$_SESSION["translate"]->it("Phrase")."</th><th>".$_SESSION["translate"]->it("Translates To").":</th><th>".$_SESSION["translate"]->it("Module")."</th>
  </tr>
  <tr>
   <td>".PHPWS_Form::formTextField("lang_new_phrase")."</td><td>".PHPWS_Form::formTextField("lang_new_trans")."</td>
   <td>".PHPWS_Form::formSelect("lang_mod_title", $all_mods, "core", 1)."</td>
   <td>".PHPWS_Form::formSubmit($_SESSION["translate"]->it("Insert Phrase"), "lang_edit_com[insert_phrase]")."</td>
  </tr>
</table>
";

    $content .= "
</form>
<br />
";

    $GLOBALS["CNT_lang"]["content"]=$content;
  }


  /**
   * Form for editting specific phrases
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function edit_phrases($mode=NULL){
    $mod_array = $GLOBALS["core"]->listModules();
    $mod_array[] = "core";

    if ($this->indexed_dictionary){
      $i = 0;
      $this->phrases[1] = NULL;

      foreach($this->indexed_dictionary as $phrase_id=>$value){
	$i++;
	extract($value);
	PHPWS_WizardBag::toggle($color, " class=\"bg_light\"");
	$this->phrases[$i] = "
  <tr".$color.">
    <td align=\"center\">".PHPWS_Form::formCheckBox("lng_edit_id[$phrase_id]")."</td>
    <td>".PHPWS_Form::formSelect("lng_edit_module[$phrase_id]",$mod_array, $module, 1)."</td>
    <td>";
	if ($mode=="missing")
	  $this->phrases[$i].= PHPWS_Form::formHidden("lng_edit_phrase[$phrase_id]", $phrase)."<b>$phrase</b>";
	else
	  if (strlen($phrase) > 40){
	    $rows = floor($phrase / 40) + 2;
	    $this->phrases[$i] .= PHPWS_Form::formTextArea("lng_edit_phrase[$phrase_id]", $phrase, $rows, 40);
	  }
	  else
	    $this->phrases[$i] .= PHPWS_Form::formTextField("lng_edit_phrase[$phrase_id]", $phrase, 40);
	$this->phrases[$i] .= "</td>
    <td>";

	if (strlen($translation) > 40){
	  $rows = floor($translation / 40) + 2;
	  $this->phrases[$i] .= PHPWS_Form::formTextArea("lng_edit_translation[$phrase_id]", $translation, $rows, 40);
	}
	else
	$this->phrases[$i] .=  PHPWS_Form::formTextField("lng_edit_translation[$phrase_id]", $translation, 40);

    $this->phrases[$i] .= "</td>
    <td>".PHPWS_Form::formSubmit($_SESSION["translate"]->it("Delete"), "lng_drop_phrase[$phrase_id]")."</td>
  </tr>";
      }
      $this->list_phrases($mode);
    } else {
      $GLOBALS["CNT_lang"]["title"] = "<i>".$_SESSION["translate"]->it("No Phrases Found for [var1]", $this->fullName($this->current_language))."</i>";
      $GLOBALS["CNT_lang"]["content"] = $this->link_to_admin();
    }
  }
  
  /** 
   * Generic form to list all phrases loaded into the phrases variable
   *
   * These phrase will either come from a missing translations search of just a plain
   * phrase or translation search.
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function list_phrases($mode=NULL){
    $data = PHPWS_Array::paginateDataArray($this->phrases, "./index.php?module=language&amp;lng_adm_op=list_phrases", 20, 1, array("<b>[ ", " ]</b>"), NULL, 20);

    $GLOBALS["CNT_lang"]["title"] = $_SESSION["translate"]->it("Search Result for [var1]", $this->fullName($this->current_language)) . CLS_help::show_link("language", "searchResult");
    $GLOBALS["CNT_lang"]["content"] = "
".$this->link_to_admin()." | ".$this->back_to_edit()."<br /><br />";

    if (is_null($mode))
      $mode = "edit";

    $GLOBALS["CNT_lang"]["content"] .= "
<form name=\"phrases\" action=\"index.php\" method=\"post\">
".PHPWS_Form::formHidden(array("module"=>"language", "lng_adm_op"=>"edit_phrase_action", "language"=>$this->current_language, "mode"=>$mode))."
<table cellpadding=\"4\" cellspacing=\"1\" width=\"100%\">
  <tr class=\"bg_medium\">
    <th width=\"5%\">".$_SESSION["translate"]->it("Update")."</th>
    <th width=\"5%\">".$_SESSION["translate"]->it("Module")."</th>
    <th >".$_SESSION["translate"]->it("Phrase")."</th>
    <th width=\"45%\">".$_SESSION["translate"]->it("Translation")."</th><td>&nbsp;</td>
  </tr>";

    $GLOBALS["CNT_lang"]["content"] .= $data[0];

      $paged_requests = '';
      if(isset($_REQUEST['PDA_limit'])) {
	  $paged_requests .= PHPWS_Form::formHidden("PDA_limit", $_REQUEST['PDA_limit']);
      }
      if(isset($_REQUEST['PDA_start'])) {
	  $paged_requests .= PHPWS_Form::formHidden("PDA_start", $_REQUEST['PDA_start']);
      }
      if(isset($_REQUEST['PDA_section'])) {
	  $paged_requests .= PHPWS_Form::formHidden("PDA_section", $_REQUEST['PDA_section']);
      }
      $GLOBALS['CNT_lang']['content'] .= $paged_requests;

      $GLOBALS["CNT_lang"]["content"] .= "
</table><br /><div align=\"center\">
".PHPWS_Form::formSubmit($_SESSION["translate"]->it("Update Checked"))."
".PHPWS_WizardBag::js_insert("check_all", "phrases", NULL, TRUE)."</div>
</form><br />";

    $GLOBALS["CNT_lang"]["content"] .= "<div align=\"center\">".$data[1] . "</div>";
    $GLOBALS["CNT_lang"]["content"] .= "<div align=\"center\">".$data[2] . $_SESSION['translate']->it("Phrases")."</div>";
  }

  /**
   * Removes a phrase from a language table
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function drop_phrase($phrase_id, $language){
    $GLOBALS["core"]->sqlDelete("mod_lang_".strtoupper($language), "phrase_id", $phrase_id);
  }

  /**
   * Updates a phrase in the database
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function update_phrase($phrase_id, $module, $phrase, $translation, $language){
    if ($language){
      $phrase = $phrase;
      $translation = $translation;
      $update = array("module"=>$module, "phrase"=>$phrase, "translation"=>$translation);
      $GLOBALS["core"]->sqlUpdate($update, $this->tableName($language), "phrase_id", $phrase_id);
      $this->indexed_dictionary[$phrase_id] = $update;
    }
  }

  /**
   * Inserts a new phrase into a language table
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function insert_phrase($language, $phrase, $translation, $module){

    if (strlen($language) && strlen($phrase) && strlen($translation) && strlen($module)){
      $insert["module"] = $module;
      $insert["phrase"] = $phrase;
      $insert["translation"] = $translation;

      if($GLOBALS["core"]->sqlInsert($insert, "mod_lang_".strtolower($language), 1))
	return TRUE;
      else
	return FALSE;
    } else
      return FALSE;
  }

  /**
   * Removes a module's phrases from all language files
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function drop_mod_phrases($module){
    $languages = $this->getRegularLanguages();
    foreach($languages as $lang_file=>$dump){
      $GLOBALS["core"]->sqlDelete($this->tableName($lang_file), "module", $module);       
    }
  }

  /**
   * Defunct function
   *
   * Oringinally you could compare two languages to see where they differ. Was having
   * problems with it, so I put it on the back burner.
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function compare_languages($compare_language){
    $GLOBALS["CNT_lang"]["title"] = $_SESSION["translate"]->it("Compare [var1] to [var2]", $this->fullname($this->current_language), $this->fullname($compare_language));
    $GLOBALS["CNT_lang"]["content"] .= $this->link_to_admin()."<br /><br />";
    $compare = new PHPWS_Language;
    $compare->current_language = $compare_language;
    $compare->createDictionary("all_mods");
    $compare->createDictionary("all_mods", NULL, 1);

    $this->createDictionary("all_mods",NULL,1);
    $this->createDictionary("all_mods");

    $this->compare_dict($compare->dictionary);
    $compare->compare_dict($this->dictionary);

    if ($this->indexed_dictionary){
      $GLOBALS["CNT_lang"]["content"] .= $this->back_to_edit()."<br /><br />
".$_SESSION["translate"]->it("The following phrases are missing from [var1]", $this->fullname($compare_language)).":<br />";

      $GLOBALS["CNT_lang"]["content"] .= $this->multi_add($this->indexed_dictionary, $compare_language, 1);
    }

    if($compare->indexed_dictionary){
      if($this->indexed_dictionary)
	$GLOBALS["CNT_lang"]["content"] .= "<hr />";

      $GLOBALS["CNT_lang"]["content"] .= "<br />
".$_SESSION["translate"]->it("The following phrases are missing from [var1]", $this->fullname($this->current_language)).":<br />";
      
      $GLOBALS["CNT_lang"]["content"] .= $this->multi_add($compare->indexed_dictionary, $this->current_language, 1);

      $this->indexed_dictionary = $this->dictionary = $compare = NULL;
    }
    $compare = $this->indexed_dictionary = $this->dictionary = NULL;

  }

  /**
   * Defunct function
   *
   * Worked with compare languages.
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function compare_dict($dictionary){
    if ($this->indexed_dictionary){
      foreach ($this->indexed_dictionary as $phrase_id=>$value){
	extract($value);
	if ($dictionary[$phrase]){
	  unset($this->indexed_dictionary[$phrase_id]);
	}
      }
      return $missing;
    } else
      return NULL;
  }


  /**
   * Form for adding multiple phrases. Currently not in use
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function multi_add($missing, $language, $lock_phrase=NULL){
    $content = "
<form name=\"multi\" action=\"index.php\" method=\"post\">
".PHPWS_Form::formHidden("module", "language")."
".PHPWS_Form::formHidden("lng_adm_op", "mass_insert")."
".PHPWS_Form::formHidden("lng_upd_lang", $language)."
<table cellpadding=\"6\" width=\"100%\">
  <tr>
    <th width=\"5%\">".$_SESSION["translate"]->it("Add")."</th>
    <th width=\"5%\">".$_SESSION["translate"]->it("Module")."</th>
    <th>".$_SESSION["translate"]->it("Phrase")."</th>
    <th width=\"45%\">".$_SESSION["translate"]->it("Translation")."</th>
  </tr>";
    foreach ($missing as $phrase_id=>$value){
      extract($value);
      $phrase = htmlspecialchars($phrase);
      $translation = htmlspecialchars($translation);
      $i++;

      $content .= "
  <tr>
    <td align=\"center\">".PHPWS_Form::formCheckBox("lng_add_comp_allow[]", $i)."</td>
    <td><b>$module</b>".PHPWS_Form::formHidden("lng_add_modname[$i]", $module)."</td>
    <td>";

      if ($lock_phrase)
	$content .= PHPWS_Form::formHidden("lng_add_phrase[$i]", $phrase)."<b>$phrase</b>";
      else
	$content .= PHPWS_Form::formTextField("lng_add_phrase[$i]", $phrase, 30);

      $content .= "</td>
    <td>".PHPWS_Form::formTextField("lng_add_trans[$i]", $translation, 30)."</td>
  </tr>";
    }

    $content .= "
</table>
".PHPWS_Form::formSubmit($_SESSION["translate"]->it("Insert Checked"))." ".PHPWS_WizardBag::js_insert("check_all", "multi")."
</form>";

    return $content;
  }

  /**
   * Clears out both dictionaries in the object
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function dump(){
    $this->dictionary = NULL;
    $this->indexed_dictionary = NULL;
  }

  /**
   * Header command to send the admin back to the admin menu
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function force_to_admin($extra="lng_adm_op=admin"){
    header("location:index.php?module=language&$extra");
    exit();
  }

  /**
   * Links admin back to the edit language menu
   *
   * @author Matthew McNaney <matt@NOSPAM_tux.appstate.edu>
   */
  function back_to_edit(){
    return "<a href=\"index.php?module=language&amp;lng_adm_op=editLanguage&amp;language=".$this->current_language."\">".$_SESSION["translate"]->it("Update")."</a>";
  }


  function installModuleLanguage($moduleList){
    $content = NULL;
    foreach ($moduleList as $moduleDir)
      $content .= PHPWS_Language::installLanguages($moduleDir);

    return $content;
  }


  function uninstallLanguages($moduleDir){
    $content = NULL;
    $modFile   = PHPWS_SOURCE_DIR . "mod/" . $moduleDir . "/conf/boost.php";
    if (!file_exists($modFile))
      return $_SESSION["translate"]->it("Module Information file missing in") . " $modFile<br />";
    
    include ($modFile);

    if ($languages = PHPWS_Language::getRegularLanguages()){
      foreach ($languages as $language=>$fullname){
	$content .= $_SESSION["translate"]->it("Removing phrases from") . " $fullname<br />";
	$GLOBALS["core"]->sqlDelete(PHPWS_Language::tableName($language), "module", $mod_title);
      }
    }

    if ($languages = PHPWS_Language::getDynamicLanguages()){
      foreach ($languages as $language=>$fullname){
	$content .= $_SESSION["translate"]->it("Removing dynamic phrases from") . " $fullname<br />";
	$result = $GLOBALS['core']->sqlSelect("mod_dyn_modules", "module_name", $mod_title);
	if (isset($result)){
	  foreach ($result as $item)
	    $GLOBALS["core"]->sqlDelete(PHPWS_Dynamic::dynamicTable($language), 'table_name', $item['table_name']);	    
	}
      }
      PHPWS_Dynamic::unregisterModule($mod_title);
    }

    $content .= "<b>" . $_SESSION["translate"]->it("Finished uninstalling [var1] from languages", $mod_pname) . "</b><br />";
    return $content;
  }


  function installLanguages($moduleDir){
    $content = NULL;
    $modFile   = PHPWS_SOURCE_DIR . "mod/" . $moduleDir . "/conf/boost.php";
    if (!file_exists($modFile))
      return $_SESSION["translate"]->it("Module Information file missing in") . " $modFile<br />";
    
    include ($modFile);

    $languages = PHPWS_Language::getRegularLanguages();

    if (empty($languages))
      return NULL;

    $directory = PHPWS_SOURCE_DIR . "mod/$moduleDir/lang/";

    if (!($files = PHPWS_File::readDirectory($directory)))
      return $_SESSION["translate"]->it("No language directory in [var1]", $mod_pname).".<br />";

    $content .= "<b>" . $_SESSION["translate"]->it("Checking language file for [var1]", $mod_pname) .":</b><br />";
    
    foreach ($languages as $language=>$fullname){
      $file_check = $mod_title.".".$language.".lng";
      if (in_array($file_check, $files)){
	if (PHPWS_Language::import($directory . $file_check, FALSE))
	  $content .= "* " . $_SESSION["translate"]->it("[var1] language file installed", $fullname)."<br />\n";
	else
	  $content .= "* " . $_SESSION["translate"]->it("Unable in import [var1] language file", $fullname)."<br />\n";
      } else
	$content .= "* " . $_SESSION["translate"]->it("No language file present for [var1]", $fullname)."<br />\n";
    }
    return $content;

  }
}

?>