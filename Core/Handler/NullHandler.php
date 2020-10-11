<?php
//==============================================================================
//	データベースに接続しないNULLハンドラー

class NullHandler {
//==============================================================================
//	コンストラクタ：　データベースのテーブルに接続する
	function __construct($table) {
		$this->fields = [];
		$this->fieldAlias = new fieldAlias();
	}
public function SetPaging($pagesize, $pagenum) { }
public function getValueLists($table,$ref,$id) { return []; }
public function getLastError() { return ''; }

public function getRecordValue($row,$relations) { return []; }
public function doQueryBy($key,$val) { return []; }
public function doQuery($sql) { return NULL; }
public function findRecord($row,$relations,$sort = []) { }
public function fetch_array() { return FALSE; }
public function insertRecord($row) { }
public function updateRecord($wh,$row) { }
public function deleteRecord($wh) { }

}
