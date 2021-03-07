<?php

require_once (PHPWS_SOURCE_DIR . "core/Text.php");

require_once (PHPWS_SOURCE_DIR . "core/Array.php");

require_once(PHPWS_SOURCE_DIR . "core/File.php");

class PHPWS_Layout_Box {

  function loadBoxInfo(){
    unset($this->themeVarList);

    $sql = $GLOBALS["core"]->sqlSelect("mod_layout_box", "theme", $this->current_theme, "box_order asc");
    $this->buildThemeVarList();

    if ($sql){
      foreach ($sql as $result){
	extract ($result);
	$this->ordered_list[$theme_var]["content"][$box_order] = $content_var;

	$this->content_array[$content_var]["theme_var"] = $theme_var;
	$this->content_array[$content_var]["box_order"] = $box_order;
	$this->content_array[$content_var]["id"] = $id;
	$this->content_array[$content_var]["box_file"] = $box_file;
	$this->content_array[$content_var]["popbox"] = $popbox;
	$this->content_array[$content_var]["home_only"] = $home_only;
      }
    } else {
      $this->_buildBoxes($this->current_theme);
      $this->loadBoxInfo();
      return;
    }

    if ($this->themeVarList){
      foreach ($this->themeVarList as $row){
	if (isset($this->ordered_list[$row]["content"]) && is_array($this->ordered_list[$row]["content"]))
	  ksort($this->ordered_list[$row]["content"]);
      }
    }

  }

  function rebuildThemeBoxes($theme, $theme_var){
    $count = 0;
    $where["theme"] = $theme;
    $where["theme_var"] = $theme_var;

    if ($sql = $GLOBALS["core"]->sqlSelect("mod_layout_box", $where, NULL, "box_order asc")){
      foreach ($sql as $boxes){
	$count++;
	$update["box_order"] = $count;
	
	$GLOBALS["core"]->sqlUpdate($update, "mod_layout_box", "id", $boxes["id"]);
      }
    }
    
  }

  function buildThemeVarList(){
    if ($file = PHPWS_File::readFile($this->theme_dir. "transfers.tpl")){
      $vars = explode("\n", $file);
      PHPWS_Array::dropNulls($vars);
      foreach ($vars as $row){
	$sub_row = explode(":", $row);
	$this->ordered_list[$sub_row[0]] = array ("section"=>$sub_row[1], "row"=>$sub_row[2]);
	$this->themeVarList[] = $sub_row[0];
	$this->row_col[$sub_row[1]][$sub_row[2]] = $sub_row[0];
	ksort($this->row_col);
	ksort($this->row_col[$sub_row[1]]);
      }
    } else
      exit("Unable to load " . $this->theme_dir . "transfers.tpl");
  }

  /**
   * Creates a new content var
   *
   */

  function create_temp($mod_title, $content_var, $theme_var, $home_only=NULL){
    $themeList = PHPWS_Layout::get_themes();
    foreach ($themeList as $theme)
      $this->_addBox($theme, $mod_title, $content_var, $theme_var, $home_only);

    if (isset($_SESSION["OBJ_layout"])){
      $_SESSION["OBJ_layout"]->initializeLayout();
      $_SESSION["OBJ_layout"]->loadBoxInfo();
    }
  }


  /**
   * Builds a theme's box list if none exists
   *
   * @author Matthew McNaney<matt@NOSPAM.tux.appstate.edu>
   */
  function _buildBoxes($buildTheme){
    if (!$this->themeExists($buildTheme))
      return;

    $this->_dropTheme($buildTheme);
    $moduleList = $GLOBALS["core"]->listModules();

    foreach ($moduleList as $mod_title)
      $this->installModule($mod_title);

    if ($buildTheme != "Default"){
      if($sql = $GLOBALS["core"]->sqlSelect("mod_layout_box", "theme", "Default")){
	foreach ($sql as $row){
	  extract($row);
	  $this->_addBox($buildTheme, $mod_title, $content_var, $theme_var, $home_only, $box_file, $popbox);
	}
      }
    }
  }


