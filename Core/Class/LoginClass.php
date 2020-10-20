<?php
/*
    Loginが必要なアプリケーションで使用する
    データベースの定義は LoginModel に実装する
    データベーステーブルには以下の列が必須
        ユーザーID(任意)    // LoginID プロパティで指定可
        'password'          // パスワード openssl_encrypt() で暗号化したもの
        'language'          // ユーザー言語(ja;en)
*/
abstract class LoginClass extends AppModel {
    static public $LoginUser;
    public $error_type;
//==============================================================================
//　ユーザーIDの妥当性を検証する
public function is_validUser($userid,$passwd = NULL) {
    $this->error_type = "Login NEED A '{$this->LoginID}'";
    if(empty($userid)) return NULL;
    $data = $this->getRecordBy($this->LoginID,$userid);
    if($userid === $data[$this->LoginID]) {
        // $passwd != NULL ならここでパスワードチェックをする
        if($passwd !== NULL) {
            $this->error_type = $this->__('.PassError');
            $user_pass = $data['password'];
            if($passwd !== $user_pass) return NULL;
        }
        static::$LoginUser = $data;
        $lang = (isset($data['language'])) ? $data['language']: DEFAULT_LANG;
        $this->error_type = '';
        return [$userid,$lang];
    }
    $this->error_type = $this->__('.UnknownUser').": '{$userid}'";
    return NULL;
}
//==============================================================================
// Recieved LOGIN POST FORM, do accept USER LOGIN correct
public function is_validLogin($values) {
    $Login = [];
    foreach($values as $key => $val) {
        // FORM POST name, renamed to Database column name
        $xkey = (isset($this->PostRenames[$key])) ? $xkey = $this->PostRenames[$key] : $key;
        if(array_key_exists($xkey,$this->Schema)) {     // pickup exists field name
            list($disp,$flag) = $this->Schema[$xkey];   // need encrypt password
            $dval = ($flag === 1) ? passwd_encrypt($val) : $val;
            $Login[$xkey] = $dval;    // accepta NULL value
        }
    }
    $this->error_type = NULL;
    if(!array_key_exists($this->LoginID,$Login)) return NULL;
    return $this->is_validUser($Login[$this->LoginID],$Login['password']);
}

}
