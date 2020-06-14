<?php

class LicensesModel extends AppModel {
  static $DatabaseSchema = [
<<<<<<< HEAD
        'Handler' => 'SQLite',
        'DatabaseName' => 'pcenv',
=======
        'Handler' => 'Postgre',
>>>>>>> origin/master
        'DataTable' => 'licenses',
        'Primary' => 'id',
        'Unique' => 'operating_system_id',
        'Schema' => [
            'id' => ['.id',32],
            'operating_system_id' => ['.os', 2],
            'license' => ['.license',2],
            'arch'=>      ['.arch',2],
            'note'=>      ['.note',1]
        ],
        'Relations' => [
          'operating_system_id' => 'operating_systems.id.name',
        ],
        'PostRenames' => [
        ]
    ];
//==============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//==============================================================================


}
