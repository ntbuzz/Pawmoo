<?php
//==============================================================================
class HostsModel extends AppModel {
  static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DataTable' => 'host_lists',
        'Primary' => 'id',
        'Unique' => 'id',
        'Schema' => [
            'id' =>                   ['.IP',32],
            'active' =>               ['.Status',22],
            'name_list_id' =>         ['.Host',2],
            'product_name' =>         ['.product',2],
            'operating_system_id' =>  ['.os', 2],
            'license_id' =>           ['.license',2],   //'prodkey',2],
            'location'=>              ['.location',2],  // '所在',2],
            'entity'=>                ['.entity',2],
            'service'=>               ['.service',0],
            'subnet' =>               ['.subnet',0],
            'note'=>                  ['.note',0]
        ],
        'Relations' => [
          'name_list_id' =>   'name_lists.id.name',
          'license_id' => 'licenses.id.license.operating_system_id',
          'operating_system_id' => 'operating_systems.id.name.osid',
          'provider_id' => 'providers.id.name',
          'desktop_id' => 'desktops.id.name'
        ],
        'PostRenames' => [
            'hostname' => 'name_list_id',
            'ipaddr' => 'id',
            'osname' => 'operating_system_id',
            'operating_system' => 'operating_system_id',
            'license' => 'license_id',
            'begDate' => 'setup_date',
            'endDate' => 'setup_date'
            
        ]
    ];
//==============================================================================
// モジュールクラスではコンストラクタを定義しない
//  必要なら ClassInit() メソッドで初期化する
//==============================================================================

}
