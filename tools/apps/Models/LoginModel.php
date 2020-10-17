<?php
class LoginModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' =>HANDLER,
        'DataTable' => 'users',
        'Primary' => 'id',
        'Schema' => [
            'id' =>         ['',0],          // モジュールSchemaの言語ID
            'active' =>     ['',0],
            'username'=>    ['',0],
            'password'=>    ['',1],
            'email' =>      ['',0],
            'note'=>        ['',0],
            'last_login'=>  ['',0],
        ],
        'PostRenames' => [
            'userid' => 'username',
            'passwd' => 'password',
        ]
    ];
//==============================================================================
// ログイン情報のPOSTを受け取ってログイン処理をおこなう
public function is_validLogin($values) {
    $Login = [];
    foreach($values as $key => $val) {
        // POSTされてきた名前を読み替える
        $xkey = (isset($this->PostRenames[$key])) ? $xkey = $this->PostRenames[$key] : $key;
        // フィールドキーが存在するものだけ書き換える
        if(array_key_exists($xkey,$this->Schema)) {
            // 暗号化が必要化確認する
            $vv = $this->Schema[$xkey];
            $dval = ($vv[1]===1) ? passwd_encrypt($val) : $val;
            if(!empty($val)) $Login[$xkey] = $dval;    // 値があるものだけ
        }
    }
    $userid = $Login['username'];
    $data = $this->is_validUser($userid);
    if($data !== NULL) {
        $lang = $data['language'];
        MySession::set_LoginValue(['username'=>$userid,'LANG'=>$lang]);
    }
    return $data;
}
//==============================================================================
// ログイン情報のPOSTを受け取ってログイン処理をおこなう
public function is_validUser($userid) {
    if(empty($userid)) return NULL;
    $data = $this->getRecordBy('username',$userid);
    if($userid === $data['username']) {
        static::$LoginUser = $data;
        return $data;
    }
    return NULL;
}

}
