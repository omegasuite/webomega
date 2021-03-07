<?php

// These tags are defaulty allowed in the parse function.
$allowed_tags = "<b><a><i><u><ul><ol><li><table><tr><td><dd><dt><dl><p><br><div><span><blockquote><th><tt><img><pre><h1><h2><h3><h4>";

// Set to TRUE to strip_profanity when the parse function is used
$strip_profanity = TRUE;

// Set to TRUE to convert newline characters to html breaks   	 
$add_breaks = TRUE; 	 

/**
 * This is the array of bad words you want to removed from your
 * text. The array will used in a regular expression, so format
 * the words appropriately.
 * Example: To remove "cock" and not "peacock" we use:
 * "[\s-\.]+cock" which means 'only replace if there is a whitespace
 * character before.
 *
 * The value of the array is what you want to replace the word with.
 * We supply $censor as an example but you can format it however you wish.
 * Notice in the case of the 'N-word' we cut off all further information with
 * the '.*' suffix to the search.
 * Learn how to use regular expressions if you are confused.
 */

$censor = "*bleep*";

$bad_words = array(
		   "[\s-\.]+cock"=>" " .$censor,
		   "mother\s?fucker"=>$censor,
		   "fuck"=>$censor,
		   "shit"=>$censor,
		   "asshole"=>$censor,
		   "cunt"=>$censor,
		   "nigger.*"=>"... I am a racist idiot! ",
		   "faggot.*"=>"... I have issues with my sexuality! "
		   );
?>