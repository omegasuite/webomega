<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');

class Attributes {
  public static $basicUnits = array('斤','公斤','吨','升','米','厘米','平方米','平方厘米','两','克','毫升','立方米','g','kg','m','cm','m2','cm2','l','ml');
  public static $units = array('斤','公斤','吨','升','米','厘米','平方米','平方厘米','件','包','盒','袋','罐','瓶','桶','箱','个','只','份','条','尾','两','克','毫升','立方米','g','kg','m','cm','m2','cm2','l','ml');
  public static $weightUnits = array('斤','公斤','吨','两','克','g','kg');
  public static $volumnUnits = array('升','毫升','立方米','l','ml');
  public static $areaUnits = array('平方米','平方厘米','m2','cm2');
  public static $lengthUnits = array('米','厘米','m','cm');
  public static $countUnits = array('件','包','盒','袋','罐','瓶','桶','箱','个','只','份','条','尾');
  public static $unitsConv = array(
	  '个'=>array('个'=>1,'只'=>1,'份'=>1,'条'=>1,'尾'=>1),
	  '只'=>array('个'=>1,'只'=>1,'份'=>1,'条'=>1,'尾'=>1),
	  '份'=>array('个'=>1,'只'=>1,'份'=>1,'条'=>1,'尾'=>1),
	  '条'=>array('个'=>1,'只'=>1,'份'=>1,'条'=>1,'尾'=>1),
	  '尾'=>array('个'=>1,'只'=>1,'份'=>1,'条'=>1,'尾'=>1),
/*
	  '件'=>array('件'=>1),
	  '盒'=>array('盒'=>1),
	  '袋'=>array('袋'=>1),
	  '包'=>array('包'=>1),
	  '罐'=>array('罐'=>1),
	  '瓶'=>array('瓶'=>1),
	  '桶'=>array('桶'=>1),
*/
	  '米'=>array('米'=>1, 'm'=>1, 'cm'=>100, '厘米'=>100),
	  '厘米'=>array('米'=>0.01, 'm'=>0.01, 'cm'=>1, '厘米'=>1),
	  'm'=>array('米'=>1, 'm'=>1, 'cm'=>100, '厘米'=>100),
	  'cm'=>array('米'=>0.01, 'm'=>0.01, 'cm'=>1, '厘米'=>1),
	  '平方米'=>array('平方米'=>1, '平方厘米'=>10000, 'm2'=>1, 'cm2'=>10000),
	  '平方厘米'=>array('平方厘米'=>1, '平方米'=>0.0001, 'cm2'=>1, 'm2'=>0.0001),
	  'm2'=>array('平方米'=>1, '平方厘米'=>10000, 'm2'=>1, 'cm2'=>10000),
	  'cm2'=>array('平方厘米'=>1, '平方米'=>0.0001, 'cm2'=>1, 'm2'=>0.0001),
	  '斤'=>array('斤'=>1,'公斤'=>0.5,'吨'=>0.0005,'两'=>10,'克'=>500,'g'=>500,'kg'=>0.5),
	  '公斤'=>array('斤'=>2,'公斤'=>1,'吨'=>0.001,'两'=>20,'克'=>1000,'g'=>1000,'kg'=>1),
	  'kg'=>array('斤'=>2,'公斤'=>1,'吨'=>0.001,'两'=>20,'克'=>1000,'g'=>1000,'kg'=>1),
	  '吨'=>array('斤'=>2000,'公斤'=>1000,'吨'=>1,'两'=>20000,'克'=>1000000,'g'=>1000000,'kg'=>1000),
	  '两'=>array('斤'=>0.1,'公斤'=>0.05,'吨'=>0.0005,'两'=>1,'克'=>50,'g'=>50,'kg'=>0.05),
	  '克'=>array('斤'=>0.0005,'公斤'=>0.001,'吨'=>0.000001,'两'=>0.02,'克'=>1,'g'=>1,'kg'=>0.001),
	  'g'=>array('斤'=>0.0005,'公斤'=>0.001,'吨'=>0.000001,'两'=>0.02,'克'=>1,'g'=>1,'kg'=>0.001),
	  '升'=>array('升'=>1,'毫升'=>1000,'立方米'=>0.001, 'l'=>1,'ml'=>1000),
	  '毫升'=>array('升'=>0.001,'毫升'=>1,'立方米'=>0.000001, 'l'=>0.001,'ml'=>1),
	  'l'=>array('升'=>1,'毫升'=>1000,'立方米'=>0.001, 'l'=>1,'ml'=>1000),
	  'ml'=>array('升'=>0.001,'毫升'=>1,'立方米'=>0.000001, 'l'=>0.001,'ml'=>1),
	  '立方米'=>array('升'=>1000,'毫升'=>1000000,'立方米'=>1, 'l'=>1000,'ml'=>1000000));
  public static $unitsClasses = array(
	  '个'=>1, '只'=>1, '份'=>1, '条'=>1, '尾'=>1, '件'=>0, '盒'=>0, '袋'=>0, '包'=>0, '罐'=>0, '瓶'=>0, '桶'=>0, '米'=>4,
	  '厘米'=>4, 'm'=>4, 'cm'=>4, '平方米'=>5, '平方厘米'=>5, 'm2'=>5, 'cm2'=>5, '斤'=>2, '公斤'=>2, 'kg'=>2, '吨'=>2,
	  '两'=>2, '克'=>2, 'g'=>2, '升'=>3, '毫升'=>3, 'l'=>3, 'ml'=>3, '立方米'=>3);
}

