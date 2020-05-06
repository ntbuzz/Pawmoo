<?php

class NamesModel extends AppModel {
  static $DatabaseSchema = [
        'Handler' => 'Postgre',
        'DatabaseName' => 'pcenv',
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
/*
    .en_US => [
      名前 =>     Name
      ホスト名 =>     Host
      プロダクト名 =>  Product
      OS名 =>       OS
      メーカー => Provider
      ライセンス =>  License
      バージョン =>  Version
      所在 => Location
      実体 =>   Entity
      サービス =>  Service
      SUBNET =>   SUBNET
      備考 =>     Note
  ]
*/
//===============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//===============================================================================


}
