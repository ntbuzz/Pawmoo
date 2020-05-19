<?php

class AppsModel extends AppModel {
  static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DatabaseName' => 'pcenv',
        'DataTable' => 'app_lists',
        'Primary' => 'id',
        'Unique' => 'name',
        'Schema' => [
          'id'    =>    ['.id', 32],
          'name'    =>    ['.name', 2],
          'version' =>    ['.version', 2],
          'arch'    =>    ['.arch',2],
          'type'    =>    ['.type', 2],
          'category_id' =>['.category', 2],
          'license_key' =>['.license', 1],
          'provider_id' =>['.provider', 2],
          'buy_date'=>    ['.buy_date', 2],
          'note'    =>    ['.note',1]
        ],
        'Relations' => [
          'category_id' => 'categorys.id.name',
          'provider_id' => 'providers.id.name',
        ],
        'PostRenames' => [
        ]
    ];
//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//===============================================================================

}
