<?php

class IndexModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'Postgre',
        'DatabaseName' => PG_DB,
        'DataTable' => 'host_lists',
        'Primary' => 'id',
        'Unique' => 'name_list_id',
        'Schema' => [
            'id' =>             ['.id',32],          // モジュールSchemaの言語ID
            'active' =>         ['.active',22],
            'name_list_id' =>   ['.Host',2],    // 共通Schemaの言語ID
            'product_name' =>   ['.product',2],
            'operating_system_id' => ['.os', 2],
            'license_id' =>     ['.license',2],
            'location'=>        ['.location',2],
            'entity'=>          ['.entity',2],
            'service'=>         ['.service',0],
            'subnet' =>         ['.subnet',0],
            'note'=>            ['.note',0]
        ],
        'Relations' => [
            'name_list_id' =>   'name_lists.id.name',
            'license_id' => 'licenses.id.license.operating_system_id',
            'operating_system_id' => 'operating_systems.id.name.osid',
            'provider_id' => 'providers.id.name',
            'desktop_id' => 'desktops.id.name'
        ],
        'PostRenames' => [
        ]
    ];
//==============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//==============================================================================


}
