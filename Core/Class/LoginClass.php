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
    $this->error_type = '';
    if(empty($userid)) return NULL;
    $data = $this->getRecordBy($this->LoginID,$userid);
    if($userid === $data[$this->LoginID]) {
        // $passwd != NULL ならここでパスワードチェックをする
        if($passwd !== NULL) {
            $this->error_type = 'password missing';
            $user_pass = $data['password'];
            if($passwd !== $user_pass) return NULL;
        }
        static::$LoginUser = $data;
        $lang = (isset($data['language'])) ? $data['language']: DEFAULT_LANG;
        $this->error_type = '';
        return [$userid,$lang];
    }
    $this->error_type = "unknown user:{$userid}";
    return NULL;
}
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
            list($disp,$flag) = $this->Schema[$xkey];
            $dval = ($flag === 1) ? passwd_encrypt($val) : $val;
            if(!empty($val)) $Login[$xkey] = $dval;    // 値があるものだけ
        }
    }
    $this->error_type = '';
    if(!isset($Login[$this->LoginID])) return NULL;
    return $this->is_validUser($Login[$this->LoginID],$Login['password']);
}

}