function validAttribs($a, $unit) {
	if ($unit && Attributes::$basicUnits[$unit]) return true;
	if (isset($a['单位']) && isset($a[$a['单位']])) return validAttribs($a[$a['单位']], $a['单位']);
	foreach ($a as $v) if (validAttribs($v, NULL)) return true;
	return false;
}

function matchAttribs($a, $attribs = NULL) {	// whether attribs includes everything is a, except for quantity
	  if (!$a) return true;
	  if (!$attribs) return false;
	  if (!is_array($attribs)) $attribs = unserialize($attribs);
	  if (!is_array($a)) $a = unserialize($a);
	  foreach ($a as $i=>$v) {
		  if ($i == '单位' || $i == '数量' || $i{0} == '_') continue;
		  if (is_array($v) && is_array($attribs[$i])) {
				if (!matchAttribs($v, $attribs[$i])) return false;
		  }
		  elseif (is_array($v) || is_array($attribs[$i])) return false;
		  elseif ($attribs[$i] != $v) return false;
	  }
	  return true;
}

function unitcloseness($a, $attribs = NULL) {
	if ($a['单位'] && $attribs['单位'] && Attributes::$unitsConv[$a['单位']][$attribs['单位']])
		return 0;

	$s = -1000;
	if (isset($a[$a['单位']]) && isset($attribs[$attribs['单位']]))
		$s = max($s, unitcloseness($a[$a['单位']], $attribs[$attribs['单位']])) - 1;

	if (isset($a[$a['单位']]))
		$s = max($s, unitcloseness($a[$a['单位']], $attribs) - 2);
	if (isset($attribs[$attribs['单位']]))
		$s = max($s, unitcloseness($a, $attribs[$attribs['单位']]) - 2);

	return $s;
}

function ismore($a, $b) {
	if ($a['quantity'] == 0) return false;
	if ($b['quantity'] == 0) return true;

	if (in_array($a['unit'], Attributes::$basicUnits)) {
		$au = $a['unit']; $aq = $a['quantity'];
	}
	elseif (!$a['attribs'] || !is_array($a['attribs'])) return NULL;
	else {
		$t = bottomUnit($a['attribs']);
		if (!$t) return NULL;
		$au = $t['单位']; $aq = $t['数量'] * $a['quantity'];
	}

	if (in_array($b['unit'], Attributes::$basicUnits)) {
		$bu = $b['unit']; $bq = $b['quantity'];
	}
	elseif (!$b['attribs'] || !is_array($b['attribs'])) return NULL;
	else {
		$t = bottomUnit($a['attribs']);
		if (!$t) return NULL;
		$bu = $t['单位']; $bq = $t['数量'] * $b['quantity'];
	}

	if ($au == $bu) return $aq >= $bq;

	if (Attributes::$unitsConv[$bu][$au])
		return $aq >= $bq * Attributes::$unitsConv[$bu][$au];

	return NULL;
}

function lessby($a, $b) {
	if (in_array($a['unit'], Attributes::$basicUnits)) {
		$au = $a['unit']; $aq = $a['quantity']; $atq = 1;
	}
	elseif (!$a['attribs'] || !is_array($a['attribs'])) return NULL;
	else {
		$t = bottomUnit($a['attribs']);
		if (!$t) return NULL;
		$au = $t['单位']; $aq = $t['数量'] * $a['quantity']; $atq = $t['数量'];
	}

	if (in_array($b['unit'], Attributes::$basicUnits)) {
		$bu = $b['unit']; $bq = $b['quantity'];
	}
	elseif (!$b['attribs'] || !is_array($b['attribs'])) return NULL;
	else {
		$t = bottomUnit($a['attribs']);
		if (!$t) return NULL;
		$bu = $t['单位']; $bq = $t['数量'] * $b['quantity'];
	}

	if ($au == $bu) return $aq - $bq;

	if (Attributes::$unitsConv[$bu][$au])
		return ($aq - $bq * Attributes::$unitsConv[$bu][$au]) / $atq;

	return NULL;
}

