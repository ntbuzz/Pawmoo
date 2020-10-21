<?php
class LoginModel extends LoginClass {
    static $DatabaseSchema = [
        'Handler' =>HANDLER,
        'DataTable' => 'users',
        'Primary' => 'id',
        'LoginID' => 'userid',
        'Schema' => [
            'id' =>         ['',0],          // モジュールSchemaの言語ID
            'active' =>     ['',0],
            'userid'=>      ['',0],
            'password'=>    ['',1],
            'email' =>      ['',0],
            'note'=>        ['',0],
            'last_login'=>  ['',0],
            'language'=>    ['',0],
        ],
        'PostRenames' => [],
    ];
//==============================================================================
// 実体はフレームワーク・コアの LoginClass

}
