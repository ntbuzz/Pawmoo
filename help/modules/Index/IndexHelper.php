<?php

class IndexHelper extends AppHelper {
//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//===============================================================================
//===============================================================================
public function echo_dump($record) {
    echo "<pre>\n";
    echo "********************* HELPER DUMP ********************************\n";
    var_dump($record);
    echo "</pre>\n";
}

//===============================================================================
// ヘッダー付きのテーブルリスト表示
public function makeListTable($deftab) {
    APPDEBUG::MSG(2, $deftab);
    $header = $this->MyModel->Header;    // $ViewData['Header'];
    $records = $this->MyModel->Records;    // $ViewData['Header'];
    APPDEBUG::MSG(21, $records);
    print "<table id='myTable' class='tablesorter'>\n";
    print '<thead><tr><th class="sorter-false">No.</th>';
    foreach($header as $key => $val) {
        list($nm,$flag) = $val;
        if($flag > 0) {
	        $tsort = ($flag==2) ? '' : ' class="sorter-false"';
			print "<th${tsort} nowrap>{$nm}</th>";
        }
    }
    print "</tr></thead>\n";
    print "<tbody>\n";
    $lno = 1;
    foreach($records as $columns) {
        if($this->_in($columns,'Schema.Host')==='') continue;
        $element = $this->_('.Schema.active');
        $columns[$element] = ($columns[$element] === 't') ? $this->_('.Busy') : '---';
//        if($columns['ホスト名']=='') continue;
//        $columns['状態'] = ($columns['状態']==='t') ? '使用中' : '---';
        print "<tr class='item downloadmenu' id='".$columns[$this->MyModel->Primary]."'>";
        print '<td align="right">'.$lno++.'</td>';
        foreach($header as $key => $val) {
            list($nm,$flag) = $val;
            if($flag > 0) print "<td nowrap>". $columns[$nm]."</td>";
        }
        print "</tr>\n";
    }
    print "</tbody></table>";
}
//===============================================================================
// セレクトリストをjavascript配列へ
public function SelectList($key) {
    foreach($this->MyModel->Select[$key] as $ttl => $id) {
        echo "[{$id},\"{$ttl}\"],";
    }
}

}
