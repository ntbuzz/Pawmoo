<?php

class SectionModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DatabaseName' => 'mvcman',
        'DataTable' => 'section',
        'Primary' => 'id',
        'Unique' => 'id',
        'Schema' => [
            'id' =>         ['.id',2],          // モジュールSchemaの言語ID
            'section' =>      ['.section',2],
            'note' =>    ['.note',2],    // 共通Schemaの言語ID
        ],
        'Relations' => [
            'category_id' => 'category.id.title',
        ],
        'PostRenames' => [
        ]
    ];
//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//===============================================================================


}
