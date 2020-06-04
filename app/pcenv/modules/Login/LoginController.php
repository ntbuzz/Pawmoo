<?php

class LoginController extends AppController {
	public $defaultAction = 'Login';		//  デフォルトのアクション

//===============================================================================
// ログイン要求が必要ならTRUEを返す
public function LoginAction() {
	debug_dump(0, [
		"SESSION" => $_SESSION,
		"ENVDATA" => MySession::$EnvData,
		"POSTENV" => MySession::$PostEnv,
	]);
	$login = MySession::getLoginInfo();
	$newlogin = $this->Model->SetLoginInfo(MySession::$PostEnv);
	if(!empty($newlogin)) {
		debug_dump(1, [
			'ログイン中' => $login,
			'再ログイン' => $newlogin,
		]);
		MySession::SetLogin($newlogin);
//		$url = App::getAppRoot();
		$url = App::$SysVAR['URI'];
		header("Location:{$url}");
	}
    if(!empty($login)) {     // ログイン状態ではない
		// 新しいログインでなければセッションに記憶された情報を書き込む
		$newlogin = $this->Model->SetLoginInfo($login);
	}
	// ログインフォームを表示して終了
	$this->View->ViewTemplate('Login');
}
//===============================================================================
// ログイン要求が必要ならTRUEを返す
public function LogoutAction() {
	MySession::ClearLogin();
	$url = App::getAppRoot();
	header("Location:{$url}");
}

}