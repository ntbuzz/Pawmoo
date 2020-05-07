<?php

class CategoryModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DatabaseName' => 'mvcman',
        'DataTable' => 'category',
        'Primary' => 'id',
        'Unique' => 'id',
        'Schema' => [
            'id' =>         ['.id',2],          // モジュールSchemaの言語ID
            'title' =>      ['.title',2],
            'note' =>    ['.note',2],    // 共通Schemaの言語ID
        ],
        'Relations' => [
        ],
        'PostRenames' => [
        ]
    ];
//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//===============================================================================


}
