<?php

include(PHPWS_SOURCE_DIR.'conf/javascriptSettings.php');

if(!$wysiwyg_on) return;

require_once(PHPWS_SOURCE_DIR.'core/Text.php');

if(!isset($GLOBALS['wysiwyg_tab_index']))
     $GLOBALS['wysiwyg_tab_index'] = 30;    // set this number higher if you need to use forms with many elements

if(!isset($GLOBALS['wysiwyg'])) $GLOBALS['wysiwyg'] = 1;

if($GLOBALS['wysiwyg'] == 1) {

$GLOBALS['core']->js_func[] = "

var body=0;
var opcode='';

function addBold(form, section){
        form = document.getElementsByName(form)[0];
        eval('form.'+section+'.value=form.'+section+'.value + \"<b>Bold Text</b>\"');
}

function addBreak(form, section){
        form = document.getElementsByName(form)[0];
        eval('form.'+section+'.value=form.'+section+'.value + \"<br />\\\n\"');
}

function addItal(form, section){
        form = document.getElementsByName(form)[0];
        eval('form.'+section+'.value=form.'+section+'.value + \"<i>Italicized Text</i>\"');
}

function addUnder(form, section){
        form = document.getElementsByName(form)[0];
        eval('form.'+section+'.value=form.'+section+'.value + \"<u>Underlined Text</u>\"');
}

function addAleft(form, section){
        form = document.getElementsByName(form)[0];
        div = '<div align=\\\"left\\\">Left Justified Text</div>';
        eval('form.'+section+'.value=form.'+section+'.value + div');
}

function addAcenter(form, section){
        form = document.getElementsByName(form)[0];
        div = '<div align=\\\"center\\\">Centered Text</div>';
        eval('form.'+section+'.value=form.'+section+'.value + div');
}

function addAright(form, section){
        form = document.getElementsByName(form)[0];
        div = '<div align=\\\"right\\\">Right Justified Text</div>';
        eval('form.'+section+'.value=form.'+section+'.value + div');
}

function addUlist(form, section){ 
        form = document.getElementsByName(form)[0];
        ul = '<ul type=\\\"disc\\\">\\r\\n  <li>Item 1</li>\\r\\n  <li>Item 2</li>\\r\\n  <li>Item 3</li>\\r\\n</ul>\\\r\\n';
        eval('form.'+section+'.value=form.'+section+'.value + ul');
}

function addOlist(form, section){ 
        form = document.getElementsByName(form)[0];
        ol = '<ol type=\\\"1\\\">\\r\\n  <li>Item 1</li>\\r\\n  <li>Item 2</li>\\r\\n  <li>Item 3</li>\\r\\n</ol>\\r\\n';
        eval('form.'+section+'.value=form.'+section+'.value + ol');
}

function addBlock(form, section){ 
        form = document.getElementsByName(form)[0];
        block = '<blockquote>\\r\\n  <p>Your indented text here...</p>\\r\\n</blockquote>\\r\\n';
        eval('form.'+section+'.value=form.'+section+'.value + block');
}

function addEmail(form, section){ 
        form = document.getElementsByName(form)[0];
        email = '<a href=\\\"mailto:email@address.here\\\">Click Text Here</a>';
        eval('form.'+section+'.value=form.'+section+'.value + email');
}

function addLink(form, section){
        form = document.getElementsByName(form)[0];
        link = '<a href=\\\"http://www.web_address.here\\\">Click Text Here</a>';
        eval('form.'+section+'.value=form.'+section+'.value + link');
}\n";

}

