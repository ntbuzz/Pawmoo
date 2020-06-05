<?php

class HostsHelper extends AppHelper {

//==============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//==============================================================================

//==============================================================================
// ヘッダー付きのテーブルリスト表示
//==============================================================================
// テーブルヘッダ出力をオーバーライド
protected function putTableHeader($tab = '') {
    print '<tr><th class="sorter-false">No.</th>';
	foreach($this->MyModel->Header as $key => $val) {
        list($nm,$flag,$align) = $val;
        if($flag > 0) {
	        $tsort = ($flag==2) ? '' : ' class="sorter-false"';
			print "<th${tsort}>{$nm}</th>";
        }
    }
    print "</tr>\n";
}
//==============================================================================
// レコードカラム出力をオーバーライド
protected function putColumnData($lno,$columns) {
    $element = $this->_('.Schema.Status');
    $columns[$element] = ($columns[$element] == 't') ? $this->_('.Busy') : '---';
    
    print "<tr class='item' id='".$columns[$this->MyModel->Primary]."'>";
    print '<td align="right">'.$lno.'</td>';
	foreach($this->MyModel->Header as $key => $val) {
        list($nm,$flag,$align) = $val;
        $pos = parent::AttrAlign[$align];
		if($flag > 0) print "<td nowrap{$pos}>". $columns[$nm]."</td>";
	}
	print "</tr>\n";
}
//==============================================================================
// ページャーボタンの表示
public function makePageLinks() {
    if($this->MyModel->pagesize == 0) {
        echo "<span>＜全 {$this->MyModel->record_max}件＞</span>";
    } else
        parent::makePageLinks();
}
//==============================================================================
// セレクトリストをjavascript配列へ
public function SelectList($key) {
    foreach($this->MyModel->Select[$key] as $ttl => $id) {
        echo "[{$id},\"{$ttl}\"],";
    }
}

}
