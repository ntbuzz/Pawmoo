<?php

class SectionModel extends customModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DatabaseName' => 'mvcman',
        'DataTable' => 'Section',
        'Primary' => 'id',
        'Unique' => 'id',
        'Schema' => [
            'id'            => ['',2],          // モジュールSchemaの言語ID
            'chapter_id'    => ['',0],
            'category_id'   => ['',0],
            'disp_id'       => ['',0],
            'title'         => ['',2],
            'short_title'   => ['.tabset',2],
            'contents'      => ['',2],    // 共通Schemaの言語ID
        ],
        'Relations' => [
            'chapter_id' => 'Chapter.id.title',
            'category_id' => 'Category.id.title',
        ],
        'PostRenames' => [
        ]
    ];
//==============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//==============================================================================
public function getSectionDoc($Chap) {
    $this->RecordFinder(['chapter_id=' => $Chap],[],'disp_id');
    foreach($this->Records as $key => $sec) {
        $this->Paragraph->RecordFinder(['section_id=' => $sec['id'] ],[],'disp_id');
        $this->Records[$key]['本文'] = $this->Paragraph->Records;
    }
}
//==============================================================================
// レコード削除、関連するテーブルのレコードも削除
public function deleteRecordset($num) {
    // セクションを参照しているパラグラフを削除する
    // パラブラフ以降のリレーションテーブルは無いので基本メソッドで消せる
	$this->Paragraph->MultiDeleteRecord(['section_id' => $num]);
	// セクションを削除する
	$this->DeleteRecord($num);
}
//==============================================================================
// レコードのリレーションからアウトライン配列を作成し、各々のレコードを取得
//==============================================================================
function ReadOutline($id,$filters) {
    $outline = array();
    // Part レコードを取得
    $this->RecordFinder(['chapter_id=' => $id],$filters,'disp_id');
    foreach($this->Records as $key => $columns) {
        $sec_id = $columns['id'];
        $outline[$key] = $columns;
        $outline[$key]['child'] = $this->Paragraph->ReadOutline($sec_id,$filters);
    };
    return $outline;
}

}
