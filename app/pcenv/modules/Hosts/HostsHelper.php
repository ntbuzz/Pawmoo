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
    echo '<tr><th class="sorter-false">No.</th>';
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
// レコードカラム出力をオーバーライド
protected function putColumnData($lno,$columns) {
    $element = $this->_('.Schema.Status');
    $columns[$element] = ($columns[$element] == 't') ? $this->_('.Busy') : '---';
    
    echo "<tr class='item' id='".$columns[$this->MyModel->Primary]."'>";
    echo '<td align="right">'.$lno.'</td>';
	foreach($this->MyModel->Header as $key => $val) {
        list($nm,$flag,$align) = $val;
        $pos = parent::AttrAlign[$align];
		if($flag > 0) echo "<td nowrap{$pos}>". $columns[$nm]."</td>";
	}
	echo "</tr>\n";
}
//==============================================================================
// ページャーボタンの表示
public function MakePageLinks() {
    if($this->MyModel->pagesize == 0) {
        echo "<span>＜全 {$this->MyModel->record_max}件＞</span>";
    } else
        parent::MakePageLinks();
}
//==============================================================================
// セレクトリストをjavascript配列へ
public function SelectList($key) {
    foreach($this->MyModel->Select[$key] as $ttl => $id) {
        echo "[{$id},\"{$ttl}\"],";
    }
}

}
