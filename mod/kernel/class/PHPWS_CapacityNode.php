<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_TaskNode.php');

class PHPWS_CapacityNode extends PHPWS_TaskNode {
  protected $_capacity = 0;		// 0x16384
  protected $_cycle = "日";		// 0x16384

  function PHPWS_CapacityNode() {
  }

  function __construct() {
	  parent::__construct();
  }

  function whatChanged($changes) {
	  $res = array();
	  if ($changes & 16384) {
		  $res = array(5);
		  $changes &= ~16384;
	  }
	  if ($changes) $res = array_merge($res, parent::whatChanged($changes));
	  return $res;
  }
  function loadData($f) {
	  parent::loadData($f);
	  $this->_capacity = $f['weight'];
	  $this->_cycle = $f['unit'];
  }
  function copy($f) {
	  parent::copy($f);
	  $this->_capacity = $f->_capacity;
	  $this->_cycle = $f->_cycle;
  }
  function weakcopy($f) {
	  parent::weakcopy($f);
	  $this->_capacity = $f->_capacity;
	  $this->_cycle = $f->_cycle;
  }
  function saveData() {
	  $f = parent::saveData();
	  $f['weight'] = $this->_capacity;
	  $f['unit'] = $this->_cycle;
	  return $f;
  }
  function dataForm($show = 0xFFFFFFFF, $edit = 0xFFFFFFFF) {
	  return parent::dataForm($show, $edit);
  }
  function changeData() {
	  extract($_REQUEST);
	  extract($_POST);

	  $changes = 0;
	  if ($capacity && $this->_capacity != $capacity) {
		  $this->_capacity = $capacity;
		  $changes |= 0x16384;
		  if (($this->_status & 0xF) < 4) $this->_status = ($this->_status & ~0xF) | 0x2;
	  }
	  if ($cycle && $this->_cycle != $cycle) {
		  $this->_cycle = $cycle;
		  $changes |= 0x16384;
		  if (($this->_status & 0xF) < 4) $this->_status = ($this->_status & ~0xF) | 0x2;
	  }
	  if ($changes) $this->saved = false;

	  $changes |= parent::changeData();
	  return $changes;
  }

  function capacity() { return $this->_capacity; }
  function cycle() { return $this->_cycle; }

  function SetCapacity($val) { if ($this->_capacity != $val) { $this->_capacity = $val; $this->saved = false; } return $val; }
  function SetCycle($val) { if ($this->_cycle != $val) {$this->_cycle = $val; $this->saved = false; } return $val; }
}

?>