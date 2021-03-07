<?php

class EZelement{

  /**
   * Id of an element
   */
  var $id;

  /**
   * Name of an element
   */
  var $name;

  /**
   * Value of an element
   */
  var $value;

  /**
   * Type of form element
   */
  var $type;

  /**
   * Substitute template tag name
   */
  var $tag;

  /**
   * Size of the element
   *
   * Used for select and text elements
   */
  var $size;

  /**
   * Optgroups of an element
   *
   * Used for select elements
   */
  var $optgroups;

  /**
   * maximum size of text field
   */
  var $maxsize;

  /**
   * Value to match for radio, checkbox, select elements
   */
  var $match;

  /**
   * Rows of a textarea
   */
  var $rows;

  /**
   * Columns of a textarea
   */
  var $cols;


  /**
   * Extra information padded to an element
   */
  var $extra;

  /**
   * Element Error
   */
  var $error;

  /**
   * Order of tab elements
   */
  var $tab;

  /**
   * if 1, password fields will fill in size
   */
  var $showPass;


  /**
   * If TRUE, match to option instead of value
   */
  var $optionMatch;

  function EZelement(){
    $this->id          = NULL;
    $this->name        = NULL;
    $this->value       = NULL;
    $this->type        = NULL;
    $this->tag         = NULL;
    $this->size        = NULL;
    $this->maxsize     = NULL;
    $this->match       = NULL;
    $this->rows        = NULL;
    $this->cols        = NULL;
    $this->extra       = NULL;
    $this->error       = NULL;
    $this->showPass    = FALSE;
    $this->optionMatch = FALSE;
    $this->optgroups   = FALSE;
  }

  function _setId($id) {
      $this->id = $id;
  }

  function _setExtra($extra){
    $this->extra = $extra;
  }

  function _setOptgroups($optgroups) {
    $this->optgroups = $optgroups;
  }

  function _setType($type){
    $formTypes = array("radio",
		       "hidden",
		       "file",
		       "text",
		       "password",
		       "textarea",
		       "checkbox",
		       "dropbox",
		       "select",
		       "multiple",
		       "submit",
		       "button",
               "reset"
		       );

    if (in_array($type, $formTypes)){
      if ($type == "dropbox")
	$this->type = "select";
      else
	$this->type = $type;
      return TRUE;
    }
    else {
      $this->error = "Unrecognized form type <b>$type</b> in element <b>".$this->name."</b>.<br />\n";
      return FALSE;
    }
  }

  function _setTab($order){
    if (!is_numeric($order)){
      $this->error = "Tab order <b>'$order'</b> in element <b>".$this->name."</b> is not a number.<br />\n";
      return FALSE;
    }
    
    $this->tab = $order;
  }

  function getTab(){
    return $this->tab;
  }


  function _setTag($tag){
    if (!PHPWS_Text::isValidInput($tag)){
      $this->error = "Tag template name <b>$tag</b> for the element <b>".$this->name."</b> must consist of letters, numbers, and underlines only.<br />\n";
      return FALSE;
    }

    $this->tag = $tag;
    return TRUE;
  }

  function _setMatch($match){
    $this->match = $match;
  }

  function _setRows($rows){
    if (!is_numeric($rows) || $rows < 1 || $rows > 100){
      $this->error = "The number sent to setRows is invalid.<br />\n";
      return FALSE;
    }

    $this->rows = $rows;
    return TRUE;
  }

  function _setWidth($width){
    if (!is_numeric($width) || $width < 1 || $width > 100){
      $this->error = "The number sent to setWidth is invalid.<br />\n";
      return FALSE;
    }

    $this->width = $width;
    return TRUE;
  }

  function _setHeight($height){
    if (!is_numeric($height) || $height < 1 || $height > 100){
      $this->error = "The number sent to setHeight is invalid.<br />\n";
      return FALSE;
    }

    $this->height = $height;
    return TRUE;
  }


  function _setCols($cols){
    if (!is_numeric($cols) || $cols < 1 || $cols > 100){
      $this->error = "The number sent to setCols is invalid.<br />\n";
      return FALSE;
    }

    $this->cols = $cols;
    return TRUE;
  }

  function getType(){
    return $this->type;
  }

  function _setValue($value){
    $this->value = $value;
  }

  function _setSize($size){
    if (is_numeric($size))
      $this->size = $size;
    else {
      $this->error = "Size sent to <b>".$this->name."</b> is not a number.";
      return FALSE;
    }
  }

  function _setMaxSize($maxsize){
    if (is_numeric($maxsize))
      $this->maxsize = $maxsize;
    else {
      $this->error = "Maxsize sent to <b>".$this->name."</b> is not a number.";
      return FALSE;
    }
  }


