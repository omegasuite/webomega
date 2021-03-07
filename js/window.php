<?php
/**
 * You will need to look up a reference on Javascript window control
 * for what each variable does. To access these functions, you need to
 * send an associative array to the $core->js_insert() function.
 * 'type' controls what is clickable (a link or a button)
 * 'url' is the url of what the window will open
 * 'label is what is echoed to the link or button
 *
 * Example:
 * To create a link that opens another window
 *
 * $window_array= array(
 *	     "type"=>"link",
 *	     "url"=>"http://phpwebsite.appstate.edu",
 *	     "label"=>"Click this!",
 *	     "scrollbars"=>"yes",
 *	     "width"=>"1000",
 *	     "height"=>"400",
 *	     "toolbar"=>"yes"
 *	     );
 *
 *
 * $CNT_widget["content"] .= $core->js_insert("window", NULL, NULL, NULL, $window_array);
 * End Example
 * 
 * See the variables below for the settings. They MUST be spelled the same in
 * the index of the array.
 */

isset($width)        ? $features[] = "width=$width"                      : NULL;
isset($height)       ? $features[] = "height=$height"                    : NULL;
isset($toolbar)      ? $features[] = "toolbar=$toolbar"                  : NULL;
isset($directories)  ? $features[] = "directories=$directories"          : NULL;
isset($location)     ? $features[] = "location=$location"                : NULL;
isset($menubar)      ? $features[] = "menubar=$menubar"                  : NULL;
isset($scrollbars)   ? $features[] = "scrollbars=$scrollbars"            : NULL;
isset($status)       ? $features[] = "status=$status"                    : NULL;
isset($resizable)    ? $features[] = "resizable=$resizable"              : NULL;

//New browser commands
isset($titlebar)     ? $features[] = "titlebar=$titlebar"                : NULL;
isset($h_margin)     ? $features[] = "screenX=$h_margin,left=$h_margin"  : NULL;
isset($v_margin)     ? $features[] = "screenY=$v_margin,top=$v_margin"   : NULL;
isset($dependent)    ? $features[] = "dependent=$dependent"              : NULL;

if (isset($features))
  $full_features = implode(",", $features);

$run_js= "window.open('$url&amp;lay_quiet=1','_BLANK','$full_features');";

if ($type == "link"){
    $js = "<a href=\"javascript:void(0)\" onClick=\"$run_js\">$label</a>";
} elseif ($type == "button") {
    $js = "<input type=\"button\" value=\"$label\" onClick=\"$run_js\" />";
}
     
?>