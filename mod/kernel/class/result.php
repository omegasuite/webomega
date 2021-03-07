<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');

class PHPWS_Result {
  var $result = array(), $effect = array();

  function PHPWS_Result($r = NULL) {
	  if ($r) {
		  $this->result = $r['result'];
		  $this->effect = $r['effect'];
	  }
  }

  function isempty() {
	  return sizeof($this->result) + sizeof($this->effect) == 0;
  }

  function mergeResult($r2) {
	  if (is_object($r2)) {
		  $this->result = array_merge($this->result, $r2->result);
		  $this->effect = array_merge($this->effect, $r2->effect);
	  }
	  else {
		  if ($r2['result']) $this->result = array_merge($this->result, $r2['result']);
		  if ($r2['effect']) $this->effect = array_merge($this->effect, $r2['effect']);
	  }
  }

  function append($r2) {
	  $this->result = array_merge($this->result, $r2->result);
	  $this->effect = array_merge($this->effect, $r2->effect);
  }

  function showchgresult() {
	  if ($this->result) {
		  $s .= "<tr><th colspan=8 align=center style='color:red'>此次修改导致以下问题</th></tr>";
		  $s .= "<tr><th>工作任务</th><th>受影响的供需节点</th><th></th><th>产品</th><th>数量</th><th>成本</th><th>时间</th><th>地点</th></tr>";
		  $rr = array();
		  foreach ($this->result as $r) {
			  $problem = '';
			  foreach ($r as $pid=>$tp)
				  if (in_array($pid, array('product', 'quantity', 'cost', 'time', 'place'))) $problem = $pid;
			  $GLOBALS['core']->sqlInsert(array('flow'=>$r['flowid'], 'node'=>$r['itemid'], 'org'=>$_SESSION['OBJ_user']->org, 'problem'=>$problem, 'currentval'=>$r[$problem][0], 'newval'=>$r[$problem][1]), 'mod_planadjusts');

			  if (!$rr[$r['flow'] . $r['item']]) $rr[$r['flow'] . $r['item']] = $r;
			  else {
				  if ($r['product']) $rr[$r['flow'] . $r['item']]['product'] = $r['product'];
				  if ($r['quantity']) $rr[$r['flow'] . $r['item']]['quantity'] = $r['quantity'];
				  if ($r['cost']) $rr[$r['flow'] . $r['item']]['cost'] = $r['cost'];
				  if ($r['time']) $rr[$r['flow'] . $r['item']]['time'] = $r['time'];
				  if ($r['place']) $rr[$r['flow'] . $r['item']]['place'] = $r['place'];
			  }
		  }
		  foreach ($rr as $r) {
			  $s .= "<tr><th rowspan=2>{$r['flow']}</th><th rowspan=2>{$r['item']}</th>";
			  $s .= "<td>原计划</td>";
			  if ($r['product']) $s .= "<td>{$r['product'][0]}</td>"; else $s .= "<td></td>";
			  if ($r['quantity']) $s .= "<td>{$r['quantity'][0]}</td>"; else $s .= "<td></td>";
			  if ($r['cost']) $s .= "<td>{$r['cost'][0]}</td>"; else $s .= "<td></td>";
			  if ($r['time']) $s .= "<td>{$r['time'][0]}</td>"; else $s .= "<td></td>";
			  if ($r['place']) $s .= "<td>{$r['place'][0]}</td>"; else $s .= "<td></td>";
			  $s .= "</tr><tr><td>新要求</td>";
			  if ($r['product']) $s .= "<td>{$r['product'][1]}</td>"; else $s .= "<td></td>";
			  if ($r['quantity']) $s .= "<td>{$r['quantity'][1]}</td>"; else $s .= "<td></td>";
			  if ($r['cost']) $s .= "<td>{$r['cost'][1]}</td>"; else $s .= "<td></td>";
			  if ($r['time']) $s .= "<td>{$r['time'][1]}</td>"; else $s .= "<td></td>";
			  if ($r['place']) $s .= "<td>{$r['place'][1]}</td>"; else $s .= "<td></td>";
			  $s .= "</tr>";
		  }
	  }
	  if ($this->effect) {
		  $rr = array();
		  foreach ($this->effect as $r) {
			  if (!$rr[$r['flow'] . $r['item']]) $rr[$r['flow'] . $r['item']] = $r;
			  else {
				  if ($r['product']) $rr[$r['flow'] . $r['item']]['product'] = $r['product'];
				  if ($r['quantity']) $rr[$r['flow'] . $r['item']]['quantity'] = $r['quantity'];
				  if ($r['cost']) $rr[$r['flow'] . $r['item']]['cost'] = $r['cost'];
				  if ($r['time']) $rr[$r['flow'] . $r['item']]['time'] = $r['time'];
				  if ($r['place']) $rr[$r['flow'] . $r['item']]['place'] = $r['place'];
			  }
		  }
		  $s .= "<tr><th colspan=8 align=center>此次修改产生以下影响</th></tr>";
		  $s .= "<tr><th>工作任务</th><th>受影响的供需节点</th><th></th><th>产品</th><th>数量</th><th>成本</th><th>时间</th><th>地点</th></tr>";
		  foreach ($rr as $r) {
			  $s .= "<tr><th rowspan=2>{$r['flow']}</th><th rowspan=2>{$r['item']}</th>";
			  $s .= "<td>原计划</td>";
			  if ($r['product']) $s .= "<td>{$r['product'][0]}</td>"; else $s .= "<td></td>";
			  if ($r['quantity']) $s .= "<td>{$r['quantity'][0]}</td>"; else $s .= "<td></td>";
			  if ($r['cost']) $s .= "<td>{$r['cost'][0]}</td>"; else $s .= "<td></td>";
			  if ($r['time']) $s .= "<td>{$r['time'][0]}</td>"; else $s .= "<td></td>";
			  if ($r['place']) $s .= "<td>{$r['place'][0]}</td>"; else $s .= "<td></td>";
			  $s .= "</tr><tr><td>变化</td>";
			  if ($r['product']) $s .= "<td>{$r['product'][1]}</td>"; else $s .= "<td></td>";
			  if ($r['quantity']) $s .= "<td>{$r['quantity'][1]}</td>"; else $s .= "<td></td>";
			  if ($r['cost']) $s .= "<td>{$r['cost'][1]}</td>"; else $s .= "<td></td>";
			  if ($r['time']) $s .= "<td>{$r['time'][1]}</td>"; else $s .= "<td></td>";
			  if ($r['place']) $s .= "<td>{$r['place'][1]}</td>"; else $s .= "<td></td>";
			  $s .= "</tr>";
		  }
	  }
	  return $s;
  }
}

?>