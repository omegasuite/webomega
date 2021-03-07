<?php

/* Settings for the javascript features within phpwebsite */

/* wysiwyg */
$wysiwyg_on = true;

/* spell checker */
if(extension_loaded('pspell')) {
    $ssc_on    = true;
    $ssc_lang  = null;
    $ssc_speed = PSPELL_FAST;
}

?>