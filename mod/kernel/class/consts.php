<?php

// capabilities
define('ORG_MANUFACTURER', '制造');
define('ORG_TRANSPORTER', '运输');
define('ORG_PROCESSOR', '生产');
define('ORG_RETAILER', '销售');
define('ORG_WHOLESALER', '批发');
define('ORG_PACKER', '包装');
define('ORG_PURCHASE', '采购');
define('ORG_STOCK', '仓储');
define('ORG_FINANCER', '金融');

define('ORG_MANUFACTURER_BIT', 1);
define('ORG_TRANSPORTER_BIT', 2);
define('ORG_PROCESSOR_BIT', 4);
define('ORG_RETAILER_BIT', 8);
define('ORG_WHOLESALER_BIT', 16);
define('ORG_PACKER_BIT', 64);
define('ORG_PURCHASE_BIT', 131072);
define('ORG_STOCK_BIT', 512);
define('ORG_FINANCER_BIT', 256);

// node types
define('MANUFACTURE_NODE', 1);		// 1
define('TRANSPORT_NODE', 2);		// 2
define('PROCESSOR_NODE', 4);		// 4
define('SALE_NODE', 8);				// 8
define('WHOLESALE_NODE', 16);		// 16
define('GOODS_NODE', 32);			// 32
define('PACK_NODE', 64);			// 64
define('SUMUP_NODE', 128);			// 128
define('DIVIDE_NODE', 256);			// 256
define('STOCK_NODE', 512);			// 512
define('ROUTE_NODE', 1024);			// 1024
define('TRUCK_NODE', 2048);			// 2048
define('STOP_NODE', 4096);			// 4096
define('WASTE_NODE', 8192);			// 8192
define('COMSUME_NODE', 16384);		// 16384
define('WHAREHOUSE_NODE', 32768);	// 32768
define('WHAREHOUSE_XFER_NODE', 65536);	// 65536
define('PURCHASE_NODE', 131072);	// 131072
define('RETURN_NODE', 262144);		// 262144
define('AUGSTOCK_NODE', 523288);	// 523288
define('AGGREGATE_NODE', 1046576);	// 1046576

$GLOBALS['_NODE_NAMES'] = array(
	MANUFACTURE_NODE=>'制造',
	TRANSPORT_NODE=>'运输',
	PROCESSOR_NODE=>'生产',
	SALE_NODE=>'销售',
	WHOLESALE_NODE=>'批发',
	PACK_NODE=>'包装',
	PURCHASE_NODE=>'采购',
	RETURN_NODE=>'退货',
	SUMUP_NODE=>'汇总',
	DIVIDE_NODE=>'分拆',
	GOODS_NODE=>'货物',
	STOCK_NODE=>'仓储',
	ROUTE_NODE=>'班线',
	TRUCK_NODE=>'运输工具',
	STOP_NODE=>'站点',
	WASTE_NODE=>'损耗',
	COMSUME_NODE=>'消费',
	WHAREHOUSE_NODE=>'仓库',
	WHAREHOUSE_XFER_NODE=>'转仓',
	AUGSTOCK_NODE=>'增仓',
	AGGREGATE_NODE=>'并分'
);

$GLOBALS['_STATUS_COLOR_CODE'] = array(
	1=>"<span style='color:#000;'>",
	2=>"<span style='color:blue;'>",
	3=>"<span style='color:blue;'>",
	4=>"<span style='color:blue;'>",
	5=>"<span style='color:red;'>",
	6=>"<span style='color:red;'>",
	7=>"<span style='color:red;'>",
	8=>"<span style='color:green;'>",
	9=>"<span style='color:green;'>",
	10=>"<span style='color:green;'>",
	11=>"<span style='color:green;'>",
	12=>"<span style='color:green;'>",
	13=>"<span style='color:green;'>",
	14=>"<span style='color:#999;'>",
	15=>"<span style='color:#666;'>"
);

// flow status
// Bits 0-3 for actual work
define("FLOW_PATTERN", 0);			// 模板
define("FLOW_PLAN", 1);				// 计划
define("FLOW_FIRM", 2);				// 确定，提交审核
define("FLOW_CHECKED", 3);			// 审核通过，提交审批
define("FLOW_EXECUTING", 4);		// 执行中	(any numer between FLOW_EXECUTING and FLOW_EXECUTED means something has been done, but order has not completed)
define("FLOW_EXECUTED", 8);			// 执行完毕		（货已全部发/收）
define("FLOW_CANCELLED", 0xE);		// 取消
define("FLOW_COMPLETED", 0xF);		// 完成			（货已验收）
// Bits 4-7 for finance
define("FLOW_PAID", 0x10);			// 款已付(部分或全部)
define("FLOW_PAID_ALL", 0x20);		// 款已全付
define("FLOW_INVOICED", 0x40);		// 对账单已开(收到)
define("FLOW_FINALIZED", 0x80);		// 账已结（对账正确）

define("FLOW_STATUS_TASKMASK", 0xF);
define("FLOW_STATUS_MONEYMASK", 0xF0);

$GLOBALS['_STATUS_WORD'] = array(
	FLOW_PATTERN=>'模板',
	FLOW_PLAN=>'计划',
	FLOW_FIRM=>'确定',
	FLOW_CHECKED=>'审核通过',
	FLOW_EXECUTING=>'执行中',
	FLOW_EXECUTED=>'执行完毕',
	FLOW_CANCELLED=>'取消',
	FLOW_COMPLETED=>'完成',
	FLOW_PAID=>'款已结',
	FLOW_FINALIZED=>'账已结'
);

// Warehouse type flags
define("WAREHOUSE_CATEGORY", 1);
define("WAREHOUSE_FROZEN", 2);
define("WAREHOUSE_HEAP", 4);

// Notification flags
define("NOTICE_WORKCHG", 1);
define("NOTICE_RELATEDWORKCHG", 2);
define("NOTICE_NEWWORK", 4);
define("NOTICE_NEWRELATEDWORK", 8);
define("NOTICE_WORKDONE", 16);
define("NOTICE_APPROVALMSG", 32);

?>