function closeness($a, $attribs = NULL, $checkunit = true) {
	// measure how close an $attribs is to target $a. the higher the score, the closer it is
	// quantity quality is measured by negative level of indirection. a final non-match get a negative -10000 score
	// all other matches gets a 10 pont

	if (!$a) return 0;
	if (!$attribs) return 0;
	if (!is_array($attribs)) $attribs = unserialize($attribs);

	$score = ($checkunit?20 + unitcloseness($a, $attribs) : 0);

	foreach ($a as $i=>$v) {
		if ($i == '单位' || $i == '数量') continue;

		if (is_array($v) && is_array($attribs[$i])) {
			$score += closeness($v, $attribs[$i], false);
		}
		elseif (!is_array($v) && !is_array($attribs[$i]) && $attribs[$i] == $v) $score += 10;
	}
	return $score;
}

function attribsEq($thisattribs, $attribs = NULL) {
	  if (!$thisattribs && !$attribs) return true;
	  if (!$attribs || !$thisattribs) return false;
	  if (!is_array($attribs)) $attribs = unserialize($attribs);
	  foreach ($thisattribs as $i=>$v)
		  if (is_array($v) && is_array($attribs[$i])) {
				if (!attribsEq($v, $attribs[$i])) return false;
		  }
		  elseif (is_array($v) || is_array($attribs[$i])) return false;
		  elseif ($attribs[$i] != $v) return false;

	  foreach ($attribs as $i=>$v)
		  if (is_array($v) && is_array($thisattribs[$i])) {
				if (!attribsEq($v, $thisattribs[$i])) return false;
		  }
		  elseif (is_array($v) || is_array($thisattribs[$i])) return false;
		  elseif ($thisattribs[$i] != $v) return false;
	  return true;
}

function convertibleUnit($attribs) {
	if (!$attribs) return NULL;

	if ($attribs['数量'] && $attribs['单位']) {
		if ($attribs[$attribs['单位']] && is_array($attribs[$attribs['单位']])) {
			$t = convertibleUnit($attribs[$attribs['单位']]);
			if ($t) return $t;
		}
		elseif (in_array($attribs['单位'], Attributes::$basicUnits)) return $attribs['单位'];
	}

	if ($attribs) foreach ($attribs as $i=>$v) {
		if (is_array($v)) {
			$t = convertibleUnit($v);
			if ($t) return $t;
		}
	}

	return NULL;
}

function formatAttrib($attrib, $nl = "<br>", $indent = '') {
	$s = '';
	if ($attrib) foreach ($attrib as $name=>$val) {
		if ($name == '_') return $val;
		if ($name{0} == '_') continue;

		$s .= $name . "：";
		if (is_array($val)) $s .= formatAttrib($val, $nl, $indent . ($nl == "<br>"?"&nbsp;&nbsp;&nbsp;&nbsp;" : "    "));
		else $s .= $val . $nl;
	}
	return $s;
}

function attribName($attrib) {
	$s = '';
	if ($attrib) {
		foreach ($attrib as $name=>$val) {
			if ($name == '_') return $val;
		}
		if ($attrib) foreach ($attrib as $name=>$val) {
			if ($name{0} == '_') return $val;
		}
	}
	return $s;
}

function attribid($attrib) {
	$s = NULL;
	if ($attrib) foreach ($attrib as $name=>$val) {
		if ($name{0} == '_' && !$s) $s = substr($name, 1);
	}
	return $s;
}

function _formatAttribTable($attrib, $col = 1, $h = true, $full = true) {
	$s = '';
	if (is_array($attrib)) foreach ($attrib as $name=>$val) {
		if (!$full && $name == '_')
			return ($h?"<tr><td colspan=$col align=right valign=top>" : '<td align=right valign=top>') . $val . "</td></tr>";
		if ($name{0} == '_') continue;

		$s .= ($h?"<tr><td colspan=$col align=right valign=top>" : '<td align=right valign=top>') . $name . "：</td>";
		if (is_array($val)) $s .= _formatAttribTable($val, $col + 1, false);
		else $s .= "<td>" . $val . "</td></tr>";
		$h = true;
	}
	return $s;
}

