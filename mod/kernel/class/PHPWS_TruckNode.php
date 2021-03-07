<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_ResourceNode.php');

class PHPWS_TruckNode extends PHPWS_ResourceNode {
  var $_vehicle = 0;			// 0x1024	-- edit position flag
  var $_driver = 0;			// 0x1024	-- edit position flag
  var $_weight = 0;			// 0x16384
  var $_volumn = 0;			// 0x16384
  var $_lat = 0, $_lng = 0;

  function PHPWS_TruckNode() {
  }

  function __construct() {
	  $this->_processType = TRUCK_NODE;
	  parent::__construct();
  }

  function whatChanged($changes) {
	  $res = array();
	  if ($changes & (1024 | 16384)) {
		  $res = array(5);
		  $changes &= ~(1024 | 16384);
	  }
	  if ($changes) $res = array_merge($res, parent::whatChanged($changes));
	  return $res;
  }
  function loadData($f) {
	  parent::loadData($f);
	  $this->_vehicle = $f['product'];
	  $this->_driver = $f['category'];
	  $this->_weight = $f['weight'];
	  $this->_volumn = $f['volumn'];
	  $e = unserialize($f['extra']);
	  $this->_lat = $e['lat'];
	  $this->_lng = $e['lng'];
  }
  function copy($f) {
	  parent::copy($f);
	  $this->_vehicle = $f->_vehicle;
	  $this->_driver = $f->_driver;
	  $this->_weight = $f->_weight;
	  $this->_volumn = $f->_volumn;
	  $this->_lat = $f->_lat;
	  $this->_lng = $f->_lng;
  }
  function weakcopy($f) {
	  parent::weakcopy($f);
	  $this->_vehicle = $f->_vehicle;
	  $this->_driver = $f->_driver;
	  $this->_weight = $f->_weight;
	  $this->_volumn = $f->_volumn;
	  $this->_lat = $f->_lat;
	  $this->_lng = $f->_lng;
  }
  function saveData() {
	  $f = parent::saveData();
	  $f['product'] = $this->_vehicle;
	  $f['category'] = $this->_driver;
	  $f['weight'] = $this->_weight;
	  $f['volumn'] = $this->_volumn;
	  if (!$f['extra']) $f['extra'] = array();
	  $f['extra']['lat'] = $this->_lat;
	  $f['extra']['lng'] = $this->_lng;
	  return $f;
  }
  function dataForm($show = 0xFFFFFFFF, $edit = 0xFFFFFFFF) {
	  $s = parent::dataForm(0);

	  $maychg = true;
	  if (in_array($this->_status, array(0, -1, -2, FLOW_EXECUTED))) $maychg = false;

	  $vs = $GLOBALS['core']->sqlSelect('mod_vehicle', 'dept', $_SESSION["OBJ_user"]->org);
	  $trucks = array('');
	  $drivers = array('');
	  foreach ($vs as $v) {
		  $trucks[$v['id']] = $v['name'];
		  $drivers = array_merge($drivers, unserialize($v['drivers']));
	  }

	  $s .= "<tr><td>运输工具：</td><td>" . PHPWS_Form::formSelect("vehicle", $trucks, $this->_vehicle, false, true) . "</td></tr>";
	  $s .= "<tr><td>驾驶员：</td><td>" . PHPWS_Form::formSelect("driver", $drivers, $this->_driver, false, true) . "</td></tr>";
	  $s .= "<tr><td>总载重量：</td><td>";
	  if ($maychg) $s .= PHPWS_Form::formTextField("weight", $this->_weight, 4);
	  else $s .= $this->_weight;
	  $s .= "公斤</td></tr>";
	  $s .= "<tr><td>总容量：</td><td>";
	  if ($maychg)  $s .= PHPWS_Form::formTextField("volumn", $this->_volumn, 4);
	  else $s .= $this->_volumn;
	  $s .= "升</td></tr>";
	  return $s;
  }
  function changeData() {
	  extract($_REQUEST);
	  extract($_POST);

	  $changes = 0;
	  if ($vehicle && $this->_vehicle != $vehicle) {
		  $this->_vehicle = $vehicle;
		  $changes |= 0x1024;
		  if (($this->_status & 0xF) < 4) $this->_status = ($this->_status & ~0xF) | 0x2;
	  }
	  if ($driver && $this->_driver != $driver) {
		  $this->_driver = $driver;
		  $changes |= 0x1024;
		  if (($this->_status & 0xF) < 4) $this->_status = ($this->_status & ~0xF) | 0x2;
	  }
	  if ($this->_driver && $this->_vehicle && $this->_status != -1) {
		  $this->_status = -1;
		  $changes |= 1;
	  }
	  if ($weight && $this->_weight != $weight) {
		  $this->_weight = $weight;
		  $changes |= 0x16384;
		  if (($this->_status & 0xF) < 4) $this->_status = ($this->_status & ~0xF) | 0x2;
	  }
	  if ($volumn && $this->_volumn != $volumn) {
		  $this->_volumn = $volumn;
		  $changes |= 0x16384;
		  if (($this->_status & 0xF) < 4) $this->_status = ($this->_status & ~0xF) | 0x2;
	  }
	  if ($changes) $this->saved = false;

	  $changes |= parent::changeData();
	  return $changes;
  }

  function vehicle() { return $this->_vehicle; }
  function driver() { return $this->_driver; }
  function weight() { return $this->_weight; }
  function volumn() { return $this->_volumn; }
  function lat() { return $this->_lat; }
  function lng() { return $this->_lng; }

  function SetVehicle($val) { if ($this->_vehicle != $val) { $this->_vehicle = $val; $this->saved = false; } return $val; }
  function SetDriver($val) { if ($this->_driver != $val) { $this->_driver = $val; $this->saved = false; } return $val; }
  function SetWeight($val) { if ($this->_weight != $val) { $this->_weight = $val; $this->saved = false; } return $val; }
  function SetVolumn($val) { if ($this->_volumn != $val) { $this->_volumn = $val; $this->saved = false; } return $val; }
  function SetLat($val) { if ($this->_lat != $val) { $this->_lat = $val; $this->saved = false; } return $val; }
  function SetLng($val) { if ($this->_lng != $val) { $this->_lng = $val; $this->saved = false; } return $val; }
}

?>