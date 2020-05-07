<?php

class ItemModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DatabaseName' => 'mvcman',
        'DataTable' => 'items',
        'Primary' => 'id',
        'Unique' => 'id',
        'Schema' => [
            'id' =>         ['.id',2],          // モジュールSchemaの言語ID
            'name' =>      ['.name',2],
            'note' =>    ['.note',2],    // 共通Schemaの言語ID
        ],
        'Relations' => [
            'node_id' => 'node.id.name',
        ],
        'PostRenames' => [
        ]
    ];
//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//===============================================================================


}
