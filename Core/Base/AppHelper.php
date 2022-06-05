<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 * 	 AppHelper: HTML generate for ViewTemplate
 */
const TableAlignAttrs = [
	'',						// 0
	' align="left"',		// 1 = left
	' align="center"',		// 2 = center
	' align="right"',		// 3 right
];
//==============================================================================
class RecordColumns {
	public $Primary;			// Primary-Key Values
	public $PrimaryKey;			// Primary-Key Values
	public $Headers;			//
	private $numbering = false;
	private $selected = false;
	protected $altHead;
//==============================================================================
//	Constructor: Owner
//==============================================================================
	function __construct($model,$sel_no=false,$altHeader=NULL) {
		$this->PrimaryKey = $model->Primary;
		$this->selected = $sel_no;
		$schema = ($altHeader === NULL) ? $model->HeaderSchema:$altHeader;
		$this->SetHeaderList($model->ModuleName,$schema);
	}
//==============================================================================
// extract column name, value set
	protected function SetHeaderList($modelName,$HeaderSchema) {
		$this->Headers = $this->altHead = [];
		foreach($HeaderSchema as $key => $val) {
			list($type,$align,$sort,$wd) = $val;
			$alias = lang_get_module(NULL,"{$modelName}.Schema.{$key}");
			$this->Headers[$key] = [$alias,$align,$sort,$wd];
			$this->altHead[$key] = $type;
		}
	}
//==============================================================================
// extract column name, value set
	public function SetColumns($data) {
		$this->Primary = $data[$this->PrimaryKey];
		foreach($data as $key => $val) {
			$this->$key = $val;
		}
	}
//==============================================================================
// PUT TABLE List Header
	public function putTableHeader($disp_no=false) {
		$this->numbering = $disp_no;
		echo '<tr>';
		if($this->numbering) echo '<th class="sorter-false">No.</th>';
		foreach($this->Headers as $key => $val) {
			list($alias,$align,$sort,$wd) = $val;
			$tsort = ($sort===2) ? '' : ' class="sorter-false"';
			$style = ($wd > 0) ? " style='min-width:{$wd}px;max-width:{$wd}px;'" : '';
			echo "<th${tsort}{$style}>{$alias}</th>";
		}
		echo "</tr>\n";
	}
//==============================================================================
// Put Each record columns
	public function putColumnData($lno) {
		$sel = ($this->selected === $this->Primary) ? ' selected':'';
		echo "<tr class='item{$sel}' id='{$this->Primary}'>";
		if($this->numbering) echo '<td align="right">'.$lno.'</td>';
		foreach($this->Headers as $key => $val) {
			list($alias,$align,$sort,$wd) = $val;
			$pos = TableAlignAttrs[$align];
			$style = ($wd > 0) ? " style='min-width:{$wd}px;max-width:{$wd}px;'" : '';
			$dat = ($this->$key=='') ? '&nbsp;' : nl2br($this->$key);
			echo "<td nowrap{$pos}{$style}>{$dat}</td>";
		}
		echo "</tr>\n";
	}
}
//==============================================================================
class AppHelper  extends AppObject {
//==============================================================================
// Call for Owner(AppView) Template Processing Method
public function ViewTemplate($layout,$vars=[]) {
	$this->AOwner->ViewTemplate($layout,$vars);
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
// Transfer LOCALE varible by $id
public function __x($id) { return $id; }
//==============================================================================
// Make HYPER-Link
public function ALink($lnk,$txt,$attrs = NULL) {
	echo $this->ALink_str($lnk,$txt,$attrs);
}
//==============================================================================
// Make HYPER-Link
public function ALink_str($lnk,$txt,$attrs=false) {
	if(mb_substr($txt,0,1) === '#') {
		$txt = mb_substr($txt,1);
		$txt = $this->_($txt);
	}
	$href = make_hyperlink($lnk,$this->ModuleName);
	if(!empty($href)) $href = " href='{$href}'";
	if(empty($attrs)) $attrs = ['class' => 'nounder'];
	else if(is_string($attrs)) $attrs = ['class' => $attrs];
	else if(!is_array($attrs)) $attrs = [];
	if(get_protocol($href) !== NULL) $attrs['target'] = '_blank';
	$attr = array_items_list($attrs,' ','"');
	return "<a{$href} {$attr}>{$txt}</a>";
}
//==============================================================================
// generate Page Button LABEL Tag
	private function get_PageButton($n, $anchor, $npage) {
		$anchor = substr("00{$anchor}", ($anchor>=100)?-3:-2);
		$cls = (($n == $this->Model->page_num) || ($n == 0) || ($n > $npage)) ? 'active' : 'jump';
		return "<span class='{$cls}' value='{$n}'>{$anchor}</span>";
	}
//==============================================================================
// Page Move up/down link
	private function get_MoveButton($move, $anchor, $npage) {
		$n = $this->Model->page_num + $move;
		$cls = ( ($n <= 0) || ($n > $npage)) ? 'disable' : 'move';
		return "<span class='{$cls}' value='{$n}'>{$anchor}</span>";
	}
//==============================================================================
// Pager Buttons
public function MakePageLinks() {
	if($this->Model->pagesize == 0) return;
	$npage = intval(($this->Model->record_max+$this->Model->pagesize-1)/$this->Model->pagesize);
	$pnum = $this->Model->page_num;
	$bound = 4;
	$begp = max( 2, min( $npage - 1, $pnum + intval($bound/2)) - $bound);
	$endp = min($npage - 1, $begp + $bound);
	echo "<div class='pager'><div class='navigate'>";
	$ptitle = $this->__(".Page", FALSE);
	echo "<span class='pager_title' id='pager_help'>{$ptitle}</span>";
	if(!empty($arg)) {
		echo "<span class='separator'>";
		echo $this->get_MoveButton(-1,$this->__(".PrevPage"),$npage);
		echo "|";
		echo $this->get_MoveButton(1,$this->__(".NextPage"),$npage);
		echo "</span>";
	}
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
	$total = sprintf($fmt,$this->Model->record_max);
	echo "<span class='pager_message'>{$total}</span>";
	echo "</div>\n";
	// Change Page item Count
	$param = (empty(App::$Filter)) ? "1/" : implode('/',App::$Filters)."/1/";
	$href = App::Get_AppRoot($this->ModuleName,TRUE)."/page/{$param}";
	$dsp = "<span id='size_selector'>".$this->__(".Display", FALSE)."</span>";
	echo "<div class='rightalign'>{$dsp}<SELECT id='pagesize'>";
	foreach(array(5,10,15,20,25,50,100) as $val) {
		$sel = ($val == $this->Model->pagesize) ? " selected" : "";
		echo "<OPTION value='{$val}'{$sel}>{$val}</OPTION>\n";
	}
	echo "</SELECT></div>\n";
	echo "</div>";
}
//==============================================================================
// PUT TABLE List Header
	protected function putTableHeader($columns) {
		$columns->putTableHeader(false);
	}
//==============================================================================
// Put Each record columns
	protected function putColumnData($lno,$columns) {
		$columns->putColumnData($lno);
	}
//==============================================================================
// Output Table List for Records
public function MakeListTable($deftab) {
	$sel_no = (empty($this->Model->RecData)) ? false:$this->Model->RecData[$this->Model->Primary];
	// デバッグ情報
	debug_log(DBMSG_VIEW,[
		'deftab' => $deftab,
		'Page' => $this->Model->page_num,
		'Size' => $this->Model->pagesize,
	]);
	if(array_key_exists('pager',$deftab) && $deftab['pager'] == 'true') $this->MakePageLinks();
	if(is_array($deftab)) {
		list($tab,$tbl) = array_keys_value($deftab,['category','tableId']);
	} else {
		$tab = $deftab;
		$tbl = '_TableList';
	}
	$col = new RecordColumns($this->Model,$sel_no);
	echo "<table id='{$tbl}' class='tablesorter {$tab}'>\n<thead>\n";
	$this->putTableHeader($col);
	echo "</thead>\n<tbody>\n";
	$lno = ($this->Model->page_num-1)*$this->Model->pagesize + 1;
	foreach($this->Model->Records as $columns) {
		$col->SetColumns($columns);
		$this->putColumnData($lno++, $col);
	}
	echo "</tbody></table>";
}
//==============================================================================
// Output Table List for ContentsView
public function TableListView($header,$primary,$Records=NULL,$max=0) {
	if($Records===NULL) {
		$Records = $this->Model->Records;
		$max = count($Records);
	}
	$cnt = count($Records);
	$msg = $this->_('core.FIND_RESULT');
	echo "<h3>{$msg} {$cnt}/{$max}</h3>";
	if($max > $cnt) echo $this->_('core.FIND_MANY');
	echo '<div class="result_list_view fitWindow" id="sticky_header">';
	echo "<table id='find_result_table' class='tablesorter'>\n<thead>\n";
	echo '<tr>';
	foreach($header as $key => $val) {
		list($alias,$align,$flag,$wd) = $val;
		$tsort = ($flag==2) ? '' : ' class="sorter-false"';
		$style = ($wd==0) ? '' : " style='width:{$wd}px;max-width:{$wd}px;'";
		echo "<th${tsort}{$style}>{$alias}</th>";
	}
	echo "</tr>\n";
	echo "</thead>\n<tbody>\n";
	foreach($Records as $columns) {
		$id = $columns[$primary];
		echo "<tr class='item' id='{$id}'>";
		foreach($header as $key => $val) {
			list($alias,$align,$flag,$c_wd) = $val;
			$data = $columns[$key];
			$style = ($c_wd > 0) ? " style='width:{$c_wd}px;max-width:{$c_wd}px;overflow:hidden;'":'';
			$pos = TableAlignAttrs[$align];
			echo "<td{$pos}{$style}>{$data}</td>";
		}
		echo "</tr>\n";
	}
	echo "</tbody></table>";
	echo '</div>';
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
	list($selname,$select_one) = (is_array($args)) ? $args : [$args,NULL];
	if(empty($select_one)) $select_one = 'true';
	$object = "{$selname}: {\n\tselect_one: {$select_one},\n\tsel_list: [\n";
   	foreach($this->Model->Select[$selname] as $valset) {
		$new_map = array_map(function($v) {
			return (is_int($v)) ? $v :"'{$v}'";
		},$valset);
		if(count($new_map)===2) $new_map[] = 0;
		$object .= "\t[".implode(',',$new_map) ."],\n";
	}
	$object .= "]},";
	return $object;
}
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
// Create Select Tag strings from ChainLink Array
public function SelectChain($key) {
	$arr = [];
    foreach($this->Model->Select[$key] as $val) $arr[$val[1]] = $val[0];
	return $arr;
}
//==============================================================================
// Generate SELECT Tag & ECHO
public function Select($key,$name) {
	echo $this->Select_str($key,$name);
}
//==============================================================================
// Create Select Tag strings
public function Select_str($key,$name) {
	if(!array_key_exists($key,$this->Model->Select)) return '';
    $dat = intval($this->Model->RecData[$name]);	// NULL is ZERO(0)
    $select = "<SELECT name='{$name}'>";
    foreach($this->Model->Select[$key] as $ttl => $id) {
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
	$items = array_filter($arr,'strlen');	// delete empty item
	$list = (empty($items)) ? '' : "'" . implode("',\n'",$arr) . "'";
	$row = "var {$name} = [\n{$list}\n];";
	return $row;
}
//==============================================================================
// JS array define with KEY OBJECT
public static function define_array_object($arr,$name) {
	$txt = "var {$name} = {\n";
	foreach($arr as $key => $val_arr) {
		$txt = "{$txt}\"{$key}\": ['" . implode("',\n'",$val_arr)."'],\n";
	}
	return "{$txt}};";
}
//==============================================================================
// ドロップダウンメニュー
//	&DropdownMenu(true|false) => [ menu-array ]
function DropdownMenu($arg,$menu) {
	menu_box($menu,string_boolean($arg));
}
//==============================================================================
// ポップアップチェック・ラジオボタンメニュー
//	&PopupCheckBox(name,kind,label,count,plane) => [ menu-array ]
function PopupCheckBox($param,$menu) {
	list($name,$kind,$label,$cnt,$plane) = fix_explode([',',':'],$param,5);
	$kind_arr = ['checkbox'=>true,'radio'=>true];
	if(!array_key_exists($kind,$kind_arr)) $kind = 'checkbox';
	$label = string_boolean($label);
	$plane = string_boolean($plane);
	check_boxmenu($menu,$name,$kind,$label,$cnt,$plane);
}
//==============================================================================
// CSV Output for Model->Records
function CsvResponse($file_name,$csv) {
	$data = pack('C*',0xEF,0xBB,0xBF) . implode("\n",$csv);	// BOM付きUTF-8
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename={$file_name}.csv");
    header('Content-Transfer-Encoding: binary');
	header("Content-Length: ".strlen($data));
	echo $data;
}

}
