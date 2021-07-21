<?php
//==============================================================================
//	データベースに接続しないNULLハンドラー
class NullHandler {
	public  $DateStyle = 'Y-m-d';
//==============================================================================
//	コンストラクタ：　データベースのテーブルに接続する
	function __construct($table) {
		$this->fields = [];
		$this->fieldAlias = new fieldAlias();
	}
	protected function Connect($table) { }
	protected function getLastError() { return ''; }
	protected function doQuery($sql) { return NULL; }
	protected function fetch_array() { return FALSE; }
	protected function updateRecord($wh,$row) { }
//==============================================================================
public function fieldConcat($sep,$arr) { return "";}
public function drop_sql($kind,$table) { return "";}
public function truncate_sql($table) { return "";}		// TRUNCATE SQL
public function setupRelations($relations) { $this->relations = $relations; }
public function SetPaging($pagesize, $pagenum) { }
public function getValueLists($table,$ref,$id,$cond) { return []; }
public function getRecordValue($row,$relations) { return []; }
public function doQueryBy($key,$val) { return []; }
public function findRecord($row,$relations,$sort = []) { }
public function insertRecord($row) { }
public function deleteRecord($wh) { }

}
