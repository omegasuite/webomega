<?php
//Code from SquirrelMail

$GLOBALS['core']->js_func[] = "
function CheckAll(form) {
   form = document.getElementsByName(form)[0];

   for (var i = 0; i < form.elements.length; i++) {
       if( form.elements[i].type == 'checkbox' ) {
           form.elements[i].checked = !(form.elements[i].checked);
       }
   }
}
";

$count = count($GLOBALS['core']->js_func);  // used in case multiple toggle on a page
$js = "<a name=\"#toggleAll$count\" /><a href=\"#toggleAll$count\" onclick=\"CheckAll('$form_name')\" >Toggle All</a>";

?>