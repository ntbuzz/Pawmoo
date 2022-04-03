<?php
//==============================================================================
// Databas Table Create Class
class AppSchema extends AppBase {
	public $Language = [];		// Language list for safety
	public $ModelFields;		// read-only column => view column
	public $TableFields;		// read/write column => table column
	public $locale_columns;
	public $ViewSet;
	const typeXchanger  = [
		'Postgre' =>  [],
		'SQLite' => [
			'serial' => 'integer',
		],
	];
//==============================================================================
// setup table-name and view-table list
	function __construct() {
	    parent::__construct();                    // call parent
        foreach(static::$DatabaseSchema as $key => $val) $this->$key = $val;
		$viewset = (isset($this->DataView)) ? ((is_array($this->DataView)) ? $this->DataView : [$this->DataView]) : [];
		if(is_array($this->DataTable)) {
			list($table,$view) = $this->DataTable;
			if($table !== $view) array_unshift($viewset, $view);
		} else $table = $this->DataTable;
		$this->MyTable = $table;
		$this->ViewSet = $viewset;
        $driver = $this->Handler . 'Handler';
        $this->dbDriver = new $driver($this->MyTable,$this->Primary);        // connect Database Driver
		$this->SchemaSetup();
		echo sprintf("  * %-14s schema use %s Handler.\n",$this->ModuleName,$this->Handler);
    }
//==============================================================================
// Switch Schema Language
private function SchemaSetup() {
    $this->SchemaAnalyzer();
	xdebug_dump([             // DEBUG LOG information
		$this->ModuleName => [
			'SCHEMA' => $this->Schema,
			'VIEW' => $this->ViewSet,
			'TABLE' => [ $this->TableFields ],	// row table field
			'FIELD' => [ $this->ModelFields ],	// model field
		]
	]);
}
//==============================================================================
//	スキーマ解析
// 	'id'	=> [ 'serial',	00100,	0 ],	通常フィールド
//	'bind'	=> [ 'bind',	01110,	50,[ 'firstname','lastname' ] ],	// 結合フィールド
// 	'os_id' => [ 'integer',	01110,	50,		リレーションビューフィールド
// 		'Os.id' => [							リレーションモデル・キー
// 			'os_name' => [ 'name',00100,20],	ビューフィールド
// 			'os_name_en'=> 'name_en',			言語フィールド
// 			'cat_id' => [ 'integer', 00000, NULL,						
// 				'Oscat.id' => [					ネストリレーションモデル・キー
// 					'os_providor' => [ 'provider',0100,40],		リレーション名
// 					'group_id' => [	'integer', 00000, NULL,		ネスト
// 						'Osgrp.id' => [
// 							'os_group' => [ 'group',0010,50],
// 						]
// 					]
// 				]
// 			]
// 		]
// 	]
private function SchemaAnalyzer() {
	$this->ModelFields = $this->TableFields = [];
	// サブリレーションのフィールド抽出
	$relation = function($base,$rels) use(&$relation) {
		list($model) = explode('.',$base);
		foreach($rels as $kk => $vv) {
			list($alias,$flag,$width,$rel) = array_extract($vv,4);
			if(is_array($rel)) {
//				if($alias !== '---') $this->ModelFields[$kk] = ["alias-{$alias}",$flag,$width];
				list($link,$sub) = array_first_item($rel);
				$relation($link,$sub);
			} else if(is_array($vv)) {
				$this->ModelFields[$kk] = ["alias",$flag,$width,"{$model}.{$alias}"];
			}
		}
	};
	foreach($this->Schema as $key => $defs) {
		list($dtype,$flag,$width,$rel) = array_extract($defs,4);
		$dtype = strtolower($dtype);
		// 言語フィールドは省略
		if(is_array($defs)) $this->ModelFields[$key] = [$dtype,$flag,$width];
		list($link,$def) = array_first_item($rel);
		if(is_int($link)) $def = $rel;
		if($dtype !== 'virtual' && ($rel === NULL || !is_int($link))) {	// relation-field
			$this->TableFields[$key] = [$dtype,$flag,$width];
			list($sort,$align,$csv,$lang) = oct_extract($flag,4);
			if($lang) {
				foreach($this->Language as $lo) {
					$this->TableFields["{$key}_{$lo}"] = [$dtype,0,NULL];
				}
			}
		}
		if(!is_int($link) && is_array($def)) $relation($link,$def);
	}
}
//==============================================================================
//	言語リソースの再設定
protected function ResetLocation() {
	$this->locale_columns=[];
	foreach($this->ModelFields as $key => $defs) {
		list($type,$flag,$wd,$bind) = array_extract($defs,4);
		if($flag & 01000) {
			$locale_name = "{$key}_" . LangUI::$LocaleName;
			if(array_key_exists($locale_name,$this->columns)) {
				$this->locale_columns[$key] = $locale_name;
			}
		}
	}
	$this->dbDriver->fieldAlias->SetupAlias($this->locale_columns);
}
//==============================================================================
// execute SQL
	private function doSQL($exec,$sql) {
		if(empty($sql)) return;
//		echo "SQL: {$sql}\n";
		if($exec) $this->dbDriver->execSQL($sql);
    }
//==============================================================================
//	データベーステーブルを作成
public function CreateDatabase($exec=false) {
	// DEOP TABLE
	$sql = $this->dbDriver->drop_sql("TABLE",$this->MyTable);
	$this->doSQL($exec,$sql);
	// $sql = "SELECT COUNT(*) as \"total\" FROM sqlite_master WHERE TYPE='table' AND name='{$this->MyTable}';";
	// $sql = SELECT COUNT(*) as \"total\" FROM pg_stat_user_tables WHERE name='{$this->MyTable}';";
	// $this->doSQL($exec,$sql);
	// if($exec) $field = $this->dbDriver->fetch_array();
	// $exist =  ($field) ? intval($field["total"]) : 0;
	// Create Table
	$fset = [];
	foreach($this->TableFields as $column => $defs) {
		list($ftype,$flag,$wd,$bind) = array_extract($defs,4);
		if(is_scalar($ftype) && !in_array($ftype,['bind','virtual'])) {
			switch($ftype) {
			case '---': $ftype = 'integer';break;
			case '***': $ftype = 'text';break;
			}
			$lftype = strtolower($ftype);
			if(array_key_exists($lftype,static::typeXchanger[HANDLER])) $ftype = static::typeXchanger[HANDLER][$lftype];
			$str = "{$column} {$ftype}";
			if($lftype === 'serial') $str .= " NOT NULL";
			$fset[] = $str;
			// list($sort,$align,$csv,$lang) = oct_extract($flag,4);
			// if($lang) {
			// 	foreach($this->Language as $lo) {
			// 		$fset[] = "{$column}_{$lo} {$ftype}";
			// 	}
			// }
		}
	}
	$fset[] = "PRIMARY KEY ({$this->Primary})";
	$sql = "CREATE TABLE {$this->MyTable} (\n";
	$sql .= implode(",\n",$fset) . "\n);";
debug_dump(["SQL({$this->Handler})"=>$sql]);
	$this->doSQL($exec,$sql);
	// initCSVがあるときはデータロード
}
//==============================================================================
//	テーブルとビューを作成
public function DependList($list) {
	if(empty($this->Dependent)) return $list;
	$new = array_unique(array_merge($this->Dependent,$list));
	foreach($this->Dependent as $sub) {
		if(!in_array($sub,$list)) $new = $this->$sub->DependList($new);
	}
	return $new;
}
//==============================================================================
//	テーブルとビューを作成
public function CreateTableView($exec=false) {
	if(empty($this->ViewSet)) {
		debug_dump([$this->ModuleName=>'has no VIEW'],false);
		return false;
	}
	// DROP-VIEW
	foreach($this->ViewSet as $view) {
		$sql = $this->dbDriver->drop_sql("VIEW",$view);
		$this->doSQL($exec,$sql);
	}
	// CREATE-VIEW
	foreach($this->ViewSet as $view) {
		debug_dump(["Create View" => $view],false);
		$sql = $this->createView($view);
		$this->doSQL($exec,$sql);
	}
}
//==============================================================================
// extract DataTable or Alternate DataView
    private function model_view($db) {
		list($model,$field) = fix_explode('.',$db,2);
        if(preg_match('/(\w+)(?:\[(\d+)\])/',$model,$m)===1) {
            list($tmp,$model,$n) = $m;
        } else $n = NULL;
		$table = $this->$model->get_view_name($n);
        return [$table,$field];
    }
//==============================================================================
// extract DataTable or Alternate DataView
private function get_view_name($n) {
		if($n === NULL) {
			$table = (is_array($this->DataTable))? $this->DataTable[1]:$this->DataTable;
		} else if(isset($this->DataView)) {
			$table = $this->DataView[$n];
		} else $table = $this->MyTable;
        return $table;
    }
//==============================================================================
// createView: Create View Table
private function createView($view) {
	$join = [];
	$fields = [];
// 	'cat_id' => [ 'type', 0, NULL , 'Oscat.id' => [ ... ]  ]
//   key         rels
	$relations = function($table,$key,$rels) use(&$relations,&$join,&$fields) {
		list($kk,$arr) = array_first_item($rels);
		// kk = Oscat.id arr = []
		list($tbl,$nm) = $this->model_view($kk);
		$join[] = "LEFT JOIN {$tbl} ON {$table}.{$key}={$tbl}.{$nm}";
		foreach($arr as $fn => $defs) {
			list($type,$flag,$wd,$bind) = array_extract($defs,4);
			list($sort,$align,$csv,$lang) = oct_extract($flag,4);
			//  fn = cat_name, type  = 'name', flag= 00100, $wd=20
			//  fn = cat_id type  = [ OScat.id => [...], NULL ,NULL ],
			if(is_array($type)) {	// リレーション
				$relations($tbl,$fn,$type);
			} else if($bind===NULL) {
				$fields[] = "{$tbl}.\"{$type}\" as {$fn}";
				if($lang) {
					foreach($this->Language as $lo) {
						$fields[] = "{$tbl}.\"{$type}_{$lo}\" as {$fn}_{$lo}";
					}
				}
			} else {	// BIND またはサブリレーション
				list($kk,$rel) = array_first_item($bind);
				if(is_int($kk)) {
					list($sep,$bind) = array_first_item($rel);
					$fields[] = $this->dbDriver->fieldConcat($sep,$bind) . " as {$fn}";
					if($lang) {
						foreach($this->Language as $lo) {
							$lo_bind = array_map(function($v) use(&$lo) { return "{$v}_{$lo}";},$bind);
							$fields[] = $this->dbDriver->fieldConcat($sep,$lo_bind) . " as {$fn}_{$lo}";
						}
					}

				} else {
					if(!empty($type) && $type !== '---' && $type !== '***') $fields[] = "{$tbl}.\"$fn\"";
					$relations($tbl,$fn,$bind);
				}
			}
		}
	};
	foreach($this->Schema as $column => $defs) {
		list($dtype,$flag,$wd,$bind) = array_extract($defs,4);
		if(is_scalar($dtype)) {
			if($dtype === 'virtual') continue;
			if($bind === NULL) {
				$fields[] = "{$this->MyTable}.\"{$column}\"";
				list($sort,$align,$csv,$lang) = oct_extract($flag,4);
				if($lang) {
					foreach($this->Language as $lo) {
						$fields[] = "{$this->MyTable}.\"{$column}_{$lo}\"";
					}
				}
			} else if(is_array($bind)) {
				list($kk,$rel) = array_first_item($bind);
				if(is_int($kk)) {		// Self Bind
					list($sep,$bind) = array_first_item($rel);
		 			$fields[] = $this->dbDriver->fieldConcat($sep,$bind) . " as {$column}";
				} else {	// リレーション
					$fields[] = "{$this->MyTable}.\"$column\"";
					$relations($this->MyTable,$column,$bind);
				}
			}
		} else {	// リレーション
			$relations($this->MyTable,$column,$dtype);
		}
	}
	$join_sql = (empty($join)) ? '':"\n".implode("\n",$join)."";
	$sql = "CREATE VIEW {$view} AS SELECT\n".implode(",\n",$fields) ."\nFROM {$this->MyTable}{$join_sql};";
debug_dump(["SQL({$this->Handler})"=>$sql]);
	return $sql;
}
//==============================================================================
// CSVデータが定義されていればデータを読み込む
public function ImportCSV($data_path,$exec) {
	echo "ImportCSV: ({$this->ModuleName}) TABLE ";
	if($exec === false) return;
	if(isset($this->InitCSV)) {
		$path = "{$data_path}{$this->InitCSV}";
		if(is_file($path)) {
			$sql = $this->dbDriver->truncate_sql($this->MyTable);
			$this->doSQL(true,$sql);
			echo "from '{$this->InitCSV}'\n";
			if (($handle = fcsvopen($path, "r")) !== FALSE) {
				$row_columns = array_keys($this->TableFields);
				while (($data = fcsvget($handle))) {	// for Windows/UTF-8 trouble avoidance
					if(count($data) !== count($row_columns)) {
						debug_die(['CHECK-CSV'=>['FILE'=>$path,'COL'=>$row_columns,'CSV'=>$data]]);
					} else {
						$diff_arr = array_diff($data,$row_columns);
						if(empty($diff_arr)) continue;	// maybe CSV Header line
					}
					$row = array_combine($row_columns,$data);
					list($primary,$id) = array_first_item($row);
					$this->dbDriver->upsertRecord([$primary=>$id],$row);
				}
				fclose($handle);
			}
		} else echo "NOT-FOUND:{$this->InitCSV}\n";
		list($ftype,$not_null) = $this->Schema[$this->Primary];
		if(strtolower($ftype) === 'serial') {
			$this->dbDriver->resetPrimary();
		}
	} else echo "has NO-InitCSV\n";
}

}