$js = "<a name=\"wysiwyg{$GLOBALS['wysiwyg']}\" />\n";
$js .= "<a href=\"#wysiwyg{$GLOBALS['wysiwyg']}\" tabindex=\"".$GLOBALS['wysiwyg_tab_index']++."\" onclick=\"addBold('{$form_name}', '{$section_name}');\" onmouseover=\"window.status='Add bold'; return true;\" onmouseout=\"window.status='';\">" . phpws_text::imageTag("./images/javascript/wysiwyg/bold.gif", "Bold", 21, 20) . "</a>\n";
$js .= "<a href=\"#wysiwyg{$GLOBALS['wysiwyg']}\" tabindex=\"".$GLOBALS['wysiwyg_tab_index']++."\" onclick=\"addItal('{$form_name}', '{$section_name}');\" onmouseover=\"window.status='Add italic'; return true;\" onmouseout=\"window.status='';\">" . phpws_text::imageTag("./images/javascript/wysiwyg/italic.gif", "Italic", 21, 20) . "</a>\n";
$js .= "<a href=\"#wysiwyg{$GLOBALS['wysiwyg']}\" tabindex=\"".$GLOBALS['wysiwyg_tab_index']++."\" onclick=\"addUnder('{$form_name}', '{$section_name}');\" onmouseover=\"window.status='Add underline'; return true;\" onmouseout=\"window.status='';\">" . phpws_text::imageTag("./images/javascript/wysiwyg/underline.gif", "Underlined", 21, 20) . "</a>\n";
$js .= "<a href=\"#wysiwyg{$GLOBALS['wysiwyg']}\" tabindex=\"".$GLOBALS['wysiwyg_tab_index']++."\" onclick=\"addAleft('{$form_name}', '{$section_name}');\" onmouseover=\"window.status='Add left justified'; return true;\" onmouseout=\"window.status='';\">" . phpws_text::imageTag("./images/javascript/wysiwyg/aleft.gif", "Left Justified", 21, 20) . "</a>\n";
$js .= "<a href=\"#wysiwyg{$GLOBALS['wysiwyg']}\" tabindex=\"".$GLOBALS['wysiwyg_tab_index']++."\" onclick=\"addAcenter('{$form_name}', '{$section_name}');\" onmouseover=\"window.status='Add centered'; return true;\" onmouseout=\"window.status='';\">" . phpws_text::imageTag("./images/javascript/wysiwyg/acenter.gif", "Center Text", 21, 20) . "</a>\n";
$js .= "<a href=\"#wysiwyg{$GLOBALS['wysiwyg']}\" tabindex=\"".$GLOBALS['wysiwyg_tab_index']++."\" onclick=\"addAright('{$form_name}', '{$section_name}');\" onmouseover=\"window.status='Add right justified'; return true;\" onmouseout=\"window.status='';\">" . phpws_text::imageTag("./images/javascript/wysiwyg/aright.gif", "Right Justified", 21, 20) . "</a>\n";
$js .= "<a href=\"#wysiwyg{$GLOBALS['wysiwyg']}\" tabindex=\"".$GLOBALS['wysiwyg_tab_index']++."\" onclick=\"addUlist('{$form_name}', '{$section_name}');\" onmouseover=\"window.status='Add unordered list'; return true;\" onmouseout=\"window.status='';\">" . phpws_text::imageTag("./images/javascript/wysiwyg/bullet.gif", "Bulleted List", 21, 20) . "</a>\n";
$js .= "<a href=\"#wysiwyg{$GLOBALS['wysiwyg']}\" tabindex=\"".$GLOBALS['wysiwyg_tab_index']++."\" onclick=\"addOlist('{$form_name}', '{$section_name}');\" onmouseover=\"window.status='Add ordered list'; return true;\" onmouseout=\"window.status='';\">" . phpws_text::imageTag("./images/javascript/wysiwyg/numbered.gif", "Numbered List", 21, 20) . "</a>\n";
$js .= "<a href=\"#wysiwyg{$GLOBALS['wysiwyg']}\" tabindex=\"".$GLOBALS['wysiwyg_tab_index']++."\" onclick=\"addBlock('{$form_name}', '{$section_name}');\" onmouseover=\"window.status='Add block quote'; return true;\" onmouseout=\"window.status='';\">" . phpws_text::imageTag("./images/javascript/wysiwyg/increase.gif", "Increase", 21, 20) . "</a>\n";
$js .= "<a href=\"#wysiwyg{$GLOBALS['wysiwyg']}\" tabindex=\"".$GLOBALS['wysiwyg_tab_index']++."\" onclick=\"addEmail('{$form_name}', '{$section_name}');\" onmouseover=\"window.status='Add email'; return true;\" onmouseout=\"window.status='';\">" . phpws_text::imageTag("./images/javascript/wysiwyg/email.gif", "Email", 21, 20) . "</a>\n";
$js .= "<a href=\"#wysiwyg{$GLOBALS['wysiwyg']}\" tabindex=\"".$GLOBALS['wysiwyg_tab_index']++."\" onclick=\"addLink('{$form_name}', '{$section_name}');\" onmouseover=\"window.status='Add link'; return true;\" onmouseout=\"window.status='';\">" . phpws_text::imageTag("./images/javascript/wysiwyg/link.gif", "Link", 21, 20) . "</a>\n";
$js .= "<a href=\"#wysiwyg{$GLOBALS['wysiwyg']}\" tabindex=\"".$GLOBALS['wysiwyg_tab_index']++."\" onclick=\"addBreak('{$form_name}', '{$section_name}');\" onmouseover=\"window.status='Add break'; return true;\" onmouseout=\"window.status='';\">" . phpws_text::imageTag("./images/javascript/wysiwyg/break.gif", "Break", 20, 20) . "</a>\n";

if ($_SESSION['OBJ_user']->js_on && extension_loaded('pspell') && $ssc_on) {

    if (!isset($GLOBALS['ssc'])) {
	$GLOBALS['ssc'] = true;
	
	$GLOBALS['core']->js_func[] = "
function sscCheckText(section) {
   element = document.getElementById(section);

   if(element.value == \"\") {
      alert('There is no text to be checked for spelling errors.');
   } else {
      loc = './js/ssc/speller.php?ssc_lang={$ssc_lang}&ssc_speed={$ssc_speed}&section=' + section + '&style=' + '../../{$_SESSION['OBJ_layout']->theme_address}' + 'style.css';
      window.open(loc, '_BLANK', 'width=800,height=600,toolbar=no,scrollbars=yes,status=yes,top=50,left=50,screenX=50,screenY=50');
   }
}
";

    }
    
    $js .= "<a href=\"#wysiwyg{$GLOBALS['wysiwyg']}\" tabindex=\"".$GLOBALS['wysiwyg_tab_index']++."\" onclick=\"sscCheckText('{$section_name}');\" onmouseover=\"window.status='Spell Checker'; return true;\" onmouseout=\"window.status='';\">" . phpws_text::imageTag("./images/javascript/wysiwyg/spell.gif", "Spell Checker", 20, 20) . "</a>\n";
}

$js .= "<br />\n";

$GLOBALS['wysiwyg']++;

?>