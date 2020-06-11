<?php

class NamesModel extends AppModel {
  static $DatabaseSchema = [
        'Handler' => 'Postgre',
        'DatabaseName' => PG_DB,
        'DataTable' => 'name_lists',
        'Primary' => 'id',
        'Unique' => 'id',
        'Schema' => [
            'id' =>         ['.id',32],
            'used' =>       ['.used',2],
            'name' =>       ['.name',2],
            'host_id' =>    ['.product',2],
            'description' =>['.description',2],
            'source' =>     ['.source',2],
            'note'=>        ['.note',1]
        ],
        'Relations' => [
          'host_id' =>   'host_lists.id.product_name',
        ],
        'PostRenames' => [
        ]
    ];
//==============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//==============================================================================


}
