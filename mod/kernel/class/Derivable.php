<?php
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/flow.php');

class Derivable extends PHPWS_Flow {
  function Derivable() {
  }

  function __construct($order = NULL) {
	  parent::__construct();

	  if ($order && is_numeric($order)) $this->load_flow($order);
	  elseif ($order && is_object($order)) $this->derive($order);
  }

  function derive($flow) {
	  $this->flowTable = &$flow->flowTable;
	  $this->processors = &$flow->processors;
	  $this->goods = &$flow->goods;
	  $this->name = &$flow->name;
	  $this->id = &$flow->id;
	  $this->goodsid = &$flow->goodsid;
	  $this->processorsid = &$flow->processorsid;
	  $this->alltypes = &$flow->alltypes;
	  $this->status = &$flow->status;
	  $this->org = &$flow->org;
	  $this->creator = &$flow->creator;
	  $this->saved = &$flow->saved;
  }

  function deliveredTotal($p) {
	  $g = $this->processors[$p->dest()];
	  $qs = $g->correspond($p);
	  $total = 0;
	  if ($qs) foreach ($qs as $q) {
		  $q = $this->goods[$q];
		  if ($q->dest() > 0 && in_array($this->processors[$q->dest()]->processType(), array(RETURN_NODE, WASTE_NODE)))
			  continue;
		  $total += $q->executedSum();
	  }
	  return $total;
  }
  function returnTotal($p) {
	  $g = $this->processors[$p->dest()];
	  $qs = $g->correspond($p);
	  $total = 0;
	  if ($qs) foreach ($qs as $q) {
		  $q = $this->goods[$q];
		  if ($q->dest() <= 0 || $this->processors[$q->dest()]->processType() != RETURN_NODE)
			  continue;
		  $total += $q->executedSum();
	  }
	  return $total;
  }
  function wasteTotal($p) {
	  $g = $this->processors[$p->dest()];
	  $qs = $g->correspond($p);
	  $total = 0;
	  if ($qs) foreach ($qs as $q) {
		  $q = $this->goods[$q];
		  if ($q->dest() <= 0 || $this->processors[$q->dest()]->processType() != WASTE_NODE)
			  continue;
		  $total += $q->quantity();
	  }
	  return $total;
  }

  function getdownstream($g, $node = SALE_NODE) {
	  $g2 = $g;
	  while ($g2 && $g2->processType() != $node) {
		  $q = NULL;
		  foreach ($g2->output() as $i) {
			  $p = $this->goods[$i];
			  if ($p->dest() > 0) {
				  $q = $this->processors[$p->dest()];
				  break;
			  }
		  }
		  $g2 = $q;
	  }
	  return $g2;
  }

  function getsalefrom($g) {
	  return $this->getdownstream($g, SALE_NODE);
  }
  function getupstream($g, $node = SALE_NODE) {
	  $g2 = $g;
	  while ($g2 && $g2->processType() != $node) {
		  $q = NULL;
		  foreach ($g2->input() as $i) {
			  $p = $this->goods[$i];
			  if ($p->src() > 0) {
				  $q = $this->processors[$p->src()];
				  break;
			  }
		  }
		  $g2 = $q;
	  }
	  return $g2;
  }
  function getpurchasefrom($g) {
	  return $this->getupstream($g, PURCHASE_NODE);
  }
}

?>