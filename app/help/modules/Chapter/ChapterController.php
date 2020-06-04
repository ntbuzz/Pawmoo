<?php

class ChapterController extends cutomController {

//	データの更新のみのコントローラクラスは全て共通なので
//	全てのメソッドはカスタムコントローラーに実装する
//
//===============================================================================
// データを更新してREFERERに戻す
public function DeleteAction() {
	$num = App::$Params[0];
	$this->Model->deleteRecordset($num);
	echo App::$Referer;
}

}
