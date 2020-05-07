<?php

class NodeModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DatabaseName' => 'mvcman',
        'DataTable' => 'node',
        'Primary' => 'id',
        'Unique' => 'id',
        'Schema' => [
            'id' =>         ['.id',2],          // モジュールSchemaの言語ID
            'name' =>      ['.name',2],
            'note' =>    ['.note',2],    // 共通Schemaの言語ID
        ],
        'Relations' => [
            'section_id' => 'section.id.title',
        ],
        'PostRenames' => [
        ]
    ];
//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//===============================================================================


}
