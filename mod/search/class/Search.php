<?php

/**
 * Searching class for phpwebsite
 *
 * @author  Steven Levin <steven [@] tux [.] appstate [.] edu>
 * @version $Id: Search.php,v 1.30 2005/07/05 13:16:00 matt Exp $
 */
class PHPWS_Search {

  /**
   * The query requested by the user.
   *
   * @var string
   * @see search()
   */
  var $query  = null;
  var $lists  = array();

  function form($mod = "all") {
    require_once(PHPWS_SOURCE_DIR."core/EZform.php");

    $tags = array();

    $form = new EZform("searchIt");
    $form->add("module", "hidden", "search");
    $form->add("search_op", "hidden", "search");
    $form->add("mod", "hidden", $mod);
    $form->add("query", "text", $this->query);

    if($mod == "all") {
      $form->setSize("query", 30);
    } else {
      $form->setSize("query", 20);
    }

    $form->setMaxSize("query", 255);
    $form->add("search", "submit", $_SESSION['translate']->it("Search"));

    $tags = $form->getTemplate();

    return PHPWS_Template::processTemplate($tags, "search", "form.tpl");
  }

  function search() {
    if(!isset($_REQUEST['mod']) || !is_string($_REQUEST['mod'])) {
      $module = "all";
    } else {
        $module = preg_replace('/\W/', '', $_REQUEST['mod']);
    }

    $this->lists = array();

    if(isset($_REQUEST['query'])) {
      $this->query = preg_replace("/[^\.\w\s-]/", "", $_REQUEST['query']);
    } else {
      return $this->results();
    }

    if($_REQUEST['mod'] == "all") {
      $sql = "SELECT module, block_title FROM {$GLOBALS['core']->tbl_prefix}mod_search ORDER BY module";
      $modules = $GLOBALS['core']->getAll($sql);

      if(is_array($modules) && (sizeof($modules) > 0)) {
	foreach($modules as $row) {
	  $this->lists[$row['module']]['title'] = $row['block_title'];
	  $this->lists[$row['module']]['pager'] = $this->_search($row['module']);
	}
      } 
    } else {

      $sql = "SELECT block_title FROM {$GLOBALS['core']->tbl_prefix}mod_search WHERE module='$module'";
      $row = $GLOBALS['core']->quickFetch($sql);
      $this->lists[$module]['title'] = $row['block_title'];
      $this->lists[$module]['pager'] = $this->_search($module);
    }

    return $this->results();
  }

  function _search($module) {
    $filename = PHPWS_SOURCE_DIR . 'mod/' . $module .'/conf/search.php';

    if (!is_file($filename)) {
      return $_SESSION['translate']->it("No items found matching your query.");
    }
    
    @include($filename);

    if($search_cols) {
      $search_array = explode(" ", $this->query);
      $cols_array = explode(", ", $search_cols);

      $sql = null;
      $where_clause = "WHERE ( ";
      for($i = 0; $i < count($cols_array); $i++){
	if($i > 0) {
	  $where_clause .= " OR ";
	}

	$ands = null;
	for($j = 0; $j < count($search_array); $j++){
	  $ands .= "$cols_array[$i] LIKE '%$search_array[$j]%' AND ";
	}

	$where_clause .= "(" . substr($ands, 0, -5) . ")";
      }

      $where_clause .= " )";
      $class_file_name = PHPWS_SOURCE_DIR . 'mod/' . $module . '/class/' . $class_file;

      if (!is_file($class_file_name)) {
	return $_SESSION['translate']->it("No items found matching your query.");
      }

      @require_once($class_file_name);
      
      if (class_exists($search_class)){
	$tempObj = new $search_class;
	if (method_exists($tempObj, $search_function)) {
	  $results = $tempObj->$search_function($where_clause);
	}
      }
    } else {
      if (class_exists($search_class)){
	$tempObj = new $search_class;
	if (method_exists($tempObj, $search_function)) {
	  $results = $tempObj->$search_function($search_array);
	}
      }
    }

    if(is_array($results) && (sizeof($results) > 0)) {
      require_once(PHPWS_SOURCE_DIR."core/WizardBag.php");
      $html = array();
      $x = 0;
      $highlight = null;
      foreach($results as $id=>$summary){
	$html[$x] = "<tr{$highlight}>";
	$html[$x] .= "<td>".($x + 1)."</td>";
	$html[$x] .= "<td>{$summary}</td>";
	$html[$x] .= "<td align=\"center\">";
	$html[$x] .= "<a href=\"./index.php?module={$module}{$view_string}{$id}\">".$_SESSION["translate"]->it("View")."</a>";
	$html[$x] .= "</td></tr>";
	$x++;
	PHPWS_WizardBag::toggle($highlight, " class=\"bg_light\"");
      }

      require_once(PHPWS_SOURCE_DIR."core/Pager.php");

      $pager = new PHPWS_Pager;
      $pager->setLinkBack("./index.php?module=search&amp;search_op=results&amp;list=$module");
      $pager->setLimits(array(5,10,25,50));
      $pager->setAnchor("#$module");
      $pager->limit = SEARCH_DEFAULT_RESULT_LIMIT;
      $pager->setData($html);
      $pager->pageData();

      return $pager;
    } else {
      return $_SESSION['translate']->it("No items found matching your query.");
    }
  }

