<?php
//==============================================================================
//	データベースに接続しないNULLハンドラー
class NullHandler {
	public  $DateStyle = 'Y-m-d';
	public  $TimeStyle = 'H:i:s';
	public	$columns = [];       // record column data
	public 	$raw_columns=[];   // target real column
//==============================================================================
//	コンストラクタ： データベースのテーブルに接続する
	function __construct($table,$primary) {
		$this->fields = [];
		$this->fieldAlias = new fieldAlias();
	}
	protected function Connect($table) { }
	protected function getLastError() { return ''; }
	protected function doQuery($sql) { return NULL; }
	protected function fetch_array() { return FALSE; }
	protected function updateRecord($wh,$row) { }
	protected function reset_seq($table,$primary) { return FALSE;}
	protected function concat_fields($arr) {return implode(',',$arr); }
//==============================================================================
public function register_method($class,$method) { }
public function fetchDB() { return [];}
public function fieldConcat($sep,$arr) { return "";}
public function drop_sql($kind,$table) { return "";}
public function truncate_sql($table) { return "";}		// TRUNCATE SQL
public function setupRelations($relations) { $this->relations = $relations; }
public function SetPaging($pagesize, $pagenum) { }
public function getValueLists($table,$ref,$id,$cond) { return []; }
public function getRecordValue($row,$relations) { return []; }
public function doQueryBy($key,$val) { return []; }
public function findRecord($row,$relations,$sort = []) { $this->recordMax = 0; $this->fields = []; }
public function insertRecord($row) { }
public function deleteRecord($wh) { }

}
