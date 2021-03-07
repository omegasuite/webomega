<?php
require_once(PHPWS_SOURCE_DIR . 'mod/basic.php');
require_once(PHPWS_SOURCE_DIR . 'mod/accessories/class/formoptions.php');

class goodsconv extends Data2FormConverter {
	protected $objmap = array('CODE'=>"code", 'NAME'=>"name", 'ATTRIB'=>"attribs", 'UNIT'=>"unit", 'QUANTITY'=>"quantity", 'PRICE'=>"price", 'AMOUNT'=>"totalcost", 'DISCOUNT'=>"discount", 'DISCOUNTAMOUNT'=>"totalcost", 'TAXRATE'=>"taxrate", 'TAX'=>"tax", 'COMMENT'=>"comments");

	function goodsconv($obj, $mode = 0) {
	}
	function __construct($obj, $mode = 0) {
	  parent::__construct($obj, $mode);
	}

	protected function code() { return $this->obj->ExtraVal('code'); }
	protected function discount() { return $this->obj->ExtraVal('discount'); }
	protected function taxrate() { return $this->obj->ExtraVal('taxrate'); }
	protected function tax() { return $this->value('AMOUNT') * $this->taxrate(); }
	protected function comments() {return $this->obj->ExtraVal('comments'); }

	protected function value($name) { return parent::value($this->objmap[$name]?$this->objmap[$name] : $name); }
}

?>