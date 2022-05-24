<?php
//==============================================================================
//	データベースに接続しないNULLハンドラー
class NullHandler {
	public $DateFormat = [
		'Date' => 'Y-m-d',
		'Time' => 'H:i:s',
		'TimeStamp' => 'Y-m-d H:i:s',
	];
	public	$columns = [];       // record column data
	public 	$raw_columns=[];   // target real column
	public	$relations;			// for compatible othre handler
	public	$lang_alias = [];	// from fieldAlias:language field.
	public	$lang_alternate = FALSE; // from fieldAlias:use orijin field when lang-field empty.
//==============================================================================
//	コンストラクタ： データベースのテーブルに接続する
	function __construct($table,$primary,$db=NULL) {
		$this->fields = [];
	}
	protected function Connect($table) { }
	protected function getLastError() { return ''; }
	protected function doQuery($sql) { return NULL; }
	protected function fetch_array() { return FALSE; }
	protected function updateRecord($wh,$row) { }
	protected function reset_seq($table) { return FALSE;}
	protected function concat_fields($arr) {return implode(',',$arr); }
//==============================================================================
public function register_method($class,$method) { }
public function fetchDB() { return [];}
public function fieldConcat($sep,$arr) { return "";}
public function drop_sql($kind,$table) { return "";}
public function truncate_sql($table) { return "";}		// TRUNCATE SQL
public function setupFieldTransfer($alias,$relations=NULL) { $this->lang_alias = $alias; $this->relations = $relations; }
public function SetPaging($pagesize, $pagenum) { }
public function getValueLists($table,$ref,$id,$cond) { return []; }
public function getRecordValue($row) { return []; }
public function doQueryBy($key,$val) { return []; }
public function findRecord($row,$sort = []) { $this->recordMax = 0; $this->fields = []; }
public function insertRecord($row) { }
public function deleteRecord($wh) { }

}
