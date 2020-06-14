<?php

class NamesHelper extends AppHelper {
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
    echo "<tr class='item downloadmenu' id='".$columns[$this->MyModel->Primary]."'>";
    echo '<td align="right">'.$lno.'</td>';
    $element = $this->_('.Schema.used');
    $columns[$element] = ($columns[$element] == 'TRUE') ? $this->_('.Common.Busy') : '---';
    foreach($this->MyModel->Header as $key => $val) {
        list($nm,$flag,$align) = $val;
        $pos = parent::AttrAlign[$align];
		if($flag > 0) echo "<td nowrap{$pos}>". $columns[$nm]."</td>";
    }
    echo "</tr>\n";
}
//==============================================================================
// セレクトリストをjavascript配列へ
public function SelectList($key) {
    foreach($this->MyModel->Select[$key] as $ttl => $id) {
        echo "[{$id},\"{$ttl}\"],";
    }
}

}

