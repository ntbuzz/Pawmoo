<?php
/* -------------------------------------------------------------
 * Biscuitフレームワーク
 *  AppHelper: ビューのHTML出力を担当するクラス
 * 
 */
require_once('../vendor/PHPExcel/PHPExcel.php');
require_once("../vendor/PHPExcel/PHPExcel/IOFactory.php");
require_once("../vendor/mpdf50/mpdf.php");

//===============================================================================
class AppHelper  extends AppObject {
	// プロパティリスト
    const MyPropList = [
		'Excel' => 'PHPExcel',
		'PDF' => 'mPDF'
	];
	const AttrAlign = [
		'',						// 0
		' align="left"',		// 1 = left
		' align="center"',		// 2 = center
		' align="right"',		// 3 right
	];
/*==================================================================================================
	コンストラクタ：　テーブル名
  ================================================================================================== */
	function __construct($owner) {
		parent::__construct($owner);
        $this->__InitClass();                       // クラス固有の初期化メソッド
	}
//==================================================================================================
// Excel/PDFの動的クラスプロパティを生成
// 固有クラスのため AppObject のメソッドは使用しない
	public function __get($PropName) {
		APPDEBUG::MSG(10, $PropName . " を動的生成します。");
		if(isset($this->$PropName)) return $this->$PropName;
		// Model or View or Helper or Controller を付加する
		$props = self::MyPropList[$PropName];
		if(class_exists($props)) {
			$this->$PropName = new $props();
			return $this->$PropName;
		}
		throw new Exception("Dynamic Subclass Create Error for '{$props}'");
	}
//===============================================================================
// パラメータ指定の有無を判定
	public function getParams($n, $alt) {
		return (isset(App::$Params[$n])) ? App::$Params[$n] : $alt;
	}
//===============================================================================
// パラメータ指定の有無を判定
public function getFilter($alt = '') {
	return (isset(App::$Filter)) ? App::$Filter : $alt;
}
//===============================================================================
// 親のビューテンプレートを呼び出す
	public function ViewTemplate($layout) {
		APPDEBUG::MSG(12, $layout,"HelperView");
		$this->AOwner->ViewTemplate($layout);
	}
//===============================================================================
// プロパティ変数のセット
    public function SetData($data) {
		APPDEBUG::MSG(12, $data);
        foreach($data as $key => $val) {
            $this->$key = $val;
		}
    }
//===============================================================================
// リソースの出力
	public function Resource($res) {
		APPDEBUG::MSG(12, $res);
		list($filename,$ext) = extractBaseName($res);
		// モジュール名と拡張子を使いテンプレートを決定する
		$AppStyle = new AppStyle($this->ModuleName, $ext);
		// 結合ファイルの出力
		$AppStyle->ViewStyle($filename);
		unset($AppStyle);
	}
//===============================================================================
// リクエストコントローラ
    public function IsRequestClass($comp) {
		$hit = (is_scalar($comp)) ? $comp == App::$ActionClass : in_array(App::$ActionClass,$comp);
		echo ($hit) ? '' : ' class="closed"';
    }
//===============================================================================
// ハイパーリンクの生成
    public function ALink($lnk,$txt,$under=false) {
		$http = array('http:/','https:');
		if($txt[0] == '#') {							// LocaleIDの参照
			$txt = mb_substr($txt,1);
			$txt = $this->_($txt);
		}
		if(in_array(mb_substr($lnk,0,6),$http)) {
			echo "<a href='{$lnk}' target=_blank>{$txt}</a>\n";
		} else {
			if($lnk[0] == ':') {							// LocaleIDの参照
				$lnk[0] = '/';
				$href = $lnk;
			} else {
				$href = ($lnk[0] == '/') ? $lnk : strtolower($this->ModuleName) . '/' . $lnk;
				$href = App::getRoot($href);
			}
			$uline = ($under) ? '' : ' class="nounder"';
			echo "<a{$uline} href='{$href}'>{$txt}</a>\n";
		}
    }
//===============================================================================
// リレーションによる読替え
    protected function getRealValue($key,$val) {
		return $val;
    }
//===============================================================================
// ページリンクのURL生成
	private function echo_pagelink($n, $anchor, $npage) {
		$anchor = substr("00{$anchor}", -2);
		$cls = (($n == $this->MyModel->page_num) || ($n == 0) || ($n > $npage)) ? 'active' : 'jump';
		return "<span class='{$cls}' value='{$n}'>{$anchor}</span>";	// ページサイズはセッションに記憶
	}
//===============================================================================
// ページ移動リンクのURL生成
	private function echo_movelink($move, $anchor, $npage) {
		$n = $this->MyModel->page_num + $move;
		$cls = ( ($n <= 0) || ($n > $npage)) ? 'disable' : 'move';
		return "<span class='{$cls}' value='{$n}'>{$anchor}</span>";	// ページサイズはセッションに記憶
	}
//===============================================================================
// ページリンクの一覧を出力
	public function makePageLinks() {
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
		echo $this->echo_movelink(-1,$this->__(".PrevPage"),$npage);
		echo "|";
		echo $this->echo_movelink(1,$this->__(".NextPage"),$npage);
		echo "</span>";
		if($npage > 1 && $begp > 1) {		// 先頭に飛ぶリンク
			echo $this->echo_pagelink(1,1,$npage);
			if($begp > 2) echo '<span class="disable">…</span>';
		}
		for($n=$begp; $n <= $endp; $n++) {		// ページごとのリンクを作る
			echo $this->echo_pagelink($n,$n,$npage);
		}
		if($endp < $npage) {		// 最後に飛ぶリンク
			if($endp < ($npage - 1)) echo '<span class="disable">…</span>';
			echo $this->echo_pagelink($npage,$npage,$npage);
		}
		$fmt = $this->__(".Total", FALSE);
		$total = sprintf($fmt,$this->MyModel->record_max);
		echo "<span class='pager_message'>{$total}</span>";
		echo "</div>\n";
		// ページサイズの変更
		$param = (App::$Filter==='') ? "1/" : "{App::$Filter}/1/";
		$href = App::getRoot($this->ModuleName)."/page/{$param}";
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
//===============================================================================
// テーブルヘッダを出力
protected function putTableHeader() {
//    print '<tr><th class="sorter-false">No.</th>';
	print '<tr>';
	foreach($this->MyModel->Header as $key => $val) {
		list($nm,$flag,$align) = $val;
		if($flag > 0) {
			$tsort = ($flag==2) ? '' : ' class="sorter-false"';
			print "<th${tsort}>{$nm}</th>";
		}
	}
	print "</tr>\n";
}
//===============================================================================
// レコードカラムを出力
protected function putColumnData($lno,$columns) {
	print "<tr class='item' id='".$columns[$this->MyModel->Primary]."'>";
//			print '<td align="right">'.$lno++.'</td>';
	foreach($this->MyModel->Header as $key => $val) {
		list($nm,$flag,$align) = $val;
		$pos = self::AttrAlign[$align];
		if($flag > 0) print "<td nowrap{$pos}>". $columns[$nm]."</td>";
	}
	print "</tr>\n";
}
//===============================================================================
// ヘッダー付きのテーブルリスト表示
    public function makeListTable($deftab) {
		// デバッグ情報
		APPDEBUG::arraydump(2,[
			'deftab' => $deftab,
			'Page' => $this->MyModel->page_num,
			'Size' => $this->MyModel->pagesize,
		]);
		if($deftab['pager'] == 'true') $this->makePageLinks();
		if(is_array($deftab)) {
			$tab = $deftab["category"];
			$tbl = $deftab["tableId"];
		} else {
			$tab = $deftab;
			$tbl = '_TableList';
		}
		print "<table id='{$tbl}' class='tablesorter {$tab}'>\n<thead>";
		$this->putTableHeader($tab);
		print "</thead>\n<tbody>\n";
		$lno = ($this->MyModel->page_num-1)*$this->MyModel->pagesize + 1;
        foreach($this->MyModel->Records as $columns) {
			$this->putColumnData($lno++, $columns);
		}
		print "</tbody></table>";
    }
//===============================================================================
// タブセットの生成 (UL版)
    public function Tabset($name,$menu,$sel) {
		echo "<ul class='{$name}'>\n";
		$tab = $sel;	// $this->getFilter($sel);
		foreach($menu as $key => $val) {
			echo '<li'.(($tab == $val)?' class="select">':'>') . "{$key}</li>\n";
		}
		echo "</ul>\n";
	}
//===============================================================================
// タブリストの生成 (UL版)
    public function TabContents($sel,$default='') {
		APPDEBUG::MSG(12, $sel);
		$tab = $default; 	// $this->getParams(0,$default);
		return '<li' . (($tab == $sel) ? '' : ' class="hide"') . ">\n";
	}
//===============================================================================
// フォームタグの生成
// attr array
// 'method' => 'Post'
// 'acrion' => URI
// 'id' => identifir
//
    public function Form($act, $attr) {
        APPDEBUG::MSG(12, $this);
		if ($act[0] !== '/') $act = App::getRoot($act);
		$arg = '';
		foreach($attr as $key => $val) {
				$arg .= $key .'="' . $val . '"';
	}
		echo '<form action="' . $act . '" ' . $arg . '>';
    }
//===============================================================================
// SELECTタグの生成
//	$this->Select ($key,$name)
//
    public function Select($key,$name) {
		APPDEBUG::MSG(2, $this->MyModel->Select);
		$dat = $this->MyModel->RecData[$key];
		echo "<SELECT name='{$name}'>";
		foreach($this->MyModel->Select[$key] as $ttl => $id) {
			$sel = ($id == $dat) ? " selected" : "";
			echo "<OPTION value='{$id}'{$sel}>{$ttl}</option>\n";
		}
		echo "</SELECT>\n";
    }
//===============================================================================
// INPUTタグの生成
//	$this->Input($type,$name,$attr)
 //<input type="text" id="datepicker1" name="begDate"> ～ <input type="text" id="datepicker2" name="endDate">
 //
    public function Input($type,$name,$attr) {
		APPDEBUG::MSG(12, $this);
		$tag = "<input type='{$type}' name='{$name}' ";
		foreach($attr as $key => $val) {
				$tag .= $key .'="' . $val . '" ';
		}
		$tag .= '>';
		echo $tag;
	}
//==================================================================================================
// webrootファイルの読込
	public static function ImageTag($file,$attr) { 
    	$path = (($file[0] == '/') ? '/common' : App::$sysRoot) . $file;             // 固有フォルダパス
        echo "<image src='{$path}' {$attr} />\n";
	}

}
