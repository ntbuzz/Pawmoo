<?php

class LoginController extends customController {
	public $defaultAction = 'Login';		//  デフォルトのアクション

//==============================================================================
// ログイン要求を処理する
public function LoginAction() {
	debug_dump(0, [
		"SESSION" => $_SESSION,
		"ENVDATA" => MySession::$EnvData,
		"POSTENV" => MySession::$PostEnv,
	]);
	$login = MySession::getLoginInfo();
	$newlogin = $this->Model->isValidLogin(MySession::$PostEnv);
	if(!empty($newlogin)) {
		debug_dump(1, [
			'ログイン中' => $login,
			'再ログイン' => $newlogin,
		]);
		MySession::SetLogin($newlogin);
//		$url = App::Get_AppRoot();
		$url = App::$SysVAR['URI'];
		header("Location:{$url}");
	}
    if(!empty($login)) {     // ログイン状態ではない
		// 新しいログインでなければセッションに記憶された情報を書き込む
		$newlogin = $this->Model->isValidLogin($login);
	}
	// ログインフォームを表示して終了
	$this->View->ViewTemplate('Login');
}
//==============================================================================
// ログアウト処理
public function LogoutAction() {
	MySession::ClearLogin();
	$url = App::Get_AppRoot();
	header("Location:{$url}");
}

}