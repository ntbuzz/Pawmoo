<?php

class ChapterModel extends customModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DatabaseName' => 'mvcman',
        'DataTable' => 'Chapter',
        'Primary' => 'id',
        'Unique' => 'id',
        'Schema' => [
            'id'            => ['',2],          // モジュールSchemaの言語ID
            'part_id'       => ['',0],
            'category_id'   => ['',0],
            'disp_id'       => ['',0],
            'title'         => ['',2],
            'contents'      => ['',2],    // 共通Schemaの言語ID
        ],
        'Relations' => [
            'part_id' => 'Part.id.title',
        ],
        'PostRenames' => [
        ]
    ];
//==============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//==============================================================================
// レコード削除、関連するテーブルのレコードも削除
public function deleteRecordset($num) {
    // チャプターを参照しているセクションを削除する
    $this->Section->RecordFinder(['chapter_id=' => $num],['id']);
    $id_list = $this->Section->Records;
    foreach($id_list as $rec) {
        $this->Section->deleteRecordset($rec['id']);
    }
	// チャプタを削除する
	$this->DeleteRecord($num);
}
//==============================================================================
// レコードのリレーションからアウトライン配列を作成し、各々のレコードを取得
//==============================================================================
function ReadOutline($id,$filters) {
    $outline = array();
    // Part レコードを取得
    $this->RecordFinder(['part_id=' => $id],$filters,'disp_id');
    foreach($this->Records as $key => $columns) {
        $chap_id = $columns['id'];
        $outline[$key] = $columns;
        $outline[$key]['child'] = $this->Section->ReadOutline($chap_id,$filters);
    };
    return $outline;
}


}
