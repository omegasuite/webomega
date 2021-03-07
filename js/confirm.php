<?php

require_once(PHPWS_SOURCE_DIR . 'core/Form.php');

$GLOBALS['core']->js_func[] = "
function confirmData_" . $section_name . "()
{
	if (confirm(\"$message\\nOK = YES, CANCEL = NO\"))
	location='$location';
}";

$js = PHPWS_Form::formButton($name, $value, "confirmData_" . $section_name . "();");

?>