<?php
require_once(PHPWS_SOURCE_DIR . 'mod/basic.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/attrib.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/cache.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/consts.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/flow.php');

  function getproperflow($order) {
	  $fl = new PHPWS_Flow();
	  $fl->load_flow($order);

	  $dv = deriveproperflow($fl);
	  return $dv;
  }

  function deriveproperflow($fl) {
	  switch ($g->processType()) {
		  case SALE_NODE:
			  require_once(PHPWS_SOURCE_DIR . 'mod/sale/class/SaleTask.php');
			  $f = new SaleTask;
			  $f->derive($fl);
			  return $f;
		  case PURCHASE_NODE:
			  require_once(PHPWS_SOURCE_DIR . 'mod/purchase/class/PurchaseTask.php');
			  $f = new PurchaseTask;
			  $f->derive($fl);
			  return $f;
		  case STOCK_NODE:
			  require_once(PHPWS_SOURCE_DIR . 'mod/stock/class/StockTask.php');
			  $f = new StockTask;
			  $f->derive($fl);
			  return $f;
		  case MANUFACTURE_NODE:
		  case PACK_NODE:
		  case PROCESSOR_NODE:
			  require_once(PHPWS_SOURCE_DIR . 'mod/production/class/production.php');
			  $f = new ProductionTask;
			  $f->derive($fl);
			  return $f;
/*
		  case TRANSPORT_NODE:
			  require_once(PHPWS_SOURCE_DIR . 'mod/transport/class/transport.php');
			  $f = new TransportTask;
			  $f->derive($fl, $g);
			  return $f;
*/
	  }
	  return $fl;
  }

?>