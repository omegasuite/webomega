<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_ResourceNode.php');

class PHPWS_RouteNode extends PHPWS_ResourceNode {
  var $_liner = 0;			// 0x1024	-- edit position flag
  var $_linegroup = 0;		// 2048

  function PHPWS_ResourceNode() {
  }

  function __construct() {
	  $this->_processType = ROUTE_NODE;
	  parent::__construct();
  }
  function whatChanged($changes) {
	  $res = array();
	  if ($changes & ~1024) $res = array_merge($res, parent::whatChanged($changes & ~1024));
	  if ($changes & 1024) {
		  if (!in_array(2, $res)) $res[] = 2;
		  if (!in_array(4, $res)) $res[] = 4;
	  }
	  return $res;
  }
  function loadData($f) {
	  parent::loadData($f);
	  $this->_liner = $f['product'];
	  $this->_linegroup = $f['category'];
  }
  function copy($f) {
	  parent::copy($f);
	  $this->_liner = $f->_liner;
	  $this->_linegroup = $f->_linegroup;
  }
  function weakcopy($f) {
	  parent::weakcopy($f);
	  $this->_liner = $f->_liner;
	  $this->_linegroup = $f->_linegroup;
  }
  function saveData() {
	  $f = parent::saveData();
	  $f['product'] = $this->_liner;
	  $f['category'] = $this->_linegroup;
	  return $f;
  }
  function dataForm($show = 0xFFFFFFFF, $edit = 0xFFFFFFFF) {
	  $s = parent::dataForm(0, 0);	//16);

	  $maychg = true;
	  if (in_array($this->_status, array(0, -1, 8))) $maychg = false;

	  $ps = $GLOBALS['core']->query("SELECT * FROM supply_mod_locations WHERE province='{$this->_province}'" . ($this->_city?" AND city='{$this->_city}'" : ''), false);
	  $places = array(0=>'');
	  while ($p = $ps->fetchRow()) $places[$p['id']] = $p['name'];

	  $cities = array(0=>'');
	  if ($this->_province) {
		  $ps = $GLOBALS['core']->query("SELECT DISTINCT city FROM supply_mod_locations WHERE province='{$this->_province}'", false);
		  while ($p = $ps->fetchRow()) $cities[] = $p['city'];
	  }

	  $s .= "<tr><td>途经地点：</td><td>" . PHPWS_Form::formSelect("province1", $GLOBALS['provinces'], $this->_province, true, false, "chgProvince(this, 'city1', 'place1');") . PHPWS_Form::formSelect("city1", $cities, $this->_city, true, false, "chgCity(this, 'province1', 'place1');") . PHPWS_Form::formSelect("place1", $places, $this->_place, false, true, 'chgPlace(this, 0);') . "</td></tr>";
	  $s .= "<tr><td>途经地点：</td><td>" . PHPWS_Form::formSelect("province2", $GLOBALS['provinces'], $this->_province, true, false, "chgProvince(this, 'city2', 'place2');") . PHPWS_Form::formSelect("city2", $cities, $this->_city, true, false, "chgCity(this, 'province2', 'place2');") . PHPWS_Form::formSelect("place2", $places, $this->_place, false, true, 'chgPlace(this, 1);') . "</td></tr>";
	  if ($this->_liner) {
		  $ln = $GLOBALS['core']->sqlSelect("mod_liner", "lineid", $this->_liner);
		  $ln = $ln[0]['name'] . "<br>" ;
	  }
	  $s .= "<tr><td>班线：</td><td>" . $ln;
	  if ($maychg) $s .= PHPWS_Form::formSelect("linegroup", array(' '), $this->_linegroup, false, true);
	  $s .= "</td></tr>";
	  $s .= "<tr><td>班次：</td><td>" . $ln;
	  if ($maychg) $s .= PHPWS_Form::formSelect("liner", array(' '), $this->_liner, false, true);
	  $s .= "</td></tr>";

	  return $s;
  }
  function changeData() {
	  extract($_REQUEST);
	  extract($_POST);

	  $changes = 0;
	  if ($liner && $this->_liner != $liner) {
		  $this->_liner = $liner;
		  $changes |= 1024;
		  if ($this->_status != -1 && $this->_status != 4) {
			  $this->_status = 4;
			  $changes |= 1;
		  }
	  }

	  if ($linegroup && $this->_linegroup != $linegroup) {
		  $this->_linegroup = $linegroup;
		  if ($this->_status != -1 && $this->_status != 4) {
			  $this->_status = 4;
			  $changes |= 1;
		  }
	  }
	  if ($changes) $this->saved = false;

	  $changes |= parent::changeData();
	  return $changes;
  }

  function liner() { return $this->_liner; }
  function linegroup() { return $this->_linegroup; }

  function SetLiner($val) { if ($this->_liner != $val) { $this->_liner = $val; } $this->saved = false; }
  function SetLinegroup($val) { if ($this->_linegroup != $val) { $this->_linegroup = $val; } $this->saved = false; }
}

?>