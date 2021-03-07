Here is what you need to know in order to use help with you module.  First you need to register you module with help.
This is done by adding this statement to your ./boost/install.php file.  

CLS_help::setup_help("your_mod_name");

Also in your ./boost/uninstall.php

CLS_help::uninstall_help("your_mod_name");

You will also need a ./conf/help.php file.  This file will have statements like this in it.

$main_menu = "Main Menu";
$main_menu_content = "This is the main menu.";

These two statement set the data for you help item "main_menu". You can make what ever you want just keep the
variable names different.  To use the "main_menu" help item in your code.  You will need to use a statement like this.

$_SESSION["OBJ_help"]->show_link("your_mod_name", "main_menu");