  function _addBox($theme, $mod_title, $content_var, $theme_var=NULL, $home_only=NULL, $default_box=NULL, $default_pop=NULL){
    if ($GLOBALS["core"]->getOne("select id from mod_layout_box where theme='$theme' and mod_title='$mod_title' and content_var='$content_var'", TRUE))
      return;

    $defaultDir = PHPWS_HOME_DIR . "themes/". $theme . "/defaults/";

    $modFile = $defaultDir . $mod_title . ".php";
    $defaultFile = $defaultDir . "default.php";

    if (is_null($default_box) && is_null($default_pop)){
      if (is_file($modFile))
	include ($modFile);
      elseif (is_file($defaultFile))
	include ($defaultFile);
      else {
	$default_box = "default_box.tpl";
	$default_pop = "default_pop.tpl";
      }
    }

    if (isset($boxstyles)){
      if (isset($boxstyles[$content_var]["default_box"]))
	$default_box = $boxstyles[$content_var]["default_box"];

      if (isset($boxstyles[$content_var]["default_pop"]))
	$default_pop = $boxstyles[$content_var]["default_pop"];
    }

    $insert["box_file"]  = $default_box;
    $insert["popbox"]    = $default_pop;

    $insert["theme"]     = $theme;
    $insert["mod_title"] = $mod_title;

    $insert["content_var"] = $content_var;

    if (!$this->themeVarList)
      $this->buildThemeVarList();

    if (!$theme_var || !in_array($theme_var, $this->themeVarList))
      $theme_var = "body";

    $insert["theme_var"] = $theme_var;
    
    $maxWhere["theme_var"] = $theme_var;
    $maxWhere["theme"]     = $theme;

    $box_order = $GLOBALS["core"]->sqlMaxValue("mod_layout_box", "box_order", $maxWhere);
    if (is_null($box_order))
      $insert["box_order"] = 1;
    else
      $insert["box_order"] = $box_order+1;

    if (is_null($home_only))
      $insert["home_only"] = 0;
    else
      $insert["home_only"] = $home_only;

    $GLOBALS["core"]->sqlInsert($insert, "mod_layout_box");
  }

  function moveTop($content_var){
    $topInfo["lay_adm_op"] = $left_info["lay_adm_op"] = $up_info["lay_adm_op"] = "move_box";
    $topInfo["move_content_var"] = $left_info["move_content_var"] = $up_info["move_content_var"] = $content_var;
    $left_info["direction"] = "left";
    $up_info["direction"] = "up";
    $topInfo["direction"] = "top";

    $theme_var = $this->content_array[$content_var]["theme_var"];
    $box_order = $this->content_array[$content_var]["box_order"];
    $section = $this->ordered_list[$theme_var]["section"];
    $row = $this->ordered_list[$theme_var]["row"];

    $left = PHPWS_Text::imageTag(PHPWS_SOURCE_HTTP."mod/layout/img/left.gif");
    $up = PHPWS_Text::imageTag(PHPWS_SOURCE_HTTP."mod/layout/img/up.gif");
    $dup = PHPWS_Text::imageTag(PHPWS_SOURCE_HTTP."mod/layout/img/dup.gif");

    $link_top = PHPWS_Text::moduleLink($left, "layout", $left_info)
      . PHPWS_Text::moduleLink($up, "layout", $up_info)
      . PHPWS_Text::moduleLink($dup, "layout", $topInfo)
      . " <span class=\"smalltext\" style=\"background-color : white; color : black;\">$section : $row : $box_order</span>";

    return $link_top;
  }

