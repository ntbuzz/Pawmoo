<?php

class PartModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DatabaseName' => SQLITE_DB,
        'DataTable' => 'Part',
        'Primary' => 'id',
        'Unique' => 'id',
        'Schema' => [
            'id'            => ['',2],          // モジュールSchemaの言語ID
            'category_id'   => ['',0],
            'disp_id'       => ['',0],
            'title'         => ['',2],
            'contents'      => ['',2],    // 共通Schemaの言語ID
        ],
        'Relations' => [
            'category_id' => 'Category.id.title',
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
    // パートを参照しているチャプターを削除する
    $this->Chapter->RecordFinder(['part_id=' => $num],['id']);
    $id_list = $this->Chapter->Records;
    foreach($id_list as $rec) {
        $this->Chapter->deleteRecordset($rec['id']);
    }
	// チャプタを削除する
	$this->DeleteRecord($num);
}


}
