<?php

class OsModel extends AppModel {
  static $DatabaseSchema = [
        'Handler' => 'Postgre',
        'DataTable' => 'operating_systems',
        'Primary' => 'id',
        'Unique' => 'name',
        'Schema' => [
            'id' =>       ['.id',32],
            'osid' =>     ['.osid',22],
            'name' =>     ['.name',2],
            'version' =>  ['.version',2],
            'provider_id' => ['.provider',2],
            'expired' =>  ['.expired',32],
            'note'=>      ['.note',1]
        ],
        'Relations' => [
          'provider_id' => 'providers.id.name',
        ],
        'PostRenames' => [
        ]
    ];


}
