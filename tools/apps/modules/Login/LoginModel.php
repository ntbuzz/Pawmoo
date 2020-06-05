<?php

class LoginModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'Postgre',
        'DatabaseName' => 'pcmanager',
        'DataTable' => 'users',
        'Primary' => 'id',
        'Unique' => 'id',
        'Schema' => [
            'id' =>         ['',0],          // モジュールSchemaの言語ID
            'active' =>     ['',0],
            'username'=>    ['',0],
            'password'=>    ['',1],
            'email' =>      ['',0],
            'note'=>        ['',0],
            'last_login'=>  ['',0],
        ],
        'Relations' => [
        ],
        'PostRenames' => [
            'userid' => 'username',
            'passwd' => 'password',
        ]
    ];
//==============================================================================
// ログイン情報を割り当てる
public function SetLoginInfo($values) {
    $this->LoginData = array();
    foreach($values as $key => $val) {
        // POSTされてきた名前を読み替える
        $xkey = (isset($this->PostRenames[$key])) ? $xkey = $this->PostRenames[$key] : $key;
        // フィールドキーが存在するものだけ書き換える
        if(array_key_exists($xkey,$this->Schema)) {
            // 暗号化が必要化確認する
            $vv = $this->Schema[$xkey];
            $dval = ($vv[1]===1) ? openssl_encrypt($val, 'AES-128-CBC', '_minimvc_biscuit') : $val;
            // 復号化するときは
            // 'password' = openssl_decrypt('password', 'AES-128-CBC', '_minimvc_biscuit');
            if($val !== '') $this->LoginData[$xkey] = $dval;    // 値があるものだけ
        }
    }
    // ログイン情報のvalidateを処理する
    // セッションに記憶するログイン情報を返す
    return $this->LoginData;
}

}
