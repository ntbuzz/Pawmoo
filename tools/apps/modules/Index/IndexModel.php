<?php

class IndexModel extends AppModel {
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
//==============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
    protected function ClassInit() {
    }

}
