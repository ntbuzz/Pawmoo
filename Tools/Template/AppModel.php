<?php
//==============================================================================
// 
class %model%Model extends AppModel2 {
static $DatabaseSchema = [
%databasedefs%
	];
//==============================================================================
//	クラス初期化処理
protected function ClassInit() {
}
%virtual_class%

}
