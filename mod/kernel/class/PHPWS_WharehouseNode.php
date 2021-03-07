<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_ResourceNode.php');

class PHPWS_WharehouseNode extends PHPWS_ResourceNode {
  var $_wharehouse = 0;				// 0x1024	-- edit position flag

  function PHPWS_WharehouseNode() {
  }

  function __construct() {
	  $this->_processType = WHAREHOUSE_NODE;
	  parent::__construct();
  }

  function whatChanged($changes) {
	  $changes &= ~1024;
	  if ($changes) return parent::whatChanged($changes);
	  return array();
  }
  function loadData($f) {
	  parent::loadData($f);
	  $this->_wharehouse = $f['product'];
  }
  function copy($f) {
	  parent::copy($f);
	  $this->_wharehouse = $f->_wharehouse;
  }
  function weakcopy($f) {
	  parent::weakcopy($f);
	  $this->_wharehouse = $f->_wharehouse;
  }
  function saveData() {
	  $f = parent::saveData();
	  $f['product'] = $this->_wharehouse;
	  return $f;
  }
  function dataForm() {
	  $maychg = true;
	  if (in_array($this->_status, array(0, -1, -2, FLOW_EXECUTED))) $maychg = false;

	  if (!$this->_wharehouse) {
		  $c = $GLOBALS['core']->sqlSelect('mod_wharehouse', 'ownerOrg', $_SESSION['OBJ_user']->org);
		  $wharehouse = array(0=>'');
		  if ($c) foreach ($c as $cs) {
			  $wharehouse[$cs['id']] = $cs['name'];
		  }

		  if (sizeof($wharehouse) > 1)
			  $s .= "<tr><td>仓库：</td><td id=showgoods><div id=cats></div>" . PHPWS_Form::formSelect("wharehouse", $wharehouse, $this->_wharehouse, false, true) . "</td></tr>";
	  }
	  else {
		  $c = $GLOBALS['core']->sqlSelect('mod_wharehouse', 'id', $this->_wharehouse);
		  $s .= "<tr><td>仓库：</td><td>" . $c[0]['name'] . "</td></tr>";
	  }

	  $s .= parent::dataForm(0);

	  return $s;
  }
  function changeData() {
	  extract($_REQUEST);
	  extract($_POST);

	  $changes = 0;
	  if ($wharehouse && $this->_wharehouse != $wharehouse) {
		  $this->_wharehouse = $wharehouse;
		  $changes |= 1024;
		  if ($this->_status != -1 && $this->_status != 4) {
			  $this->_status = 4;
			  $changes |= 1;
		  }
	  }
	  if ($changes) $this->saved = false;

	  $changes |= parent::changeData();
	  return $changes;
  }

  function wharehouse() { return $this->_wharehouse; }

  function SetWharehouse($val) { if ($this->_wharehouse != $val) { $this->_wharehouse = $val; $this->saved = false; } return $val; }
}

?>