<?php
//==============================================================================
// 
class %model%Model extends AppModel {
  static $DatabaseSchema = [
	'Handler' => '%handler%',
	'DataTable' => %table%,
	%view%
	'Primary' => '%primary%',
	%schema%
  ];
//==============================================================================
//	クラス初期化処理
protected function ClassInit() {

}

}
