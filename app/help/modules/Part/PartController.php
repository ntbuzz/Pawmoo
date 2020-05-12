<?php

class PartController extends cutomController {

//	データの更新のみのコントローラクラスは全て共通なので
//	全てのメソッドはカスタムコントローラーに実装する
//===============================================================================
// 削除アクション以外はカスタムコントローラに置く
public function DeleteAction() {
	$num = App::$Params[0];
	dump_debug(DEBUG_DUMP_NONE, "Update", [
		'番号' => $num,
		'POST' => $_REQUEST,
		'データ' => MySession::$PostEnv,
		'タブセット' => MySession::$PostEnv['TabSelect'],
	]);
	$this->Model->DeleteRecord($num);
	echo App::$Referer;
}

}
