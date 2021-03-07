<?php
// bbCode control by
// subBlue design
// www.subBlue.com

$this->js_func[] = "

// Startup variables
var imageTag = false;
var theSelection = false;

// Check for Browser & Platform for PC & IE specific bits
// More details from: http://www.mozilla.org/docs/web-developer/sniffer/browser_type.html
var clientPC = navigator.userAgent.toLowerCase(); // Get client info
var clientVer = parseInt(navigator.appVersion); // Get browser version

var is_ie = ((clientPC.indexOf(\"msie\") != -1) && (clientPC.indexOf(\"opera\") == -1));
var is_nav  = ((clientPC.indexOf('mozilla')!=-1) && (clientPC.indexOf('spoofer')==-1)
                && (clientPC.indexOf('compatible') == -1) && (clientPC.indexOf('opera')==-1)
                && (clientPC.indexOf('webtv')==-1) && (clientPC.indexOf('hotjava')==-1));

var is_win   = ((clientPC.indexOf(\"win\")!=-1) || (clientPC.indexOf(\"16bit\") != -1));
var is_mac    = (clientPC.indexOf(\"mac\")!=-1);


// Helpline messages
b_help = \"Bold text: [b]text[/b]  (alt+b)\";
i_help = \"Italic text: [i]text[/i]  (alt+i)\";
u_help = \"Underline text: [u]text[/u]  (alt+u)\";
q_help = \"Quote text: [quote]text[/quote]  (alt+q)\";
c_help = \"Code display: [code]code[/code]  (alt+c)\";
l_help = \"List: [list]text[/list] (alt+l)\";
o_help = \"Ordered list: [list=]text[/list]  (alt+o)\";
p_help = \"Insert image: [img]http://image_url[/img]  (alt+p)\";
w_help = \"Insert URL: [url]http://url[/url] or [url=http://url]URL text[/url]  (alt+w)\";
a_help = \"Close all open bbCode tags\";
s_help = \"Font color: [color=red]text[/color]  Tip: you can also use color=#FF0000\";
f_help = \"Font size: [size=x-small]small text[/size]\";


// Define the bbCode tags
bbcode = new Array();
bbtags = new Array('<b>','</b>','<i>','</i>','<u>','</u>','<blockquote>','</blockquote>','<ul>','</ul>','<ol>','</ol>','<img src=\"','\" />','<a href=\"','\">Click Text here</a>');
imageTag = false;

function bbfontstyle(bbopen, bbclose) {
	if ((clientVer >= 4) && is_ie && is_win) {
		theSelection = document.selection.createRange().text;
		if (!theSelection) {
			document.post.message.value += bbopen + bbclose;
			document.post.message.focus();
			return;
		}
		document.selection.createRange().text = bbopen + theSelection + bbclose;
		document.post.message.focus();
		return;
	} else {
		document.post.message.value += bbopen + bbclose;
		document.post.message.focus();
		return;
	}
	storeCaret(document.post.message);
}


function bbstyle(bbnumber) {

	donotinsert = false;
	theSelection = false;
	bblast = 0;

	if (bbnumber == -1) { // Close all open tags & default button names
		while (bbcode[0]) {
			butnumber = arraypop(bbcode) - 1;
			document.post.message.value += bbtags[butnumber + 1];
			buttext = eval('document.post.addbbcode' + butnumber + '.value');
			eval('document.post.addbbcode' + butnumber + '.value =\"' + buttext.substr(0,(buttext.length - 1)) + '\"');
		}
		imageTag = false; // All tags are closed including image tags :D
		document.post.message.focus();
		return;
	}

	if ((clientVer >= 4) && is_ie && is_win)
		theSelection = document.selection.createRange().text; // Get text selection

	if (theSelection) {
		// Add tags around selection
		document.selection.createRange().text = bbtags[bbnumber] + theSelection + bbtags[bbnumber+1];
		document.post.message.focus();
		theSelection = '';
		return;
	}

	// Find last occurance of an open tag the same as the one just clicked
	for (i = 0; i < bbcode.length; i++) {
		if (bbcode[i] == bbnumber+1) {
			bblast = i;
			donotinsert = true;
		}
	}

	if (donotinsert) {		// Close all open tags up to the one just clicked & default button names
		while (bbcode[bblast]) {
				butnumber = arraypop(bbcode) - 1;
				document.post.message.value += bbtags[butnumber + 1];
				buttext = eval('document.post.addbbcode' + butnumber + '.value');
				eval('document.post.addbbcode' + butnumber + '.value =\"' + buttext.substr(0,(buttext.length - 1)) + '\"');
				imageTag = false;
			}
			document.post.message.focus();
			return;
	} else { // Open tags

		if (imageTag && (bbnumber != 14)) {		// Close image tag before adding another
			document.post.message.value += bbtags[15];1

			lastValue = arraypop(bbcode) - 1;	// Remove the close image tag from the list
			document.post.addbbcode14.value = \"Img\";	// Return button back to normal state
			imageTag = false;
		}

		// Open tag
		document.post.message.value += bbtags[bbnumber];
		if ((bbnumber == 14) && (imageTag == false)) imageTag = 1; // Check to stop additional tags after an unclosed image tag
		arraypush(bbcode,bbnumber+1);
		eval('document.post.addbbcode'+bbnumber+'.value += \"*\"');
		document.post.message.focus();
		return;
	}
	storeCaret(document.post.message);
}

// Insert at Claret position. Code from
// http://www.faqts.com/knowledge_base/view.phtml/aid/1052/fid/130
function storeCaret(textEl) {
	if (textEl.createTextRange) textEl.caretPos = document.selection.createRange().duplicate();
}
";



$js = "<form action=\"posting.php\" method=\"post\" name=\"post\" onsubmit=\"return checkForm(this)\">
<table><tr><td>
<input type=\"button\" class=\"button\" accesskey=\"b\" name=\"addbbcode0\" value=\" B \" style=\"font-weight:bold; width: 30px\" onClick=\"bbstyle(0)\" onMouseOver=\"helpline('b')\" />
<input type=\"button\" class=\"button\" accesskey=\"i\" name=\"addbbcode2\" value=\" i \" style=\"font-style:italic; width: 30px\" onClick=\"bbstyle(2)\" onMouseOver=\"helpline('i')\" />
<input type=\"button\" class=\"button\" accesskey=\"u\" name=\"addbbcode4\" value=\" u \" style=\"text-decoration: underline; width: 30px\" onClick=\"bbstyle(4)\" onMouseOver=\"helpline('u')\" />
<input type=\"button\" class=\"button\" accesskey=\"q\" name=\"addbbcode6\" value=\"Quote\" style=\"width: 50px\" onClick=\"bbstyle(6)\" onMouseOver=\"helpline('q')\" />
<input type=\"button\" class=\"button\" accesskey=\"c\" name=\"addbbcode8\" value=\"Code\" style=\"width: 40px\" onClick=\"bbstyle(8)\" onMouseOver=\"helpline('c')\" />
<input type=\"button\" class=\"button\" accesskey=\"l\" name=\"addbbcode10\" value=\"List\" style=\"width: 40px\" onClick=\"bbstyle(10)\" onMouseOver=\"helpline('l')\" />
<input type=\"button\" class=\"button\" accesskey=\"o\" name=\"addbbcode12\" value=\"List=\" style=\"width: 40px\" onClick=\"bbstyle(12)\" onMouseOver=\"helpline('o')\" />
<input type=\"button\" class=\"button\" accesskey=\"p\" name=\"addbbcode14\" value=\"Img\" style=\"width: 40px\"  onClick=\"bbstyle(14)\" onMouseOver=\"helpline('p')\" />
<input type=\"button\" class=\"button\" accesskey=\"w\" name=\"addbbcode16\" value=\"URL\" style=\"text-decoration: underline; width: 40px\" onClick=\"bbstyle(16)\" onMouseOver=\"helpline('w')\" /><br />
</td></tr>
		<tr><td>Font colour:
					<select name=\"addbbcode18\" onChange=\"bbfontstyle('[color=' + this.form.addbbcode18.options[this.form.addbbcode18.selectedIndex].value + ']', '[/color]')\" onMouseOver=\"helpline('s')\">
					  <option style=\"color:black; background-color: #FAFAFA\" value=\"#444444\" class=\"genmed\">Default</option>
					  <option style=\"color:darkred; background-color: #FAFAFA\" value=\"darkred\" class=\"genmed\">Dark Red</option>
					  <option style=\"color:red; background-color: #FAFAFA\" value=\"red\" class=\"genmed\">Red</option>
					  <option style=\"color:orange; background-color: #FAFAFA\" value=\"orange\" class=\"genmed\">Orange</option>
					  <option style=\"color:brown; background-color: #FAFAFA\" value=\"brown\" class=\"genmed\">Brown</option>
					  <option style=\"color:yellow; background-color: #FAFAFA\" value=\"yellow\" class=\"genmed\">Yellow</option>
					  <option style=\"color:green; background-color: #FAFAFA\" value=\"green\" class=\"genmed\">Green</option>
					  <option style=\"color:olive; background-color: #FAFAFA\" value=\"olive\" class=\"genmed\">Olive</option>
					  <option style=\"color:cyan; background-color: #FAFAFA\" value=\"cyan\" class=\"genmed\">Cyan</option>
					  <option style=\"color:blue; background-color: #FAFAFA\" value=\"blue\" class=\"genmed\">Blue</option>
					  <option style=\"color:darkblue; background-color: #FAFAFA\" value=\"darkblue\" class=\"genmed\">Dark Blue</option>
					  <option style=\"color:indigo; background-color: #FAFAFA\" value=\"indigo\" class=\"genmed\">Indigo</option>
					  <option style=\"color:violet; background-color: #FAFAFA\" value=\"violet\" class=\"genmed\">Violet</option>
					  <option style=\"color:white; background-color: #FAFAFA\" value=\"white\" class=\"genmed\">White</option>
					  <option style=\"color:black; background-color: #FAFAFA\" value=\"black\" class=\"genmed\">Black</option>
					</select>
 &nbsp;Font size:<select name=\"addbbcode20\" onChange=\"bbfontstyle('[size=' + this.form.addbbcode20.options[this.form.addbbcode20.selectedIndex].value + ']', '[/size]')\" onMouseOver=\"helpline('f')\">
					  <option value=\"7\" class=\"genmed\">Tiny</option>
					  <option value=\"9\" class=\"genmed\">Small</option>
					  <option value=\"12\" selected class=\"genmed\">Normal</option>
					  <option value=\"18\" class=\"genmed\">Large</option>
					  <option  value=\"24\" class=\"genmed\">Huge</option>
					</select>
				  <a href=\"javascript:bbstyle(-1)\" class=\"genmed\" onMouseOver=\"helpline('a')\">Close Tags</a>
			  <input type=\"text\" name=\"helpbox\" size=\"45\" maxlength=\"100\" style=\"width:450px; font-size:10px\" class=\"helpline\" value=\"Tip: Styles can be applied quickly to selected text\" />
</td>
</tr>
<tr>
<td>
			  <textarea name=\"message\" rows=\"15\" cols=\"35\" wrap=\"virtual\" style=\"width:450px\" tabindex=\"3\" class=\"post\" onselect=\"storeCaret(this);\" onclick=\"storeCaret(this);\" onkeyup=\"storeCaret(this);\"></textarea>
</td>
<tr>
</table>
</form>";