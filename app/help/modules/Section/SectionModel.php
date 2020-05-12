<?php

class SectionModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DatabaseName' => 'mvcman',
        'DataTable' => 'Section',
        'Primary' => 'id',
        'Unique' => 'id',
        'Schema' => [
            'id'            => ['.id',2],          // モジュールSchemaの言語ID
            'chapter_id'    => ['',0],
            'category_id'   => ['',0],
            'disp_id'      => ['',0],
            'title'         => ['.title',2],
            'short_title'   => ['.tabset',2],
            'contents'      => ['.content',2],    // 共通Schemaの言語ID
        ],
        'Relations' => [
            'chapter_id' => 'Chapter.id.title',
            'category_id' => 'Category.id.title',
        ],
        'PostRenames' => [
        ]
    ];
//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//===============================================================================
public function getSectionDoc($Chap) {
    $this->RecordFinder(['chapter_id' => $Chap],[],'disp_id');
    foreach($this->Records as $key => $sec) {
        $this->Paragraph->RecordFinder(['section_id' => $sec['id'] ],[],'disp_id');
        $this->Records[$key]['本文'] = $this->Paragraph->Records;
    }
}
//===============================================================================
// レコード削除、関連するテーブルのレコードも削除
public function deleteRecordset($num) {
	// パラグラフを削除する
	$this->Paragraph->MultiDeleteRecord(['section_id' => $num]);
	// セクションを削除する
	$this->DeleteRecord($num);
}

}
