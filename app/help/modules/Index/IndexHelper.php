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
public function makeOutlineTree() {
dump_debug(0,[ "アウトライン" => $this->MyModel->outline]);
    echo "<ul class='filetree' id='sitemenu'>\n";
    $first=' class="open"';
    foreach($this->MyModel->outline as $key => $chapter) {
        echo "<li${first}><span class='folder'>{$chapter[title]}</span>\n";
        echo "<ul>\n";
        foreach($chapter['child'] as $kk => $val) {
            echo "<li><span class='file'>";
            $this->ALink("view/{$key}/{$val[id]}",$val['title']);
            echo "</span></li>\n";
        }
        echo "</ul>\n";
        echo "</li>\n";
        $first='';//' class="closed"';
    }
    echo "</ul>\n";
}
//===============================================================================
// セクション名をキーにタブ表示する
public function SectionTab() {
    echo "<ul class='tab'>\n";
    if($this->Section !== []) {
        $first = ' class="select"';
        foreach($this->Section as $key => $val) {
            $ttl = ($val['tabset']==='') ? $val['title']:$val['tabset'];
            echo "<li{$first} id='$val[id]'>$ttl</li>\n";
            $first = '';
        }
    } else {
        echo "<li class='new-section' id='$val[id]'>セクション追加</li>\n";
    }
    echo "</ul>\n";
}
//===============================================================================
// セクションのコンテンツをリスト表示する
public function SectionContents() {
    echo "<ul class='content'>\n";
    $first = '';
    foreach($this->Section as $key => $sec) {
        echo "<li{$first} id='{$sec[id]}'>";
        echo "<div class='section' id='$sec[id]'>";
        echo "<h2>$sec[title]</h2>\n";
        if(isset($sec['content'])) {
            echo "<p>$sec[content]</p>\n";
        }
        echo "<hr>\n";
        foreach($sec['本文'] as $val) {
            $id = "{$val[section_id]}:{$val[id]}";
            echo "<div class='paragraph' id='${id}'>";
            if($val['title']) {
                echo "<h3>$val[title]</h3>\n";
            }
            echo "<p class='data'>".$val[$this->_('.Schema.contents')]."</p>";
            echo "</div>\n";
        }
        echo "</div>\n";
        echo "</li>\n";
        $first = ' class="hide"';
    }
    echo "</ul>\n";
}
//===============================================================================
// セレクトリストをjavascript配列へ
public function SelectList($key) {
    foreach($this->MyModel->Select[$key] as $ttl => $id) {
        echo "[{$id},\"{$ttl}\"],";
    }
}

}