  function moveBottom($content_var){
    $bottomInfo["lay_adm_op"] = $right_info["lay_adm_op"] = $down_info["lay_adm_op"] = "move_box";
    $bottomInfo["move_content_var"] = $right_info["move_content_var"] = $down_info["move_content_var"] = $content_var;
    $right_info["direction"] = "right";
    $down_info["direction"] = "down";
    $bottomInfo["direction"] = "bottom";

    $right = PHPWS_Text::imageTag(PHPWS_SOURCE_HTTP."mod/layout/img/right.gif");
    $down = PHPWS_Text::imageTag(PHPWS_SOURCE_HTTP."mod/layout/img/down.gif");
    $ddown = PHPWS_Text::imageTag(PHPWS_SOURCE_HTTP."mod/layout/img/ddown.gif");

    $theme_var = $this->content_array[$content_var]["theme_var"];
    //    $box_order = PHPWS_Text::moduleLink($this->content_array[$content_var]["box_order"], "layout", $bottomInfo);
    $box_order = $this->content_array[$content_var]["box_order"];
    $section = $this->ordered_list[$theme_var]["section"];
    $row = $this->ordered_list[$theme_var]["row"];

    $link_bottom = "<span class=\"smalltext\" style=\"background-color : white; color : black;\">$section : $row : $box_order</span> "
      . PHPWS_Text::moduleLink($ddown, "layout", $bottomInfo)
      . PHPWS_Text::moduleLink($down, "layout", $down_info)
      . PHPWS_Text::moduleLink($right, "layout", $right_info);

    return $link_bottom;
  }

  function move_box(){
    extract($_REQUEST);

    if ($direction == "up" || $direction == "down" || $direction == "top" || $direction == "bottom")
      $this->shift_vert($move_content_var, $direction, 1);
    elseif ($direction == "left" || $direction == "right")
      $this->shift_horz($move_content_var, $direction);
  }

  function shift_horz($content_var, $direction){
    if ($direction != "left" && $direction != "right")
      exit("shift_horz requires a direction of left or right");
    $theme_var = $this->content_array[$content_var]["theme_var"];     // Theme variable of current content_var
    $current_section = $this->ordered_list[$theme_var]["section"];   // Current section of theme var
    $current_order = $this->content_array[$content_var]["box_order"];       // Where in the list the current content var sits
    $current_row = $this->ordered_list[$theme_var]["row"];           // Array of content vars in the theme var

    if ($direction == "right"){
      if (isset($this->row_col[$current_section+1]))
	$shift_section = $current_section+1;
      else
	$shift_section = 1;
    } else {
      if ($current_section != 1){
	$shift_section = $current_section - 1;
      } else {
	$shift_section = count($this->row_col[$current_section]);
      }
    }

    if ($this->row_col[$shift_section][$current_row])
      $shift_row = $current_row;
    else
      $shift_row = PHPWS_Array::maxKey($this->row_col[$shift_section]);

    $horz_theme = $this->row_col[$shift_section][$shift_row];
    $old_rows = $this->ordered_list[$theme_var]["content"];
    $count = 0;
    unset($old_rows[$current_order]);
    $replace_rows = array();
    if (is_array($old_rows) && count($old_rows)){
      foreach ($old_rows as $new_var){
	$count++;
	$replace_rows[$count] = $new_var;
      }
    }

    if(isset($this->ordered_list[$horz_theme]["content"]) && ($horz_rows = $this->ordered_list[$horz_theme]["content"]))
      $horz_rows[] = $content_var;
    else
      $horz_rows[1] = $content_var;
    $this->set_box_order($theme_var, $replace_rows);
    $this->set_box_order($horz_theme, $horz_rows);
  }

