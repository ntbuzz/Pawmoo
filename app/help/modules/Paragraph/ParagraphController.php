<?php

class ParagraphController extends cutomController {

//===============================================================================
// 削除アクション以外はカスタムコントローラに置く
public function DeleteAction() {
	$num = App::$Params[0];
	debug_dump(DEBUG_DUMP_NONE, [
		'番号' => $num,
		'POST' => $_REQUEST,
		'データ' => MySession::$PostEnv,
		'タブセット' => MySession::$PostEnv['TabSelect'],
	]);
	$this->Model->DeleteRecord($num);
	echo App::$Referer;
}
//===============================================================================
// クリアアクションはパラグラフのみ
public function ClearAction() {
	$num = App::$Params[0];
	debug_dump(DEBUG_DUMP_NONE, [
		'番号' => $num,
		'POST' => $_REQUEST,
		'データ' => MySession::$PostEnv,
		'タブセット' => MySession::$PostEnv['TabSelect'],
	]);
	// パラブラフ以降のリレーションテーブルは無いので基本メソッドで消せる
	$this->Model->MultiDeleteRecord(['section_id' => $num]);
	echo App::$Referer;
}


}
