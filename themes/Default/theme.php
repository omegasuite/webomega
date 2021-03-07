<?php
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');

$THEME_DIRECTORY = "./themes/" . $_SESSION['OBJ_layout']->current_theme . "/";

extract($_REQUEST);
extract($_POST);

$HEADER = array();

$HEADER['LOGO'] = $THEME_DIRECTORY . "images/yezhu.JPG";
// $THEME['ORGNAME'] = '明轩供应链管理平台';
$THEME['PCTA'] = $THEME['PCTB'] = '40%';


if (!isset($_SESSION["OBJ_user"]->username) && $_REQUEST['module'] != 'users') {
	$THEME['PCTA'] = '30%'; $THEME['PCTB'] = '50%';
}
elseif (isset($_SESSION["OBJ_user"]->username)) {
    if ($_SESSION["OBJ_user"]->isDeity()) {
		$HEADER['CONTROLPANEL'] = '<li>
                                    <a href="./index.php?module=controlpanel"><i
                                            class="fa fa-sign-out pull-right"></i> 控制板块</a>
                                </li>';
	}
	
	$THEME['HEAD_SECTION'] = PHPWS_Template::processTemplate($HEADER, "layout", 'header.tpl');
}

if (!$GLOBALS['FULLSCREEN']) {
	$THEME['BODYWARP'] = '<div class="content">';	//  <div class="content_resize">';
    $THEME['BODYEND'] = '<div class="clr"></div>';	// </div>';
	$THEME['--BUTTS'] = '<br><br><br><br>
  <div class="footer" style="height:60px;">
    <div class="footer_resize"> <img src="' . $THEME_DIRECTORY . 'images/fbg_img.gif" alt="picture" width="931" height="16" />
      <p>Copyright &copy; 芜湖明轩科技有限公司<br />
          <a href="./index.php?module=knowledge&legaldoc=aboutus">关于我们</a> | <a href="./index.php?module=knowledge&legaldoc=clause">免责声明</a> | <a href="./index.php?module=knowledge&legaldoc=terms">用户协议</a> | <a href="./index.php?module=knowledge&legaldoc=privacy">隐私保护</a>
</p>
    </div>
    <div class="clr"></div>
  </div>';
}

if (!isset($_SESSION["OBJ_user"]->username) || !$_SESSION["OBJ_user"]->isDeity()) 
	$THEME['TOP'] = NULL;

function mainmenu() {
}

?>
