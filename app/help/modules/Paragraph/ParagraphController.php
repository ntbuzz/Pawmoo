<?php

class ParagraphController extends cutomController {

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
