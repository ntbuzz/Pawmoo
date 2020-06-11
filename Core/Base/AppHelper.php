<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  AppHelper: ビューのHTML出力を担当するクラス
 * 
 */
//==============================================================================
class AppHelper  extends AppObject {
	const AttrAlign = [
		'',						// 0
		' align="left"',		// 1 = left
		' align="center"',		// 2 = center
		' align="right"',		// 3 right
	];
//==============================================================================
// コンストラクタ：　テーブル名
	function __construct($owner) {
		parent::__construct($owner);
        $this->__InitClass();                       // クラス固有の初期化メソッド
        $this->autoload = TRUE;
	}
//==============================================================================
// 親のビューテンプレートを呼び出す
public function ViewTemplate($layout) {
	$this->AOwner->ViewTemplate($layout);
}
//==============================================================================
// プロパティ変数のセット
public function SetData($data) {
	APPDEBUG::MSG(11, $data);
	foreach($data as $key => $val) {
		$this->$key = $val;
	}
}
//==============================================================================
// リソースの出力
public function Resource($res) {
	APPDEBUG::MSG(11, $res);
	list($filename,$ext) = extract_base_name($res);
	// モジュール名と拡張子を使いテンプレートを決定する
	$AppStyle = new AppStyle($this->ModuleName, $ext);
	// 結合ファイルの出力
	$AppStyle->ViewStyle($filename);
	unset($AppStyle);
}
//==============================================================================
// リクエストコントローラ
public function IsRequestController($comp) {
	$hit = (is_scalar($comp)) ? $comp === App::$Controller : in_array(App::$Controller,$comp);
	echo ($hit) ? '' : ' class="closed"';
}
//==============================================================================
// ハイパーリンクの生成
public function ALink($lnk,$txt,$under=false) {
	if($txt[0] == '#') {							// LocaleIDの参照
		$txt = mb_substr($txt,1);
		$txt = $this->_($txt);
	}
	$href = make_hyperlink($lnk,$this->ModuleName);
	if(get_protocol($href) !== NULL) {
		echo "<a href='{$href}' target=_blank>{$txt}</a>\n";
	} else {
		$uline = ($under) ? '' : ' class="nounder"';
		echo "<a{$uline} href='{$href}'>{$txt}</a>\n";
	}
}
//==============================================================================
// ページリンクのURL生成
	private function get_PageButton($n, $anchor, $npage) {
		$anchor = substr("00{$anchor}", -2);
		$cls = (($n == $this->MyModel->page_num) || ($n == 0) || ($n > $npage)) ? 'active' : 'jump';
		return "<span class='{$cls}' value='{$n}'>{$anchor}</span>";	// ページサイズはセッションに記憶
	}
//==============================================================================
// ページ移動リンクのURL生成
	private function get_MoveButton($move, $anchor, $npage) {
		$n = $this->MyModel->page_num + $move;
		$cls = ( ($n <= 0) || ($n > $npage)) ? 'disable' : 'move';
		return "<span class='{$cls}' value='{$n}'>{$anchor}</span>";	// ページサイズはセッションに記憶
	}
//==============================================================================
// ページリンクの一覧を出力
public function MakePageLinks() {
	if($this->MyModel->pagesize == 0) return;		// ページングを使わない
	$npage = intval(($this->MyModel->record_max+$this->MyModel->pagesize-1)/$this->MyModel->pagesize);
	$pnum = $this->MyModel->page_num;
	$bound = 7;
	$begp = max( 2, min( $npage - 1, $pnum + intval($bound/2)) - $bound);
	$endp = min($npage - 1, $begp + $bound);
	echo "<div class='pager'><div class='navigate'>";
	$ptitle = $this->__(".Page", FALSE);
	echo "<span class='pager_title' id='pager_help'>{$ptitle}:</span>";
	echo "<span class='separator'>";
	echo $this->get_MoveButton(-1,$this->__(".PrevPage"),$npage);
	echo "|";
	echo $this->get_MoveButton(1,$this->__(".NextPage"),$npage);
	echo "</span>";
	if($npage > 1 && $begp > 1) {		// 先頭に飛ぶリンク
		echo $this->get_PageButton(1,1,$npage);
		if($begp > 2) echo '<span class="disable">…</span>';
	}
	for($n=$begp; $n <= $endp; $n++) {		// ページごとのリンクを作る
		echo $this->get_PageButton($n,$n,$npage);
	}
	if($endp < $npage) {		// 最後に飛ぶリンク
		if($endp < ($npage - 1)) echo '<span class="disable">…</span>';
		echo $this->get_PageButton($npage,$npage,$npage);
	}
	$fmt = $this->__(".Total", FALSE);
	$total = sprintf($fmt,$this->MyModel->record_max);
	echo "<span class='pager_message'>{$total}</span>";
	echo "</div>\n";
	// ページサイズの変更
	$param = (App::$Filter==='') ? "1/" : "{App::$Filter}/1/";
	$href = App::Get_AppRoot($this->ModuleName)."/page/{$param}";
//		echo "<div class='rightalign'>表示数:<SELECT id='pagesize' onchange='location.href=\"{$href}\"+this.value;'>";
	$dsp = "<span id='size_selector'>".$this->__(".Display", FALSE)."</span>";
	echo "<div class='rightalign'>{$dsp}:<SELECT id='pagesize'>";
	foreach(array(5,10,15,20,25,50,100) as $val) {
		$sel = ($val == $this->MyModel->pagesize) ? " selected" : "";
		echo "<OPTION value='{$val}'{$sel}>{$val}</OPTION>\n";
	}
	echo "</SELECT></div>\n";
	echo "</div>";	// div.pager enf
//		echo "<hr class='pageborder' />\n";
}
//==============================================================================
// テーブルヘッダを出力
	protected function putTableHeader() {
		// デバッグ情報
		APPDEBUG::DebugDump(11,[
			'Header' => $this->MyModel->Header,
		]);
		echo '<tr>';
		foreach($this->MyModel->Header as $key => $val) {
			list($nm,$flag,$align) = $val;
			if($flag > 0) {
				$tsort = ($flag==2) ? '' : ' class="sorter-false"';
				echo "<th${tsort}>{$nm}</th>";
			}
		}
		echo "</tr>\n";
	}
//==============================================================================
// レコードカラムを出力
	protected function putColumnData($lno,$columns) {
		// デバッグ情報
		APPDEBUG::DebugDump(11,[
			'lno' => $lno,
			'columns' => $columns,
		]);
		echo "<tr class='item' id='".$columns[$this->MyModel->Primary]."'>";
		foreach($this->MyModel->Header as $key => $val) {
			list($nm,$flag,$align) = $val;
			$pos = self::AttrAlign[$align];
			if($flag > 0) echo "<td nowrap{$pos}>". $columns[$nm]."</td>";
		}
		echo "</tr>\n";
	}
//==============================================================================
// ヘッダー付きのテーブルリスト表示
public function MakeListTable($deftab) {
	// デバッグ情報
	APPDEBUG::DebugDump(1,[
		'deftab' => $deftab,
		'Page' => $this->MyModel->page_num,
		'Size' => $this->MyModel->pagesize,
	]);
	if($deftab['pager'] == 'true') $this->MakePageLinks();
	if(is_array($deftab)) {
		$tab = $deftab["category"];
		$tbl = $deftab["tableId"];
	} else {
		$tab = $deftab;
		$tbl = '_TableList';
	}
	echo "<table id='{$tbl}' class='tablesorter {$tab}'>\n<thead>";
	$this->putTableHeader($tab);
	echo "</thead>\n<tbody>\n";
	$lno = ($this->MyModel->page_num-1)*$this->MyModel->pagesize + 1;
	foreach($this->MyModel->Records as $columns) {
		$this->putColumnData($lno++, $columns);
	}
	echo "</tbody></table>";
}
//==============================================================================
// タブセットの生成 (UL版)
public function Tabset($name,$menu,$sel) {
	echo "<ul class='{$name}'>\n";
	$tab = $sel;
	foreach($menu as $key => $val) {
		echo '<li'.(($tab == $val)?' class="selected">':'>') . "{$key}</li>\n";
	}
	echo "</ul>\n";
}
//==============================================================================
// タブリストの生成 (UL版)
public function Contents_Tab($sel,$default='') {
	APPDEBUG::MSG(11, $sel);
	$tab = $default;
	return '<li' . (($tab == $sel) ? '' : ' class="hide"') . ">\n";
}
//==============================================================================
// フォームタグの生成
// attr array
// 'method' => 'Post'
// 'acrion' => URI
// 'id' => identifir
//
public function Form($act, $attr) {
	if ($act[0] !== '/') $act = App::Get_AppRoot($act);
	$arg = '';
	foreach($attr as $key => $val) {
			$arg .= $key .'="' . $val . '"';
}
	echo '<form action="' . $act . '" ' . $arg . '>';
}
//==============================================================================
// SELECTタグの生成
//	$this->Select ($key,$name)
//
public function Select($key,$name) {
	APPDEBUG::MSG(1, $this->MyModel->Select);
	$dat = $this->MyModel->RecData[$key];
	echo "<SELECT name='{$name}'>";
	foreach($this->MyModel->Select[$key] as $ttl => $id) {
		$sel = ($id == $dat) ? " selected" : "";
		echo "<OPTION value='{$id}'{$sel}>{$ttl}</option>\n";
	}
	echo "</SELECT>\n";
}
//==============================================================================
// INPUTタグの生成
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
// webrootファイルの読込
public static function ImageTag($file,$attr) { 
	$path = (($file[0] == '/') ? '/common' : App::$sysRoot) . $file;             // 固有フォルダパス
	echo "<image src='{$path}' {$attr} />\n";
}

}
