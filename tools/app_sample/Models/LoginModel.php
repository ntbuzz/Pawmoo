<?php
/* サンプルコードでは使用していない
*/
class LoginModel extends LoginClass {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DataTable' => 'users',
        'Primary' => 'id',
        'LoginID' => 'username',
        'Schema' => [
            'id'        => ['',0],
            'roll'      => ['',0],
            'active'    => ['',0],
            'username'  => ['',0],
            'password'  => ['',0],
            'email'     => ['',0],
            'note'      => ['',0],
            'lastlogin' => ['',0],
            'created'   => ['',0],
        ],
        'PostRenames' => [],
    ];

    public function defaultUser() {
        MySession::$EnvData['Login'] = ['username' => 'admin'];
    }
}
