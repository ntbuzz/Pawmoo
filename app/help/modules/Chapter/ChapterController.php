<?php

class ChapterController extends cutomController {

//	データの更新のみのコントローラクラスは全て共通なので
//	全てのメソッドはカスタムコントローラーに実装する
//
//===============================================================================
// データを更新してREFERERに戻す
public function DeleteAction() {
	$num = App::$Params[0];
	debug_dump(DEBUG_DUMP_NONE, [
		'番号' => $num,
		'POST' => $_REQUEST,
		'データ' => MySession::$PostEnv,
		'タブセット' => MySession::$PostEnv['TabSelect'],
	]);
	$this->Model->deleteRecordset($num);
	echo App::$Referer;
}

}
