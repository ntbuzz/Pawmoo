<?php

class IndexHelper extends AppHelper {
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
    if($this->_in($columns,'Schema.Host')==='') return;
    $element = $this->_('.Schema.active');
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
// セレクトリストをjavascript配列へ
public function SelectList($key) {
    foreach($this->MyModel->Select[$key] as $ttl => $id) {
        echo "[{$id},\"{$ttl}\"],";
    }
}
//==============================================================================
// BINDのゾーンファイルを生成する
    public function MakeZoneFile() {
        $isnum = (App::$Filter == 'num') ? TRUE: FALSE;
        $dhcp = App::$Params[0];
        $count = App::$Params[1];
        echo "<pre>\n";
        $zone = "starship.zone\n";
        $rev = "starship.rev\n";
        $lno = 1;
        foreach($this->MyModel->Records as $columns) {
//var_dump($columns);
            $hostname = strtolower($this->_in($columns,".Schema.Host"));
            $fixname = str_fixwidth($hostname,' ',16);// substr($hostname.str_repeat (' ',16),0,16);
            $ipaddr = $this->_in($columns,".Schema.id");
            $status = $this->_in($columns,".Schema.active");
            $ln = ($isnum) ? str_fixwidth($lno++,' ',-3)."\t" : "";
            if($status != 't') $ln .= ";";
            if($ipaddr < 255) {
                $zone .= "{$ln}{$fixname}IN\tA\t192.168.111.{$ipaddr}\n";
                $rev .= "{$ln}{$ipaddr}\tIN\tPTR\t{$hostname}.starship.net.\n";
            }
        }
        // DHCP エントリ生成
        while($count-- > 0) {
            $ln = ($isnum) ? str_fixwidth($lno++,' ',-3)."\t" : "";
            $hostname = "dhcp{$dhcp}";
            $ipaddr = $dhcp++;
            $fixname = str_fixwidth($hostname,' ',16);
            $zone .= "{$ln}{$fixname}IN\tA\t192.168.111.{$ipaddr}\n";
            $rev .= "{$ln}{$ipaddr}\tIN\tPTR\t{$hostname}.starship.net.\n";

        }
        echo "{$zone}\n\n{$rev}\n";
        echo "</pre>\n";
    }

}
