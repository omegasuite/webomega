<?php

require_once(PHPWS_SOURCE_DIR.'core/Form.php');

$js = PHPWS_Form::formHidden("js_on", "0")."
form = document.getElementsByName('{$form_name}');
form.js_on.value=\"1\";
";

?>