  function shift_vert($content_var, $direction, $jump=NULL){
    $count=0;
    if ($direction != "up" && $direction != "down" && $direction != "top" && $direction != "bottom")
      return;

    $theme_var  = $this->content_array[$content_var]["theme_var"];   // Theme variable of current content_var
    $total_vars = count($this->ordered_list[$theme_var]["content"]); // Total content vars using this theme variable
    $var_order  = $this->content_array[$content_var]["box_order"];   // Where in the list the current content var sits
    $all_vars   = $this->ordered_list[$theme_var]["content"];        // Array of content vars in the theme var

    if ($var_order == 1 && $direction == "top"){
      $this->jump_theme($content_var, $theme_var, "up");
      $box_order = $this->content_array[$content_var]["box_order"];
      $jump_tvar = $this->content_array[$content_var]["theme_var"];
      $new_content = $this->ordered_list[$jump_tvar]["content"];
      $stop = 1;
    }
    elseif ($direction == "top"){
      $count = 1;
      unset($all_vars[$var_order]);
      $newOrder[1] = $content_var;
      foreach ($all_vars as $tempVar){
	$count++;
	$newOrder[$count]= $tempVar;
      }

      $all_vars = $newOrder;
    }
    elseif ($var_order == 1 && $direction == "up"){
      if (!$jump){
	if ($total_vars > 1){
	  $temp_var = $content_var;

	  // Shift all other content vars down
	  for ($i = 1; $i <= $total_vars - 1; $i++){
	    $new_row[$i] = $all_vars[$i+1];
	  }
	  $all_vars = $new_row;
	  $all_vars[] = $temp_var;
	}
      } else {
	$this->jump_theme($content_var, $theme_var, "up");
	$box_order = $this->content_array[$content_var]["box_order"];
	$jump_tvar = $this->content_array[$content_var]["theme_var"];
	$new_content = $this->ordered_list[$jump_tvar]["content"];
	$stop = 1;
      }
    } elseif($var_order == $total_vars && $direction == "bottom"){
	$this->jump_theme($content_var, $theme_var, "down");
	$stop = 1;
    } elseif ($direction == "bottom"){
      $holdVar = $all_vars[$var_order];
      unset($all_vars[$var_order]);
      foreach ($all_vars as $tempVar){
	$count++;
	$newOrder[$count]= $tempVar;
      }
      $newOrder[$count+1] = $holdVar;
      $all_vars = $newOrder;
    } elseif ($var_order == $total_vars && $direction == "down"){
      if (!$jump){
	$temp_var = $content_var;
	for ($i = 2; $i <= $total_vars; $i++){
	  $new_row[$i] = $all_vars[$i-1];
	}
	$all_vars = $new_row;
	$all_vars[1] = $temp_var;
      } else {
	$this->jump_theme($content_var, $theme_var, "down");
	$stop = 1;
      }
    } else {
      if ($direction == "up")
	$row_move = $var_order - 1;
      else
	$row_move = $var_order + 1;

      $temp_var = $all_vars[$row_move];
      $all_vars[$row_move] = $content_var;
      $all_vars[$var_order] = $temp_var;
    }
    ksort($all_vars);

    if (!isset($stop)){
      $this->set_box_order($theme_var, $all_vars);
      $this->loadBoxInfo();
    }
   
  }


  function updateBox($content_var, $filename){
    if (!isset($this->current_theme))
      return;

    if (!(preg_match("/\.tpl\$/i", $filename)))
      exit("updateBox was sent a non-template filename");
    
    $update["box_file"] = $filename;
    
    $where["content_var"] = $content_var;
    $where["theme"] = $this->current_theme;

    $GLOBALS["core"]->sqlUpdate($update, "mod_layout_box", $where);
    $_SESSION["OBJ_layout"]->initializeLayout();
    $_SESSION["OBJ_layout"]->loadBoxInfo();
  }

  function updatePop($content_var, $filename){
    if (!(preg_match("/\.tpl\$/i", $filename)))
      exit("updatePop was sent a non-template filename");
    
    $update["popbox"] = $filename;
    
    $where["content_var"] = $content_var;
    $where["theme"] = $this->current_theme;

    $GLOBALS["core"]->sqlUpdate($update, "mod_layout_box", $where);
    $_SESSION["OBJ_layout"]->initializeLayout();
    $_SESSION["OBJ_layout"]->loadBoxInfo();
  }

