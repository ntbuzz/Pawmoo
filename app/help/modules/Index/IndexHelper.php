<?php

class IndexHelper extends AppHelper {
//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
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
            $cls = ($val['id'] == App::$Params[1]) ? ' selected':'';
            echo "<li><span class='file{$cls}'>";
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
        $n = 0;
        foreach($this->Section as $key => $val) {
            $sel = ($this->Tabmenu == $n++) ? ' class="selected"' : '';
            $ttl = ($val['tabset']==='') ? $val['title']:$val['tabset'];
            echo "<li{$sel} id='$val[id]'>$ttl</li>\n";
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
    $n = 0;
    foreach($this->Section as $key => $sec) {
        $sel = ($this->Tabmenu == $n++) ? '' : ' class="hide"';
        echo "<li{$sel} id='{$sec[id]}'>";
        echo "<div class='section' id='$sec[id]'>";
        echo "<h2 class='title'>$sec[title]</h2>\n";
        if(isset($sec['content'])) {
            echo "<div class='description'>$sec[content]</p>\n";
        }
        echo "<hr>\n";
        foreach($sec['本文'] as $val) {
            $id = "{$val[section_id]}-{$val[id]}-{$val[disp_id]}";
            echo "<div class='paragraph' id='${id}'>";
            if($val['title']) {
                echo "<h3 class='caption'>$val[title]</h3>\n";
            }
            echo "<div class='data'>".$val[$this->_('.Schema.contents')]."</div>";
            echo "</div>\n";
        }
        echo "</div>\n";
        echo "</li>\n";
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
