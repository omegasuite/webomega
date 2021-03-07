<?php
// Written by Matthew McNaney <matt@NOSPAM.tux.appstate.edu>

$langAdmin = "Language Administration";
$langAdmin_content = 
"Menu lets you control the language options of phpWebSite. The admin menu is broken into three sections.
<ol>
<li> Language Defaults - Controls the default processes of the Language Module</li>
<li> Language Editing - Allows manipulation of the language tables.</li>
<li> Dynamic Translations - Lets you translate submissions from different content modules </li>
</ol>";

$langActive = "Language Active";
$langActive_content = 
"If turned off, the language module will not translate the code in phpWebSite, nor will it mark it as untranslated.

If you don't plan on translating the text into a different language, you might get a speed boost by turning this 'off'.";

$dynActive = "Dynamic Active";
$dynActive_content = 
"If turned off, the language module will not translate dynamic content.

If you are not supporting dynamic language processing, you can turn this off.";

$mark = "Mark Untranslated";
$mark_content =
"When the language module comes across a phrase in the code it does not recognize, it will mark it with a question mark. This lets you know it needs to be translated.

If you do not want this question mark to appear on your site, turn this off. Keep this on when you want see parts of your website that need translation.
";

$auto = "Auto Update";
$auto_content = 
"The Language Module will automatically add phrases to the database that have not been translated. If you want to prevent this, turn this switch 'off'.

This can be useful if you are creating a module and are unsure what your final translations are going to be.";

$ignore = "Ignore Default Language";
$ignore_content =
"Turn this switch 'on' if you want the language module to ignore translating the default language.

For example, if you are running a multilingual site but your default language is English (which must of the code is written in) then turning this switch on may give a small speed increase.
"; 

$language = "Language Choice";
$language_content = "Pick the language you wish to edit:
<ul>
<li><b>Set Default Language</b> - Sets the default language for the site. When a user logs in, this will be the language they read the site in. The user can change their preference via their language user settings.</li>
<li><b>Edit Language</b> - Go here to edit phrases and translations. You can also delete translations from the dictionary.</li>
<li><b></b> - </li>
<li><b></b> - </li>
<li><b></b> - </li>
</ul>
";

$createTable = "Create Language Table";
$createTable_content =
"The Language Module contains two types of tables. The first type manages the translation of the static phpWebSite code. The other type of of language table handles dynamic content.

Choose a language table to create by go through the list and clicking on the 'Create Language Table' button. You will then be able to translate the text printed by phpWebSite and its modules.

If you want to create a dynamic table, pick a language in its list and click 'Create Dynamic Table'. If you don't see the language for the table you want to create, then you need to make a regular table first. Dynamic tables will also not appear on the list if they have already been created.";


$languageOptions = "Language Options";
$languageOptions_content =
"By choosing a language from the drop-down box, you can then perform various functions upon it.
<ul>
<li><b>Set Default Language</b> - This will be the language the site normally uses. It is seen by non-users and users who have not set their preferences.</li>
<li><b>Edit Language</b> - If you wish to add, edit, or delete a phrase in the dictionary, click on this button.</li>
<li><b>Search for Missing Translations</b> - Anytime a translate request is made on a phrase that is not in the dictionary, phpWebSite marks the phrase for later. When you click this button, you will see all of the phrases that need to be translated for the currently chosen language.</li>
<li><b>Remove Language Table</b> - If you wish to remove a language from your website, pick the language and click on this button.</li>
</ul>";

$importExport = "Import / Export";
$importExport_content =
"<b>Import</b>
You can import language files it into your web site. They take the form of:
module_title.language_abbrevation.lng

For example the User module's English language file is: <b>user.en.lng</b>

Choose the language and the module title of the file you want to import and click the 'Import Language File' button. You should now have the translations for that module.


<b>Export</b>
Once you have completed translation of a module for a specific language. you can export it. Make sure your web server has write priviledges in the lang/ directory of the module you are exporting. Choose the language and module title and then click the 'Export Language File' button. You will receive a message whether it is successful or not.

Notice that this file <b>WILL OVERWRITE</b> a file of the same name.
";

$editLanguage = "Update Language Table";
$editLanguage_content =
"This page will let you edit a language table.
<ul>
<li><b>Search for Missing Translations</b> - Performs the same function as the button on the main admin page. It lists those phrases that have not yet been translated.</li>
<li><b>Search for Phrase/Translation</b> - If there is a particular phrase you want to edit, enter a few words from the forementioned in the appropiate box. The Language Module will return a list of phrases that match that criteria.</li>
<li><b>Create New Entry</b> - You can add a new phrase and translation directly into the table here. This is not needed often as the Language Module creates a list of untranslated phrases automatically.</li>
</ul>
";


$searchResult = "Search Result Form";
$searchResult_content =
"From here you can edit the list of phrases and translations that were found from your search submission.

The <b>Update</b> checkbox should be clicked on all of the phrases that you translate. To turn all of the updates on, click the <b>Toggle All</b> button at the button.

The <b>Module</b> drop-down box controls where this phrase appears. If you see the word <b>core</b>, that means it is a commonly used translation, and is set to load for all modules.

Be careful if you edit the phrase. It <b>MUST</b> be identical to the phrase in the code. The phrase may not be edited in a search for missing translations.

More likely, you are here to edit a translation. Just type in your translation. If you see a question mark before a translation, that indicates that this phrase has not been translated yet. Make sure to remove it.

If you want to remove a phrase from the database entirely, click on the <b>Delete</b> button.

If there are several phrases, you can move among the different pages with the numbered links at the bottom of the page.

When you have checked all of your updates, click the <b>Update Checked</b> box to submit them.";


$dynForm = "Dynamic Sections Form";
$dynForm_content = "This page shows either the untranslated or updated elements.

Untranslated elements are rows of content recently submitted from a particular module. Once you update a element, it is taken off this list.

Updated elements consists of previously translated content recently updated in the original module.

To translated one of the dynamic elements, click on it radio button. Then click on the <b>Update</b> button at the top or bottom of the page.

If you want to look through different pages of elements, click on the numbered page links or the arrows.
<hr />
<ul>
<li><b>ID</b> - The identification number of that element in its table.</li>
<li><b>Pick</b> - The radio button that indicates which element to translate.</li>
<li><b>Column</b> - One or more of the column names that the element has stored information.</li>
<li><b>Phrase</b> - An example of the content contained in the element.</li>
<li><b>Updated</b> - When the original element was last updated.</li>
</ul>
";


$updateDyn = "Update Dynamic Translation";
$updateDyn_content =
"This form will allow you to translate both an untranslated or updated element.

All elements are contained in one row in a database table. It may, however, be broken into several columns. Edit each column of the original element into appropiate language.

Click <b>Update</b> when you are finished.
";


$dynAdmin = "Dynamic Translations";
$dynAdmin_content =
"This is a listing of all the dynamic elements that require translation.

It lists the untranslated and recently updated elements for each language and registered module on your web site.

Click on the number under <b>Untranslated</b> to see recently added elements that need translating into that language.

Click on the number under <b>Updated</b> to see recently update elements that need their translation updated as well.

Click on the <b>Refresh</b> link to recalculate the number of needed translations.
";

?>