  function jump_theme($content_var, $theme_var, $direction){
    $count = 0;
    if ($direction != "up" && $direction != "down")
      exit("jump_theme must receive a direction of up or down");

    $trans_section = $this->ordered_list[$theme_var]["section"]; // Current section the theme variable is located
    $trans_row = $this->ordered_list[$theme_var]["row"];         // Current row the theme variable is located
    $max_section = PHPWS_Array::maxKey($this->row_col);                  // Last section number

    $max_row = PHPWS_Array::maxKey($this->row_col[$trans_section]);      // Last row number of current section
    $updated_rows = $this->ordered_list[$theme_var]["content"];  // Content var array that will need to updated
    $old_key = $this->content_array[$content_var]["box_order"];         // Index of current content_var

    unset($updated_rows[$old_key]);

    $final_row = array();
    if (is_array($updated_rows) && count($updated_rows)){
      foreach ($updated_rows as $row){
	$count++;
	$final_row[$count] = $row; // final_row contains the updated content_var list after removal of the content_var
	//$final_row[] = $row; // final_row contains the updated content_var list after removal of the content_var
      }
    }
    
    if ($direction == "up" || $direction == "down"){
      if ($trans_row == 1 && $direction == "up"){

	$jump_tvar = $this->row_col[$trans_section][$max_row];
	if (isset($this->ordered_list[$jump_tvar]["content"]) && ($jump_rows = $this->ordered_list[$jump_tvar]["content"])){
	  $jump_rows[] = $content_var;
	} else {
	  $jump_rows[1] = $content_var;
	}
	ksort($jump_rows);
	$this->set_box_order($jump_tvar, $jump_rows);
      } elseif ($trans_row == $max_row && $direction == "down"){
	$jump_tvar = $this->row_col[$trans_section][1];
	if(isset($this->ordered_list[$jump_tvar]["content"]) && ($jump_rows = $this->ordered_list[$jump_tvar]["content"])){
	  foreach($jump_rows as $key=>$value){
	    $new_rows[$key+1] = $value;
	  }
	}
	$new_rows[1] = $content_var;
	$this->set_box_order($jump_tvar, $new_rows);
      } else {
	if ($direction == "up"){
	  $jump_tvar = $this->row_col[$trans_section][$trans_row-1];
	  if(isset($this->ordered_list[$jump_tvar]["content"]) && ($jump_rows = $this->ordered_list[$jump_tvar]["content"]))
	    $jump_rows[] = $content_var;
	  else
	    $jump_rows[1] = $content_var;
	}
	elseif ($direction == "down"){
	  $jump_tvar = $this->row_col[$trans_section][$trans_row+1];
	  if(isset($this->ordered_list[$jump_tvar]["content"]) && ($jump_rows = $this->ordered_list[$jump_tvar]["content"])){
	    foreach($jump_rows as $key=>$value){
	      $new_rows[$key+1] = $value;
	      $new_rows[1] = $content_var;
	    }
	    $jump_rows = $new_rows;
	  } else
	    $jump_rows[1] = $content_var;
	}
	$this->set_box_order($jump_tvar, $jump_rows);
	
      }
      $this->set_box_order($theme_var, $final_row);
    }
  }

  function getThemeVar($theme, $content_var){
    return $GLOBALS["core"]->sqlSelect("mod_layout_box", array("content_var"=>$content_var, "theme"=>$theme));
  }


  function installModule($mod_title){
    $home_only = NULL;
    $themeList = PHPWS_Layout::get_themes();
    $modDir = PHPWS_SOURCE_DIR . "mod/" . $GLOBALS["core"]->getModuleDir($mod_title) . "/conf/layout.php";
    if (!file_exists($modDir))
      return FALSE;
    
    include($modDir);
    if (!isset($layout_info))
      return FALSE;

    foreach ($themeList as $theme){
      foreach ($layout_info as $value){
	unset($transfer_var);
	unset($theme_var);
	unset($home_only);

	extract($value);
	if (isset($transfer_var))
	  $theme_var = $transfer_var;

	if(!isset($home_only))
	  $home_only = NULL;

	$this->_addBox($theme, $mod_title, $content_var, $theme_var, $home_only);
      }
    }
    $this->initializeLayout();
    $this->loadBoxInfo();
    return TRUE;
  }
}
?>