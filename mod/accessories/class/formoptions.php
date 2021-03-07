<?php
if (!function_exists('formoptions')) {		// find the proper form options for the current user
	function formoptions($name, $col = NULL, $getall = true, $org = NULL) {
		return $res;
	}
}

class Data2FormConverter {
	protected $obj = NULL;
	protected $mode = NULL;

	function Data2FormConverter($obj, $mode = 0) {
	}
	function __construct($obj, $mode = 0) {
		$this->obj = $obj;
		$this->mode = $mode;
	}
	protected function exists($name) {
		return method_exists($this, $name) || method_exists($this->obj, $name) || isset($this->obj[$name]) || isset($this->obj->$name);
	}
	protected function value($name) {
		if (method_exists($this, $name)) return $this->$name();
		elseif (is_array($this->obj)) return $this->obj[$name]; 
		elseif (method_exists($this->obj, $name)) return $this->obj->$name();
		elseif (isset($this->obj->$name)) return $this->obj->$name;

		return NULL;
	}

	function mode($mode) { $this->mode = $mode; }
	function data2col($form) {
		$res = array();
		foreach ($form as $q) {
			if (!$q['optin']) continue;
			$name = $q['colname'];
			if ($this->exists($q['colname'])) {
				if (is_array($mode)) {
					$mode["{VALUE}"] = $this->value($q['colname']);
					$mode["{NAME}"] = $q['colname'];
					$res[] = strtr($q['defaultval'], $mode);
				}
				else switch ($mode & 0xF) {
					case 0:
						$res[] = $this->value($q['colname']);
						break;
					case 1:		// text
						$res[] = "" . $this->value($q['colname']);
						break;
				}
			}
			else {
				if (is_array($mode)) {
					$mode["{VALUE}"] = '';
					$res[] = strtr($q['defaultval'], $mode);
				}
				else switch ($mode & 0xF0) {
					case 0x0:
						$res[] = NULL;
						break;
					case 0x10:		// text
						$res[] = "";
						break;
				}
			}
		}
		return $res;
	}
}

?>