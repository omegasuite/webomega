<?php
if (!isset($GLOBALS['core'])){
  header("location:../../");
  exit();
}

$CNT_lang['title'] = NULL;
$CNT_lang['content'] = NULL;

// Start translate object
if (!$_SESSION["translate"]->default_language){
  $_SESSION["translate"]->loadSettings();
  if ($_SESSION["translate"]->langActive)
    $_SESSION["translate"]->init();
  else
    unset($_SESSION["translate"]->dictionary);
}

if ($_SESSION["translate"]->langActive && !($_SESSION["translate"]->ignoreDefault && ($_SESSION["translate"]->current_language == $_SESSION["translate"]->default_language))) {
     $_SESSION["translate"]->createDictionary();
} else {
    unset($_SESSION["translate"]->dictionary);
}

// Administrative options
if (isset($_REQUEST["lang_admin"]) && is_array($_REQUEST["lang_admin"]))
     list($lng_adm_op) = each($_REQUEST["lang_admin"]);
     elseif (isset($_REQUEST["lng_adm_op"]))
     $lng_adm_op = $_REQUEST["lng_adm_op"];

if (isset($lng_adm_op) && $_SESSION['OBJ_user']->allow_access("language")){
  extract($_REQUEST);

  switch ($lng_adm_op){
  case "admin":
    $CNT_lang["content"] = $_SESSION["OBJ_lang"]->admin();
  break;

  case "refreshDynamic":
    $_SESSION["OBJ_lang"]->refreshDynamic($language, $table_name);
  $_SESSION["OBJ_lang"]->force_to_admin();
  break;

  case "adminMenuSelect":
    if (isset($_POST["updateLangSettings"])){
      if (!($_SESSION["OBJ_lang"]->updateLanguageSettings()))
	$CNT_lang["content"] = "<span type=\"errortext\">Error writing to language settings table.</span>";
      else
	$CNT_lang["content"] = $_SESSION["translate"]->it("Language Settings Updated");
      $CNT_lang["content"] .= $_SESSION["OBJ_lang"]->admin();
    } elseif (isset($_POST["set_default_lang"])){
      $_SESSION["OBJ_lang"]->set_default($language);
      $CNT_lang["content"] = $_SESSION["translate"]->it("Default Language set to [var1]", $_SESSION["OBJ_lang"]->fullName($language));
      $CNT_lang["content"] .= $_SESSION["OBJ_lang"]->admin();
    } elseif (isset($_POST["editLanguage"])){
      $_SESSION["OBJ_lang"]->dump();
      $_SESSION["OBJ_lang"]->editLanguage($language);
    } elseif (isset($_POST["createLanguageTable"]))
	$_SESSION["OBJ_lang"]->createLanguageTable();
    elseif (isset($_POST["removeLanguageTableForm"]))
      $_SESSION["OBJ_lang"]->removeLanguageTableForm($language);
    elseif (isset($_POST["import_lang_file"]))
      $_SESSION["OBJ_lang"]->import_lang($language, $lng_module);
    elseif (isset($_POST["export_lang_file"]))
      $_SESSION["OBJ_lang"]->export_lang($language, $lng_module);
    elseif (isset($_POST["langMissing"])){
      $_SESSION["OBJ_lang"]->createDictionary("missing", NULL, 1, $language);
      $_SESSION["OBJ_lang"]->edit_phrases("missing");
    }
    break;

  case "update_language_settings":
    break;


  case "editLanguage":
    $_SESSION["OBJ_lang"]->dump();
  $_SESSION["OBJ_lang"]->editLanguage($language);
  break;


  case "viewUntranslatedList":
    $_SESSION["OBJ_lang"]->viewTranslateList($language, "untranslated", $table_name);
    break;

  case "viewUpdatedList":
    $_SESSION["OBJ_lang"]->viewTranslateList($language, "updated", $table_name);
    break;

  case "updateDynamicTranslationsForm":
    $_SESSION["OBJ_lang"]->updateDynamicTranslationsForm($listmode);
    break;

  case "updateDynamicTranslationAction":
    if (!$_SESSION["OBJ_lang"]->updateDynamicTranslationAction())
      $CNT_lang["content"] .= "<span class=\"errortext\">There was a problem updating the ".$_SESSION["OBJ_lang"]->fullname($language)." dynamic table.</span>";

    $_SESSION["OBJ_lang"]->viewTranslateList($language, $listmode, $table_name);

    if ($listmode == "untranslated")
      $_SESSION["OBJ_lang"]->force_to_admin("lng_adm_op=viewUntranslatedList&language=" . $language . "&table_name=" . $table_name);
    else
      $_SESSION["OBJ_lang"]->force_to_admin("lng_adm_op=viewUpdatedList=" . $language . "&table_name=" . $table_name);
    break;


  case "createLanguageTable":
    $_SESSION["OBJ_lang"]->createLanguageTable();
  break;

  case "createLanguage":

  if (isset($regLanguage)){
    if (!$_SESSION["OBJ_lang"]->createLanguage($reg_opt_language))
      $CNT_lang["content"] .= "<span class=\"errortext\">".$_SESSION["translate"]->it("There was a problem creating a regular language table for [var1]", $_SESSION["OBJ_lang"]->fullname($language))."!</span><br />";
    else
      $CNT_lang["content"] .= $_SESSION["translate"]->it("The [var1] regular language table was created", $_SESSION["OBJ_lang"]->fullname($_POST['reg_opt_language']))."!<br />";
  }
  if (isset($dynLanguage)){
    if(!$_SESSION["OBJ_lang"]->createDynamic($dyn_opt_language))
      $CNT_lang["content"] .= "<span class=\"errortext\">".$_SESSION["translate"]->it("There was a problem creating a dynamic language table for [var1]", $_SESSION["OBJ_lang"]->fullname($language))."!</span><br />";
    else
      $CNT_lang["content"] .= $_SESSION["translate"]->it("The [var1] dynamic language table was created", $_SESSION["OBJ_lang"]->fullname($_POST['dyn_opt_language']))."!<br />";

    $_SESSION["OBJ_lang"]->addDynamicModules($dyn_opt_language);
  }
  $_SESSION["OBJ_lang"]->force_to_admin("lng_adm_op=createLanguageTable");
    break;


  case "removeLanguageTableAction":
    if (isset($_POST["confirm_language_deletion"]) && !isset($_POST["deny_language_deletion"]))
      $_SESSION["OBJ_lang"]->removeLanguageTableAction($language);
    $_SESSION["OBJ_lang"]->force_to_admin();
    break;

  case "edit_lang_action":
    if($lang_edit_com)
      list($lang_com) = each($lang_edit_com);

    switch ($lang_com){

    case "insert_phrase":
    if ($_SESSION["OBJ_lang"]->insert_phrase($_SESSION["OBJ_lang"]->current_language, $lang_new_phrase, $lang_new_trans, $lang_mod_title))
      $_SESSION["OBJ_lang"]->editLanguage($language);
    else {
      $CNT_lang["content"] .= "<span class=\"errortext\">".$translate->it("Phrase <i>[var1]</i> already in use", $lang_new_phrase).".</span><br />";
      $_SESSION["OBJ_lang"]->editLanguage($language);
    }
    break;

    case "search_phrase":
      if ($lng_search_phrase){
	$_SESSION["OBJ_lang"]->createDictionary("phrase", $lng_search_phrase, 1);
	$_SESSION["OBJ_lang"]->edit_phrases();
      } else
	$_SESSION["OBJ_lang"]->editLanguage($language);
      break;

    case "search_translation":
      if ($lng_search_translation){
	$_SESSION["OBJ_lang"]->createDictionary("translation", $lng_search_translation, 1);
	$_SESSION["OBJ_lang"]->edit_phrases();
      } else
	$_SESSION["OBJ_lang"]->editLanguage($language);
    break;

    case "search_for_missing":
      $_SESSION["OBJ_lang"]->createDictionary("missing", NULL, 1);
    $_SESSION["OBJ_lang"]->edit_phrases("missing");
    break;


    case "compare_languages":
      $_SESSION["OBJ_lang"]->compare_languages($lng_compare);
      break;
    // End of lang_com switch
    }
    break;

  case "edit_phrase_action":
    $mode = 'missing';
    if (isset($lng_drop_phrase)){
      list($lng_drop_id) = each($lng_drop_phrase);
      $_SESSION["OBJ_lang"]->drop_phrase($lng_drop_id, $_SESSION["OBJ_lang"]->current_language);
      unset($_SESSION["OBJ_lang"]->indexed_dictionary[$lng_drop_id]);
      $_SESSION["OBJ_lang"]->edit_phrases();
    } else {

      if (isset($lng_edit_id)){
	while(list($lng_phrase_id) = each ($lng_edit_id)){
	  $_SESSION["OBJ_lang"]->update_phrase($lng_phrase_id, $lng_edit_module[$lng_phrase_id], $lng_edit_phrase[$lng_phrase_id], $lng_edit_translation[$lng_phrase_id], $_SESSION["OBJ_lang"]->current_language);
	}
      }

      if (isset($lng_search_phrase))
	$search = $lng_search_phrase;
      elseif (isset($lng_search_translation))
	$search = $lng_search_translation;
      else
	$search = NULL;

      $_SESSION["OBJ_lang"]->createDictionary($mode, $search, 1);
      $_SESSION["OBJ_lang"]->edit_phrases($mode);
    }
    break;

  case "mass_insert":
    foreach ($lng_add_comp_allow as $lng_allow){
      $_SESSION["OBJ_lang"]->insert_phrase($lng_upd_lang, $lng_add_phrase[$lng_allow], $lng_add_trans[$lng_allow], $lng_add_modname[$lng_allow]);
    }
    $_SESSION["OBJ_lang"]->editLanguage($language);
    break;


  case "list_phrases":
      $_SESSION["OBJ_lang"]->list_phrases();
    break;
  //End of lng_adm_op switch
  }
} elseif (isset($lng_adm_op)){
  exit();
}

if (isset($_REQUEST["lng_usr_op"])){
  switch($_REQUEST["lng_usr_op"]){
  case "user_admin":
    $_SESSION["translate"]->user_admin();
    break;
    
  case "setUserLanguage":
    $_SESSION["translate"]->setUserLanguage($_POST["user_language"]);
    break;
  }
}


?>