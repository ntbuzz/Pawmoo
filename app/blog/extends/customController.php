<?php

class customController  extends AppController {
//==============================================================================
// authorised login mode, if need view LOGIN form, return FALSE
function is_authorised() {
	debug_dump(0, [
		"SESSION" => $_SESSION,
		"ENVDATA" => MySession::$EnvData,
		"POSTENV" => MySession::$PostEnv,
    ]);
    // 現在のログイン情報
    $login = MySession::getLoginInfo();
    // 再ログインのポストが発生しているか
	$newlogin = $this->Login->SetLoginInfo(MySession::$PostEnv);
    if(empty($newlogin)) {      // 再ログインではない
        if(empty($login)) {     // ログイン状態ではない
            // ログインフォームを表示して終了
            $this->View->ViewTemplate('Login');
            return FALSE;
        }
    } else {
		debug_dump(1, [
			'ログイン中' => $login,
			'再ログイン' => $newlogin,
		]);
        MySession::SetLogin($newlogin);
	}
	return TRUE;
}

}