  function _getInput(){
    $id = $value = $misc = NULL;
    $size = $maxsize = $extra = $tab = $style = NULL;

    if(isset($this->id)) {
	$id = "id=\"" . $this->id . "\" ";
    }

    $name = "name=\"" . $this->name . "\" ";
    $type = "type=\"" . $this->type . "\" ";

    if (isset($this->tab))
      $tab = " tabindex=\"" . $this->tab . "\" ";

    switch ($this->type){
    case "text":
    case "password":
    case "file":
      if (isset($this->width))
	$style[] = "width : " . $this->width . "%";

      if (isset($this->height))
	$style[] = "height : " . $this->height . "%";
      
      isset($this->size) ? $size = $this->size : $size = DFLT_TEXT_SIZE;
      isset($this->maxsize) ? $maxsize = $this->maxsize : $maxsize = DFLT_MAX_SIZE;
      $size = "size=\"" . $size . "\" ";
      $maxsize = "maxlength=\"" . $maxsize . "\" ";
      break;

    case "select":
    case "multiple":
      isset($this->size) ? $size = $this->size : $size = DFLT_MAX_SELECT;
      $size = "size=\"" . $size . "\" ";
      break;

    case "textarea":
      if (isset($this->width)){
	$style[] = "width : " . $this->width . "%";
	$cols = NULL;
      }
      else {
	if (isset($this->cols))
	  $cols = "cols=\"" . $this->cols . "\" ";
	else
	  $cols = "cols=\"" . DFLT_COLS . "\" ";
      }
      
      if (isset($this->height))
	$style[] = "height : " . $this->height . "%";
      else {
	if (isset($this->rows))
	  $rows = "rows=\"" . $this->rows . "\" ";
	else
	  $rows = "rows=\"" . DFLT_ROWS . "\" ";
      }

      break;
    }

    if ($this->extra)
      $extra = $this->extra . " ";

    if (isset($this->value)){
        $this->value = PHPWS_Text::ampersand($this->value);
      switch ($this->type){
      case "hidden":       
      case "text":
      case "submit":
      case "button":
      case "checkbox":
      case "reset":
	$value = str_replace("\"", "&#x0022;", $this->value);
	$value = "value=\"" . $value . "\" ";
      break;

      case "password":
	if ($this->showPass){
	  $value = "value=\"" . preg_replace("/./", "*", $this->value) . "\" ";
	}
	break;

      case "select":
	$value = $this->value;
	break;
	
      case "textarea":
	$value = $this->value;

	if (ord(substr($value, 0, 1)) == 13)
	  $value = "\n" . $value;
	break;
      }
    }

    if ($this->type == "checkbox" && !empty($this->match) && $this->match == $this->value)
      $misc = "checked=\"checked\" ";

    if (isset($style))
      $style = "style=\"" . implode ("; ", $style) . "\" ";

    switch ($this->type){
    case "textarea":
      $formElement =  "<textarea " . $id . $name . $style . $rows . $cols . $extra . $tab . ">" . $value . "</textarea>\n";
    break;

    case "select":
      if (is_array($this->value))
	$formElement = $this->_getSelect();
      else {
      $this->error = "Missing select values for <b>".$this->name."</b>.";
      return FALSE;
      }
      break;
      
    case "multiple":
      if (is_array($this->value))
	 $formElement = $this->_getSelect(1);
      else {
	$this->error = "Missing multiple select values for <b>".$this->name."</b>.";
	return FALSE;
      }
      break;
      
    case "radio":
      if (!is_array($this->value) || count($this->value) < 2){
	$value = "value=\"".$this->value."\" ";
	$formElement =  "<input " . $type . $name . $value . $misc . $extra . $tab . "/>\n";
      } else {
	foreach ($this->value as $radioValue){
	  $misc = NULL;
	  $value = "value=\"$radioValue\" ";

	  if ($this->match == $radioValue)
	    $misc = "checked=\"checked\" ";

	  $formElement[] =  "<input " . $id . $type . $name . $value . $misc . $extra . $tab . "/>\n";
	}
      }
      break;
    
    default:
      $formElement =  "<input " . $id . $type . $name . $value . $misc . $style . $size . $maxsize . $extra . $tab . "/>\n";
    break;
    }

    return $formElement;
  }

  function _getSelect($multiple=0){
    $selectVals = $this->value;
    $id = $tab = $misc = NULL;

    if(isset($this->id)) {
	$id = "id=\"" . $this->id . "\" ";
    }
    
    if (isset($this->tab))
      $tab = " tabindex=\"" . $this->tab . "\" ";
    
    if ($multiple){
      $name = "name=\"" . $this->name . "[]\" ";
      $misc = "multiple=\"multiple\" ";
      if ($this->size)
	$misc .= " size=\"" . $this->size . "\" ";
    } else
      $name = "name=\"".$this->name."\"";
    
    $extra = '';
    if (isset($this->extra))
      $extra = " " . $this->extra . " ";
    else
      $extra = NULL;

    $data = "<select " . $id . $misc . $name . $tab . $extra . ">\n";
    foreach($selectVals as $value=>$option){
      $match = NULL;

      if ($multiple){
	if (!is_array($this->match))
	  $this->error = "The match for <b>".$this->name."</b> must be an array to match a multiple form.";
	elseif (!$this->optionMatch && in_array((string)$value, $this->match))
	  $match = " selected=\"selected\"";
	elseif ($this->optionMatch && in_array($option, $this->match))
	  $match = " selected=\"selected\"";
      }
      else {
	if (!$this->optionMatch && $this->match == $value)
	  $match = " selected=\"selected\"";
	elseif ($this->optionMatch && $this->match == $option)
	  $match = " selected=\"selected\"";
      }
      
      if(is_array($this->optgroups) && in_array($value, $this->optgroups, TRUE)) {
	$data .= "<optgroup label=\"{$option}\" />";
      } else {
	$data .= "<option value=\"" . $value . "\"" . $match . ">" . $option . "</option>\n";
      }
    }

    $data .= "</select>\n";

    return $data;
  }

}

?>