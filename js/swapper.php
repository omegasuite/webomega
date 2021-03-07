<?php

if($_SESSION['OBJ_user']->js_on) {
if($js_fun) {
$GLOBALS['core']->js_func[] = "
<!-- Original:  Phil Webb (phil@philwebb.com) -->
<!-- Web Site:  http://www.philwebb.com -->

<!-- This script and many more are available free online at -->
<!-- The JavaScript Source!! http://javascript.internet.com -->

function move(fbox, tbox, sort) {
  var arrFbox = new Array();
  var arrTbox = new Array();
  var arrLookup = new Array();
  var i;
  for (i = 0; i < tbox.options.length; i++) {
    arrLookup[tbox.options[i].text] = tbox.options[i].value;
    arrTbox[i] = tbox.options[i].text;
  }
  var fLength = 0;
  var tLength = arrTbox.length;
  for(i = 0; i < fbox.options.length; i++) {
    arrLookup[fbox.options[i].text] = fbox.options[i].value;
    if (fbox.options[i].selected && fbox.options[i].value != \"\") {
      arrTbox[tLength] = fbox.options[i].text;
      tLength++;
    }
    else {
      arrFbox[fLength] = fbox.options[i].text;
      fLength++;
    }
  }

  if(sort == true) {
   arrFbox.sort(compare);
   arrTbox.sort(compare);
  }

  fbox.length = 0;
  tbox.length = 0;
  var c;
  for(c = 0; c < arrFbox.length; c++) {
    var no = new Option();
    no.value = arrLookup[arrFbox[c]];
    no.text = arrFbox[c];
    fbox[c] = no;
  }
  for(c = 0; c < arrTbox.length; c++) {
    var no = new Option();
    no.value = arrLookup[arrTbox[c]];
    no.text = arrTbox[c];
    tbox[c] = no;
  }
}

function compare(a, b) {
  var anew = a.toLowerCase();
  var bnew = b.toLowerCase();
  if (anew < bnew) return -1;
  if (anew > bnew) return 1;
  return 0;
}

function selectAll(box) {
	for(var i=0; i<box.length; i++) {
		box.options[i].selected = true;
	}
}
";
}

$keys = array_keys($js_var_array);
 
$sort = TRUE;
if(isset($js_var_array['sorting']))
  $sort = $js_var_array['sorting'];

$js_var_array[$keys[0]] = array_diff($js_var_array[$keys[0]], $js_var_array[$keys[1]]);
  
$js = "<table border=\"0\" cellpadding=\"4\" cellspacing=\"0\">\n
<tr><td><select name=\"" . $keys[0] . "[]\" multiple=\"multiple\" size=\"10\" ondblclick=\"move(this.form.elements['" . $keys[0] . "[]'],this.form.elements['" . $keys[1] . "[]'], ".$sort.")\">\n";
  
foreach($js_var_array[$keys[0]] as $key => $value) {
  $js .= "<option value=\"" . $key . "\">" . $value . "</option>\n";
}

$js .= "</select></td>\n

<td align=\"center\" valign=\"middle\">\n
<input type=\"button\" onclick=\"move(this.form.elements['" . $keys[0] . "[]'],this.form.elements['" . $keys[1] . "[]'], ".$sort.")\" value=\"" . $_SESSION['translate']->it("Add") . " &gt;&gt;\" />\n
<br /><br />\n
<input type=\"button\" onclick=\"move(this.form.elements['" . $keys[1] . "[]'],this.form.elements['" . $keys[0] . "[]'], ".$sort.")\" value=\"&lt;&lt; " . $_SESSION['translate']->it("Remove") . "\" />\n
</td>\n";

$js .= "<td><select name=\"" . $keys[1] . "[]\" multiple=\"multiple\" size=\"10\" ondblclick=\"move(this.form.elements['" . $keys[1] . "[]'],this.form.elements['" . $keys[0] . "[]'], ".$sort.")\">\n";

foreach($js_var_array[$keys[1]] as $key => $value) {
  $js .= "<option value=\"" . $key . "\">" . $value . "</option>\n";
}

$js .= "</select></td></tr></table>\n";

} else {

$keys = array_keys($js_var_array);

$js = "<select name=\"" . $keys[1] . "[]\" multiple=\"multiple\" size=\"10\">\n";

foreach($js_var_array[$keys[0]] as $key => $value) {
  if(in_array($key, $js_var_array[$keys[1]])) {
    $js .= "<option value=\"" . $key . "\" selected=\"selected\">" . $value . "</option>\n";
  } else {
    $js .= "<option value=\"" . $key . "\">" . $value . "</option>\n";
  }
}

$js .= "</select>\n";

}

?>