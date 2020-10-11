<?php

class IndexModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DataTable' => 'Part',
        'Primary' => 'id',
        'Schema' => [
            'id'            => ['',0],          // モジュールSchemaの言語ID
            'category_id'   => ['',0,0,'Category.id.title'],
            'disp_id'       => ['',0],
            'title'         => ['',0],
            'contents'      => ['',0],
        ],
        'PostRenames' => [],
    ];
//==============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
    protected function ClassInit() {
    }

}
