<?php

if(file_exists("../conf/config.php")) {
  include("../conf/config.php");
} else {
  exit("No configuration file found in ../conf/");
}

/* Comment this line if you wish to use your system's pear libs */
ini_set("include_path", ".:" . $source_dir . "lib/pear/");

require_once("HTML/Template/IT.php");

if(file_exists("../conf/config.php")) {
  if(isset($_REQUEST["INSTALL_PASSWORD"])) {
    include("../conf/config.php");

    if($install_pw == $_REQUEST["INSTALL_PASSWORD"]) {
      clean_directory("../images/");
      clean_directory("../files/");
      @mkdir("../images");
      @mkdir("../files");

      $tags["CONTENT"] = "<h3>All files have successfully been removed!</h3>";
      show($tags);
    } else {
      $tags["CONTENT"] = "<h3>The install password you entered was incorrect!</h3>";
      $tags["CONTENT"] .= "<form method=\"POST\" action=\"clean.php\">
       <p>Please enter your installation password:</p>
       <input type=\"password\" name=\"INSTALL_PASSWORD\" size=\"33\" maxsize=\"255\" />
       <input type=\"submit\" name=\"CLEAN\" value=\"CLEAN MY FILES\" />
       </form>";
      show($tags);
    }
  } else {
    $tags["CONTENT"] = "<form method=\"POST\" action=\"clean.php\">
       <p>Please enter your installation password:</p>
       <input type=\"password\" name=\"INSTALL_PASSWORD\" size=\"33\" maxsize=\"255\" />
       <input type=\"submit\" name=\"CLEAN\" value=\"CLEAN MY FILES\" />
       </form>";
    show($tags);
  }
} else {
  header("set_config.php");
}

function show($tags) {
  $tpl = new HTML_Template_IT(".");
  $tpl->loadTemplatefile("clean.tpl", TRUE, TRUE);
  $tpl->setVariable($tags);
  echo $tpl->get();
}// END FUNC show

function clean_directory($dir) {
  if(is_dir($dir)) {
    $handle = opendir($dir);
    while($file = readdir($handle)) {
      if($file == "." || $file == "..") {
	continue;
      } elseif(is_dir($dir . $file)) {
	clean_directory($dir . $file . "/");
      } elseif(is_file($dir . $file)) {
	unlink($dir . "/" . $file);
      }
    }
    closedir($handle);
    @rmdir($dir);
    return TRUE;
  } else {
    return FALSE;
  }
}// END FUNC clean_directory

?>