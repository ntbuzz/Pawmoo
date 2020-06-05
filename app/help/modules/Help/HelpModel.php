<?php

class HelpModel extends customModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DatabaseName' => 'mvcman',
        'DataTable' => 'Part',
        'Primary' => 'id',
        'Unique' => 'id',
        'Schema' => [
            'id'            => ['',0],          // モジュールSchemaの言語ID
            'category_id'   => ['',0],
            'disp_id'       => ['',0],
            'title'         => ['',0],
            'contents'      => ['',0],
        ],
        'Relations' => [
            'category_id' => 'Category.id.title',
        ],
        'PostRenames' => [
        ]
    ];
    public $outline;            // アウトライン配列 $outlien[SECTION-ID][ITEM-ID][PAGE-ID] = CATEGORY-ID
    public $PartSelect;
//==============================================================================
// モジュールクラスではコンストラクタを定義しない
//==============================================================================
//	クラス初期化処理
//  必要ならサブクラスでオーバーライドする
    protected function ClassInit() {
    }
//==============================================================================
// レコードのリレーションからアウトライン配列を作成する
//   outline
//==============================================================================
function MakeOutline() {
    $outline = array();
    $this->PartSelect = [];
    // Part レコードを取得
    $this->RecordFinder([],[],'disp_id');
    foreach($this->Records as $columns) {
        $outline[$columns['id']] = [ $columns['title'],$columns['contents']];
        $this->PartSelect[] = array($columns['id'],$columns['title']);
    };
    // Chapter リストを取得する
    $this->outline = array();
    foreach($outline as $key => $columns) {
        $this->Chapter->RecordFinder(['part_id=' => $key],['id','title','contents'],'disp_id');
        $this->outline[$key] = ['title' => $columns[0], 'contents' => $columns[1],'child' => $this->Chapter->Records];
    }
}
//==============================================================================
// レコードのリレーションからアウトライン配列を作成する
//   
//==============================================================================
function SearchOutline($qstr) {
    // パラグラフを検索
    $this->Paragraph->findKeyword($qstr,['section_id','id','title','contents']);
    // パラグラフをセクションIDでまとめる
    $section = [];
    foreach($this->Paragraph->outline as $key => $val) {
        $pid = $val['section_id']; $id = $val['id'];
        $section[$pid][$id] = $val;
    }
    // セクション情報を補充してチャプターツリーを構成する
    $chapter = [];
    foreach($section as $key => $val) {
        $this->Section->GetRecord($key);
        $pid = $this->Section->RecData['chapter_id'];
        $chapter[$pid][$key] = $this->Section->RecData;
        $chapter[$pid][$key]['paragraph'] = $val;
    }
    // セクション内の検索
    $this->Section->findKeyword($qstr,['chapter_id','id','title','contents']);
    // 見つかった結果をチャプターツリーに統合する
    foreach($this->Section->outline as $val) {
        $pid = $val['chapter_id']; $id = $val['id'];
        // 読み込んでないものだけを追加登録する
        if(!isset($chapter[$pid][$id])) $chapter[$pid][$id] = $val;
    }
    // チャプター情報を補充してアウトラインツリーを構成する
    $outline = [];
    foreach($chapter as $key => $val) {
        $this->Chapter->GetRecord($key);
        $pid = $this->Chapter->RecData['part_id'];
        $outline[$pid][$key] = $this->Chapter->RecData;
        $outline[$pid][$key]['section'] = $val;
    }
    // チャプター内を検索
    $this->Chapter->findKeyword($qstr,['part_id','id','title','contents']);
    // 見つかった結果とパラグラフの結果を結合する
    foreach($this->Chapter->outline as $val) {
        $pid = $val['part_id']; $id = $val['id'];
        // 読み込んでないものだけを追加登録する
        if(!isset($outline[$pid][$id])) $outline[$pid][$id] = $val;
    }
    // パート情報を補充する
    foreach($outline as $key => $val) {
        $this->GetRecord($key);
        $outline[$key] = $this->RecData;
        $outline[$key]['chapter'] = $val;
    }
    // パート内を検索
    $this->findKeyword($qstr,['id','title','contents']);
    // 見つかった結果にチャプターの結果を結合する
    foreach($this->outline as $val) {
        $id = $val['id'];
        // 読み込んでないものだけを追加登録する
        if(!isset($outline[$pid])) $outline[$pid] = $val;
    }
    $this->outline = $outline;
debug_dump(0,["SearchOutline" => $this->outline]);

}

}
