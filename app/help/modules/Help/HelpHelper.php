<?php

class HelpHelper extends AppHelper {
//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//===============================================================================
// ヘッダー付きのテーブルリスト表示
public function makeOutlineTree() {
    debug_dump(0,[ "アウトライン" => $this->MyModel->outline]);
    echo "<ul class='filetree' id='sitemenu'>\n";
    $first=' class="open"';
    foreach($this->MyModel->outline as $key => $chapter) {
        echo "<li${first}><span class='folder'>{$chapter[title]}</span>\n";
        echo "<ul>\n";
        foreach($chapter['child'] as $kk => $val) {
            $cls = ($val['id'] == App::$Params[1]) ? ' selected':'';
            echo "<li><span class='file{$cls}'>";
            $this->ALink("index/view/{$key}/{$val[id]}",$val['title']);
            echo "</span></li>\n";
        }
        echo "</ul>\n";
        echo "</li>\n";
        $first='';//' class="closed"';
    }
    echo "</ul>\n";
}
//===============================================================================
// ヘッダー付きのテーブルリスト表示
public function DocIndex() {
    debug_dump(0,[ "アウトライン" => $this->MyModel->outline]);
    echo "<h1>■　目次</h1>\n<hr>\n";
    echo "<DL class='index'>\n";
    foreach($this->MyModel->outline as $key => $chapter) {
        echo "<DT>■ {$chapter[title]}</DT>\n";
        $contents = nl2br($chapter['contents']);
        echo "<DD>{$contents}\n";
        echo "<ol>\n";
        foreach($chapter['child'] as $kk => $val) {
            echo "<li>";
            $this->ALink("index/view/{$key}/{$val[id]}",$val['title']);
            $contents = nl2br($val['contents']);
            echo "<p>{$contents}</p>\n";
            echo "</li>";
        }
        echo "</ol></DD>\n";
    }
    echo "</DL>\n";
}

}
