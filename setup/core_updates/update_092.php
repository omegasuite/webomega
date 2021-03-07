<?php
if (!$_SESSION["OBJ_user"]->isDeity()){
  header("location:index.php");
  exit();
}

require_once(PHPWS_SOURCE_DIR . "mod/controlpanel/class/ControlPanel.php");

phpws_boost::installModule("controlpanel", TRUE, TRUE, TRUE);
$modules = $GLOBALS['core']->sqlSelect("modules");

foreach ($modules as $modInfo){
  if (!(PHPWS_ControlPanel::import($modInfo['mod_title']))){
    $needIcon[] = $modInfo['mod_directory'];
  }
}

if (isset($needIcon)){
  foreach ($needIcon as $modDir){
    $mod_icon = $user_icon = NULL;
    $user_op = $admin_op = NULL;
    $admin_mod = $user_mod = $deity_mod = NULL;
    
    $link = NULL;
    $boostFile = PHPWS_SOURCE_DIR . "mod/$modDir/conf/boost.php"; 
    if (is_file($boostFile)){
      include ($boostFile);
      if (isset($admin_mod) || isset($user_mod)){
	$link = new PHPWS_ControlPanel_Link;
	$link->setLabel($mod_pname);
	$link->setModule($mod_title);

	if (isset($admin_mod) && (bool)$admin_mod == TRUE){
	  $link->setURL("index.php?module=" . $mod_title . $admin_op);

	  if (isset($mod_icon))
	    $link->setImage(array("name"=>$mod_icon, "alt"=>$mod_pname));


	  $link->setAdmin(TRUE);
	  $result = $link->save();
	  if (PHPWS_Error::isError($result))
	    echo $result->message;
	  else {
	    $tab = new PHPWS_ControlPanel_Tab;
	    if (isset($deity_mod) && (bool)$deity_mod == TRUE)
	      $tab->load("administration");
	    else
	      $tab->load("content");

	    $tab->addLink($link->getId());
	    $tab->save();
	  }
	}
	
	if (isset($user_mod) && (bool)$user_mod == TRUE){
	  $link->setURL("index.php?module=" . $mod_title . $user_op);
	  if (isset($user_icon))
	    $link->setImage(array("name"=>$user_icon, "alt"=>$mod_pname));
	  $link->setAdmin(FALSE);
	  $result = $link->save();
	  if (PHPWS_Error::isError($result))
	    echo $result->message;
	  else {
	    $tab = new PHPWS_ControlPanel_Tab;
	    $tab->load("my_settings");

	    $tab->addLink($link->getId());
	    $tab->save();


	  }
	}
      }
    }
  }
}

$GLOBALS['core']->sqlDropColumn("modules", array("admin_op", "user_op", "mod_icon", "user_icon"));

?>