  function results() {
    $display = array();

    $GLOBALS['CNT_search_results']['title'] = $_SESSION['translate']->it("Search results for")."&#160;".$this->query;

    if(is_array($this->lists) && sizeof($this->lists)) {
      foreach($this->lists as $module => $list) {
	$listTags = array();
	$listTags['TITLE']         = $list['title'];
	$listTags['SUMMARY_LABEL'] = $_SESSION['translate']->it("Summary");
	$listTags['ACTION_LABEL']  = $_SESSION['translate']->it("Action");

	if(is_object($list['pager'])) {
	  if(isset($_REQUEST['list']) && ($_REQUEST['list'] == $module)) {
	    $list['pager']->pageData();
	  }

	  $listTags['ANCHOR'] = $module;
	  $listTags['ITEMS'] = $list['pager']->getData();
	  $listTags['BACK'] = $list['pager']->getBackLink();
	  $listTags['FORWARD'] = $list['pager']->getForwardLink();
	  $listTags['LIMITS'] = $list['pager']->getLimitLinks();
	  $listTags['SECTIONS'] = $list['pager']->getSectionLinks();
	  $listTags['INFO'] = $list['pager']->getSectionInfo();
	} else {
	  $listTags['ITEMS'] = "<tr><td colspan=\"3\">{$list['pager']}</td></tr>";
	}

	$display[] = PHPWS_Template::processTemplate($listTags, "search", "list.tpl");
      }
    }
  
    return implode("\n", $display);
  }

  function block($module) {
    $sql = "SELECT show_block, block_title FROM {$GLOBALS['core']->tbl_prefix}mod_search WHERE module='$module'";
    $result = $GLOBALS['core']->quickFetch($sql);

    if(is_null($result) || $result['show_block']) {	// show it by default
      $GLOBALS['CNT_search_block']['title'] = $_SESSION['translate']->it("Search")." ".$result['block_title'];
      $GLOBALS['CNT_search_block']['content'] = $this->form($module);
    }
  }

  function settings() {
    require_once(PHPWS_SOURCE_DIR.'core/List.php');

    $GLOBALS['CNT_search_results']['title'] = $_SESSION['translate']->it("Search Settings");
    if(isset($_REQUEST['save'])) {
      if(isset($_REQUEST['block_title'])) {
	foreach($_REQUEST['block_title'] as $id => $value) {
	  $title = $_REQUEST['block_title'][$id];
	  if(isset($_REQUEST['show_block'][$id])) {
	    $block = 1;
	  } else {
	    $block = 0;
	  }

	  $sql = "UPDATE {$GLOBALS['core']->tbl_prefix}mod_search SET block_title='$title', show_block='$block' WHERE id='$id'";
	  $GLOBALS['core']->query($sql);
	}
      }
    }

    $listTags = array();
    $listTags['MODULE_LABEL'] = $_SESSION["translate"]->it("Module");
    $listTags['BLOCK_TITLE_LABEL'] = $_SESSION["translate"]->it("Block Title");
    $listTags['SHOW_BLOCK_LABEL'] = $_SESSION["translate"]->it("Show Block");
    $listTags['SAVE'] = $_SESSION["translate"]->it("Save");

    $list = new PHPWS_List;
    $list->setModule("search");
    $list->setClass("PHPWS_SearchSettings");
    $list->setTable("mod_search");
    $list->setDbColumns(array("id", "module", "block_title", "show_block"));
    $list->setListColumns(array("Module", "Block_Title", "Show_Block"));
    $list->setName("settings");
    $list->setOp("search_op=settings");
    $list->setExtraListTags($listTags);
    $list->setOrder("module ASC");    

    return $list->getList();
  }

  function register($module) {
    $filename = PHPWS_SOURCE_DIR."mod/{$module}/conf/search.php";
    if(file_exists($filename)) {

      @include($filename);

      $save = array("module"=>$module, "show_block"=>$show_block, "block_title"=>$block_title);
      $GLOBALS['core']->sqlInsert($save, "mod_search");
    }
  }

  function unregister($module) {
    $GLOBALS['core']->sqlDelete("mod_search", "module", $module);
  }

  function action() {
    if(isset($_REQUEST['search_op'])) {
      $op = $_REQUEST['search_op'];
    } else {
      $op = null;
    }

    $content = null;

    switch($op) {
    case "form": 
      $GLOBALS['CNT_search_results']['title'] = $_SESSION['translate']->it("Search");
      $content = $this->form();
      break;

    case "search":
      $content = $this->search();
      break;

    case "results":
      $content = $this->results();
      break;

    case "settings":
      $content = $this->settings();
      break;
    }

    if(isset($content)) {
      $GLOBALS['CNT_search_results']['content'] = $content;
    }
  }
}

class PHPWS_SearchSettings {

  var $data = null;

  function PHPWS_SearchSettings($row) {
    $this->data = $row;
  }

  function getListModule() {
    return $this->data['module'];
  }

  function getListBlock_Title() {
    return "<input type=\"text\" name=\"block_title[{$this->data['id']}]\" value=\"{$this->data['block_title']}\" size=\"30\" maxlength=\"35\" />";
  }

  function getListShow_Block() {
    if($this->data['show_block']) {
      return "<input type=\"checkbox\" name=\"show_block[{$this->data['id']}]\" value=\"1\" checked=\"checked\" />";
    } else {
      return "<input type=\"checkbox\" name=\"show_block[{$this->data['id']}]\" value=\"1\" />";
    }
  }

}

?>