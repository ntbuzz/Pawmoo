<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 * 	 AppHelper: HTML generate for ViewTemplate
 */
//==============================================================================
class RecordColumns {
	public $Primary;			// Primary-Key Values
	//==============================================================================
	// extract column name, value set
	public function SetColumns($primary,$data) {
		$this->Primary = $data[$primary];
		foreach($data as $key => $val) {
			$this->$key = $val;
		}
	}
}
//==============================================================================
class AppHelper  extends AppObject {
	const AttrAlign = [
		'',						// 0
		' align="left"',		// 1 = left
		' align="center"',		// 2 = center
		' align="right"',		// 3 right
	];
//==============================================================================
// constructor( this_Owner_Object )
/*
	function __construct($owner) {
		parent::__construct($owner);
	}
*/
//==============================================================================
// Call for Owner(AppView) Template Processing Method
public function ViewTemplate($layout) {
	$this->AOwner->ViewTemplate($layout);
}
//==============================================================================
// Runtime output
public function Runtime() {
	echo "<hr>\n";
	debug_run_time(-1);
}
//==============================================================================
// Resource(.css/.js) Output (Not USE!)
public function Resource($res) {
	list($filename,$ext) = extract_base_name($res);
	$AppStyle = new AppStyle($this->ModuleName, $ext);
	$AppStyle->ViewStyle($filename);
	unset($AppStyle);
}
//==============================================================================
// check Request Controller is Me?
public function IsRequestController($comp) {
	$hit = (is_scalar($comp)) ? $comp === App::$Controller : in_array(App::$Controller,$comp,true);
	echo ($hit) ? '' : ' class="closed"';
}
//==============================================================================
// expand String by USE Owner(AppView) Method
public function expand_var($str,$vars=[]) {
	$str = $this->AOwner->expand_Strings($str,$vars);
	return $str;
}
//==============================================================================
// expand LOCALE varible in $str, after echo string
// format {#locale-id}
public function expand_echo($str) {
	echo $this->expand_locale($str);
}
//==============================================================================
// expand LOCALE varible in $str
// format {#locale-id}
public function expand_locale($str) {
	$p = '/\{#[^}\s]+?}/';
	preg_match_all($p,$str,$m);
	$varList = $m[0];
	if(empty($varList)) return $str;
	$varList = array_unique($varList);
	$values = array_map(function($v) {
			$v = trim($v,'#{}');
			return $this->_($v);
		},$varList);
	return str_replace($varList,$values,$str);
}
//==============================================================================
// Make HYPER-Link
public function ALink($lnk,$txt,$under=false) {
	if($txt[0] == '#') {
		$txt = mb_substr($txt,1);
		$txt = $this->_($txt);
	}
	$href = make_hyperlink($lnk,$this->ModuleName);
	if(is_array($under)) {
		$uline = ' class="'.implode(' ',$under).'"';
	} else if(is_string($under)) {
		$uline = (empty($under)) ? '' : " class='{$under}'";
	} else $uline = ($under) ? '' : ' class="nounder"';
	$target = (get_protocol($href) !== NULL) ? ' target=_blank':'';
	echo "<a{$uline} href='{$href}'{$target}>{$txt}</a>";
}
//==============================================================================
// generate Page Button LABEL Tag
	private function get_PageButton($n, $anchor, $npage) {
		$anchor = substr("00{$anchor}", ($anchor>=100)?-3:-2);
		$cls = (($n == $this->MyModel->page_num) || ($n == 0) || ($n > $npage)) ? 'active' : 'jump';
		return "<span class='{$cls}' value='{$n}'>{$anchor}</span>";
	}
//==============================================================================
// Page Move up/down link
	private function get_MoveButton($move, $anchor, $npage) {
		$n = $this->MyModel->page_num + $move;
		$cls = ( ($n <= 0) || ($n > $npage)) ? 'disable' : 'move';
		return "<span class='{$cls}' value='{$n}'>{$anchor}</span>";
	}
//==============================================================================
// Pager Buttons
public function MakePageLinks() {
	if($this->MyModel->pagesize == 0) return;
	$npage = intval(($this->MyModel->record_max+$this->MyModel->pagesize-1)/$this->MyModel->pagesize);
	$pnum = $this->MyModel->page_num;
	$bound = 7;
	$begp = max( 2, min( $npage - 1, $pnum + intval($bound/2)) - $bound);
	$endp = min($npage - 1, $begp + $bound);
	echo "<div class='pager'><div class='navigate'>";
	$ptitle = $this->__(".Page", FALSE);
	echo "<span class='pager_title' id='pager_help'>{$ptitle}</span>";
	echo "<span class='separator'>";
	echo $this->get_MoveButton(-1,$this->__(".PrevPage"),$npage);
	echo "|";
	echo $this->get_MoveButton(1,$this->__(".NextPage"),$npage);
	echo "</span>";
	if($npage > 1 && $begp > 1) {		// Top Page-Jump
		echo $this->get_PageButton(1,1,$npage);
		if($begp > 2) echo '<span class="disable">…</span>';
	}
	for($n=$begp; $n <= $endp; $n++) {		// each page jump button
		echo $this->get_PageButton($n,$n,$npage);
	}
	if($endp < $npage) {		// Jump to LAST
		if($endp < ($npage - 1)) echo '<span class="disable">…</span>';
		echo $this->get_PageButton($npage,$npage,$npage);
	}
	$fmt = $this->__(".Total", FALSE);
	$total = sprintf($fmt,$this->MyModel->record_max);
	echo "<span class='pager_message'>{$total}</span>";
	echo "</div>\n";
	// Change Page item Count
	$param = (empty(App::$Filter)) ? "1/" : implode('/',App::$Filters)."/1/";
	$href = App::Get_AppRoot($this->ModuleName,TRUE)."/page/{$param}";
	$dsp = "<span id='size_selector'>".$this->__(".Display", FALSE)."</span>";
	echo "<div class='rightalign'>{$dsp}<SELECT id='pagesize'>";
	foreach(array(5,10,15,20,25,50,100) as $val) {
		$sel = ($val == $this->MyModel->pagesize) ? " selected" : "";
		echo "<OPTION value='{$val}'{$sel}>{$val}</OPTION>\n";
	}
	echo "</SELECT></div>\n";
	echo "</div>";
}
//==============================================================================
// PUT TABLE List Header
	protected function putTableHeader() {
		echo '<tr>';
		foreach($this->MyModel->HeaderSchema as $key => $val) {
			list($alias,$align,$flag,$wd) = $val;
			$tsort = ($flag==2) ? '' : ' class="sorter-false"';
			$style = ($wd==0) ? '' : " style='width:{$wd}px;'";
			echo "<th${tsort}{$style}>{$alias}</th>";
		}
		echo "</tr>\n";
	}
//==============================================================================
// Put Each record columns
	protected function putColumnData($lno,$columns) {
		echo "<tr class='item' id='{$columns->Primary}'>";
		foreach($this->MyModel->HeaderSchema as $key => $val) {
			list($alias,$align,$flag,$c_wd) = $val;
			$style = ($c_wd > 0) ? " style='max-width:{$c_wd}px;'":'';
			$pos = self::AttrAlign[$align];
			echo "<td{$pos}{$style}>{$columns->$key}</td>";
		}
		echo "</tr>\n";
	}
//==============================================================================
// Output Table List for Records
public function MakeListTable($deftab) {
	// デバッグ情報
	debug_log(DBMSG_VIEW,[
		'deftab' => $deftab,
		'Page' => $this->MyModel->page_num,
		'Size' => $this->MyModel->pagesize,
	]);
	if(array_key_exists('pager',$deftab) && $deftab['pager'] == 'true') $this->MakePageLinks();
	if(is_array($deftab)) {
		$tab = $deftab["category"];
		$tbl = $deftab["tableId"];
	} else {
		$tab = $deftab;
		$tbl = '_TableList';
	}
	echo "<table id='{$tbl}' class='tablesorter {$tab}'>\n<thead>\n";
	$this->putTableHeader($tab);
	echo "</thead>\n<tbody>\n";
	$col = new RecordColumns();
	$lno = ($this->MyModel->page_num-1)*$this->MyModel->pagesize + 1;
	foreach($this->MyModel->Records as $columns) {
		$col->SetColumns($this->MyModel->Primary,$columns);
		$this->putColumnData($lno++, $col);
	}
	echo "</tbody></table>";
}
//==============================================================================
// Put Tabset
public function Tabset($name,$menu,$sel) {
	echo "<ul class='{$name}'>\n";
	$tab = $sel;
	foreach($menu as $key => $val) {
		echo '<li'.(($tab == $val)?' class="selected">':'>') . "{$key}</li>\n";
	}
	echo "</ul>\n";
}
//==============================================================================
// Muuuuuuu.....
public function Contents_Tab($sel,$default='') {
	$tab = $default;
	return '<li' . (($tab == $sel) ? '' : ' class="hide"') . ">\n";
}
//==============================================================================
// ChainSelect Object-List
public function SelectObject($args) {
	list($selname,$select_one) = explode(",",$args);
	$object = "{$selname}: {\n\tselect_one: {$select_one},\n\tsel_list: [\n";
   	foreach($this->MyModel->Select[$selname] as $valset) {
		$new_map = array_map(function($v) {
			return (is_numeric($v)) ? $v :"'{$v}'";
		},$valset);
		if(count($new_map)===2) $new_map[] = 0;
		$object .= "\t[".implode(',',$new_map) ."],\n";
	}
	$object .= "]},";
	return $object;
}
/*
public function SelectObject($keyset) {
	if(is_scalar($keyset)) $keyset = explode(',',$keyset);
	$str = '';
	foreach($keyset as $key) {
		$str .= "'{$key}': [\n";
    	foreach($this->MyModel->Select[$key] as $valset) {
			$new_map = array_map(function($v) {
				return (is_numeric($v)) ? $v :"'{$v}'";
			},$valset);
			if(count($new_map)===2) $new_map[] = 0;
			$str .= "[".implode(',',$new_map) ."],\n";
		}
		$str .= "],\n";
	}
	return $str;
}
*/
//==============================================================================
// Gen Form TAG
// attr array
// 'method' => 'Post'
// 'acrion' => URI
// 'id' => identifir
//
public function Form($act, $attr) {
	if ($act[0] !== '/') $act = App::Get_AppRoot($act,TRUE);
	$arg = '';
	foreach($attr as $key => $val) {
		$arg .= $key .'="' . $val . '"';
	}
	echo '<form action="' . $act . '" ' . $arg . '>';
}
//==============================================================================
// Generate SELECT Tag & ECHO
public function Select($key,$name) {
	echo $this->Select_str($key,$name);
}
//==============================================================================
// Create Select Tag strings
public function Select_str($key,$name) {
    $dat = intval($this->MyModel->RecData[$name]);	// NULL is ZERO(0)
    $select = "<SELECT name='{$name}'>";
    foreach($this->MyModel->Select[$key] as $ttl => $id) {
        $sel = ($id == $dat) ? " selected" : "";	// digit-str or int
        $select .= "<OPTION value='{$id}'{$sel}>{$ttl}</option>\n";
    }
	return "{$select}\n</SELECT>\n";
}
//==============================================================================
// Generate INPUT tag
//	$this->Input($type,$name,$attr)
//<input type="text" id="datepicker1" name="begDate"> ～ <input type="text" id="datepicker2" name="endDate">
//
public function Input($type,$name,$attr) {
	$tag = "<input type='{$type}' name='{$name}' ";
	foreach($attr as $key => $val) {
			$tag .= $key .'="' . $val . '" ';
	}
	$tag .= '>';
	echo $tag;
}
//==============================================================================
// IMAGE tag generate
public static function ImageTag($file,$attr) { 
	$path = (($file[0] == '/') ? '/common' : App::$sysRoot) . $file;
	echo "<image src='{$path}' {$attr} />\n";
}
//==============================================================================
// JS array define
public static function define_var_array($arr,$name) { 
	$row = "var {$name} = {\n'" . inplode("',\n'",$arr) . "'\n};";
	return $row;
}

}