function formatAttribTable($attrib) {
	static $seq = 0;

	$r = _formatAttribTable($attrib);
	$s = attribName($attrib);
//	$s = _formatAttribTable($attrib, 1, true, false);

	if ($r == $s) return '<table class=table-small align=center>' . $r . '</table>';

	$seq--;

	return '<center><a href=# onclick="toggleattrib(' . $seq . ');"><div name="__shortattrib[' . $seq . ']" id="__shortattrib[' . $seq . ']" style="display:block;">' . $s . '</div><div name="__fullattrib[' . $seq . ']" id="__fullattrib[' . $seq . ']" style="display:none;"><table class=table-small align=center>' . $r . '</table></div></a></center>';
//	return '<center><a href=# onclick="toggleattrib(' . $seq . ');"><div id="__shortattrib[' . $seq . ']" style="display:block;"><table class=table-small align=center>' . $s . '</table></div><div id="__fullattrib[' . $seq . ']" style="display:none;"><table class=table-small align=center>' . $r . '</table></div></a></center>';
}

function convertUnit($u, $d, $attribs = NULL) {
	if ($u == $d) return 1;
	if (Attributes::$unitsConv[$u][$d]) return Attributes::$unitsConv[$u][$d];
	if (!is_array($attribs)) return 0;

	if ($attribs['数量'] && $attribs['单位'] && isset($attribs[$attribs['单位']]) && is_array($attribs[$attribs['单位']])) {
		$t = convertUnit($u, $d, $attribs[$attribs['单位']]);
		if ($t) return $v['数量'] * $t;
	}
	elseif ($attribs['数量'] && $attribs['单位'] && Attributes::$unitsConv[$attribs['单位']][$d]) {
		return $v['数量'] * Attributes::$unitsConv[$attribs['单位']][$d];
	}

	return 0;
}

function convertUnitTo($attribs, $u, $cls = NULL) {
	if (!is_array($attribs)) return NULL;

	if ($cls && isset($attribs[$cls])) {
		$t = convertUnitTo($attribs[$cls], $u);
		if ($t !== NULL) return $t;
	}

	if (!$cls) {
		if ($attribs['数量'] && $attribs['单位'] && isset(Attributes::$unitsConv[$attribs['单位']][$u]))
			return $attribs['数量'] * Attributes::$unitsConv[$attribs['单位']][$u];

		if ($attribs['数量'] && $attribs['单位'] && is_array($attribs[$attribs['单位']])) {
			$t = convertUnitTo($attribs[$attribs['单位']], $u);
			if ($t !== NULL) return $attribs['数量'] * $t;
		}
	}

	foreach ($attribs as $v) {
		$t = convertUnitTo($v, $u, $cls);
		if ($t !== NULL) return $t;
	}

	return NULL;	
}

function bottomUnit($attribs) {
	if ($attribs['单位'] && $attribs['数量']) {
		if (in_array($attribs['单位'], Attributes::$basicUnits) || !$attribs[$attribs['单位']])
			return $attribs;
		elseif (is_array($attribs[$attribs['单位']])) {
			$res = bottomUnit($attribs[$attribs['单位']]); 
			if ($res) return array('单位'=>$res['单位'], '数量'=>$res['数量'] * $attribs['数量']);
		}
		else return array('单位'=>$attribs[$attribs['单位']], '数量'=>$attribs['数量']);
	}
	return NULL;
}

function basicUnit($attribs) {
	if ($attribs['单位'] && $attribs['数量']) {
		if (in_array($attribs['单位'], Attributes::$basicUnits)) return $attribs;
		elseif ($attribs[$attribs['单位']]) {
			$res = basicUnit($attribs[$attribs['单位']]); 
			if ($res) return array('单位'=>$res['单位'], '数量'=>$res['数量'] * $attribs['数量']);
		}
	}
	return NULL;
}

  function object_attrib_array($attrib) {	// object=>array
	  $s = array();
	  if (is_array($attrib)) foreach ($attrib as $i=>$val) {
		  if (is_object($val)) $s[$val->name] = object_attrib_array($val->val);
		  elseif (is_array($val)) $s[$i] = object_attrib_array($val);
		  else $s[$i] = $val;
	  }
	  elseif (is_object($attrib) && isset($attrib->name)) $s[$attrib->name] = object_attrib_array($attrib->val);
	  else return $attrib;
	  return $s;
  }

  function array_attrib_object($attrib) {	// array=>object
	  return convformattrib($attrib);
  }

  function convformattrib($attrib) {	// array=>object
	  $s = array();
	  if (is_array($attrib)) foreach ($attrib as $name=>$val) {
		  if (is_array($val)) $s[] = array('name'=>$name, 'val'=>convformattrib($val));
		  else $s[] = array('name'=>$name, 'val'=>$val);
	  }
	  return $s;
  }

  function spec($c) {
	$c = unserialize($c);
	$spec = ''; $g = '';
	if (is_array($c)) foreach ($c as $i=>$v) {
		$spec .= $g . $i . '：' . $v;
		$g = "；";
	}
	else $spec = "" . $c;
	return $spec;
  }

?>