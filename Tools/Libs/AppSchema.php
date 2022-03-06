<?php
//==============================================================================
// Databas Table Create Class
class AppSchema extends AppBase {
	public $FieldSchema;		// read-only column => view column
	public $TableSchema;		// read/write column => table column
	public $locale_columns;
	public $bind_columns;
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
    }
//==============================================================================
// Switch Schema Language
private function SchemaSetup() {
    $this->SchemaAnalyzer();
	xdebug_dump([             // DEBUG LOG information
		$this->ModuleName => [
			'SCHEMA' => $this->Schema,
			'VIEW' => $this->ViewSet,
			'TABLE' => [ $this->TableSchema ],	// row table field
			'FIELD' => [ $this->FieldSchema ],	// model field
		]
	]);
}
//==============================================================================
//	スキーマ解析
// 'Schema' => [
// 	'id'	=> [ 'serial',	00100,	0 ],	通常フィールド
//	'bind'	=> [ 'bind',	01110,	50,[ 'firstname','lastname' ] ],	// 結合フィールド
//	'name_list_id'	=> [ 'integer',	0,	NULL,	// 名前ID
//     'Names.id' => [
//         'host_name'	=> [ 'name',	01000 ],	// ホスト名
//         'host_name_en'	=> 'name_en',
//     ],
// ],
// 	'os_id' => [ 'alias',	01110,	50,		リレーションビューフィールド
// 		'Os.id' => [							リレーションモデル・キー
// 			'os_name' => [ 'name',00100,20],	ビューフィールド
// 			'os_name_en'=> 'name_en',			言語フィールド
// 			'cat_id' => [						
// 				'Oscat.id' => [					ネストリレーションモデル・キー
// 					'os_providor' => [ 'provider',0100,40],		リレーション名
// 					'group_id' => [				ネスト
// 						'Osgrp.id' => [
// 							'os_group' => [ 'group',0010,50],
// 						]
// 					]
// 				]
// 			]
// 		]
// 	]
private function SchemaAnalyzer() {
	$this->FieldSchema = $this->TableSchema = [];
	foreach($this->Schema as $key => $defs) {
		list($dtype,$flag,$width,$rel) = array_extract($defs,4);
		$dtype = strtolower($dtype);
		$this->FieldSchema[$key] = [$dtype,$flag,$width];
		list($link,$def) = array_first_item($rel);
		if(is_int($link)) $def = $rel;
xdebug_dump(['EXTRACT'=>$rel,'LINK'=>$link,'DEF'=>$def]);
		if($dtype !== 'virtual' && ($rel === NULL || !is_int($link))) {	// relation-field
			$this->TableSchema[$key] = [$dtype,$flag,$width];
		}
		if(is_array($def)) {
			if(is_int($link)) {		// bind
				if($width !== NULL) $this->FieldSchema[$key] = ['bind',$flag,$width];
			} else{
				$view = [];
				foreach($def as $kk => $vv) {
					list($alias,$flag,$width,$rel) = array_extract($vv,4);
					$atype = (is_array($alias))?'alias-bind':'alias';
					$this->FieldSchema[$kk] = [$atype,$flag,$width];
					$view[$kk] = $alias;
				}
			}
		}
	}
}
//==============================================================================
//	言語リソースの再設定
protected function ResetLocation() {
	$this->locale_columns=[];
	foreach($this->FieldSchema as $key => $defs) {
		if($defs[4]) {
			$locale_name = "{$key}_" . LangUI::$LocaleName;
			if(array_key_exists($locale_name,$this->columns)) {
				$this->locale_columns[$key] = $locale_name;
			}
		}
	}
	$this->dbDriver->fieldAlias->SetupAlias($this->locale_columns,$this->bind_columns);
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
	// Create Table
	$fset = [];
	foreach($this->TableSchema as $column => $defs) {
		list($ftype,$flag,$wd,$bind) = array_extract($defs,4);
		if(is_scalar($ftype) && $ftype !== 'virtual') {
			$lftype = strtolower($ftype);
			if(array_key_exists($lftype,static::typeXchanger[HANDLER])) $ftype = static::typeXchanger[HANDLER][$lftype];
			$str = "{$column} {$ftype}";
			if($lftype === 'serial') $str .= " NOT NULL";
			$fset[] = $str;
		}
	}
	$fset[] = "PRIMARY KEY ({$this->Primary})";
	$sql = "CREATE TABLE {$this->MyTable} (\n";
	$sql .= implode(",\n",$fset) . "\n);";
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
		debug_dump([$this->ModuleName=>"Not have VIEW"]);
		return false;
	}
	// DROP-VIEW
	foreach($this->ViewSet as $view) {
		$sql = $this->dbDriver->drop_sql("VIEW",$view);
		$this->doSQL($exec,$sql);
	}
	// CREATE-VIEW
	foreach($this->ViewSet as $view) {
		debug_dump(["VIEW-DEFS" => $view]);
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
xdebug_dump(['model_view'=>$model,$field]);
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
	$alias_sep = function($key) {
		if(substr($key, -1) === '.') $key .= ' ';
		list($alias,$sep) = fix_explode('.',$key,2);
		return [$alias,$sep];
	};
	$join = [];
	$fields = [];
// 	'cat_id' => [ 'Oscat.id' => [ ... ]  ]
//   key         rels
	$relations = function($table,$key,$rels) use(&$relations,&$join,&$fields) {
		list($kk,$arr) = array_first_item($rels);
		// kk = Oscat.id arr = []
		list($tbl,$nm) = $this->model_view($kk);
		$join[] = "LEFT JOIN {$tbl} ON {$table}.{$key}={$tbl}.{$nm}";
		foreach($arr as $fn => $defs) {
			list($type,$flag,$wd,$bind) = array_extract($defs,4);
			//  fn = cat_name, type  = 'name', flag= 00100, $wd=20
			//  fn = cat_id type  = [ OScat.id => [...], NULL ,NULL ],
			if(is_array($type)) {	// リレーション
				$relations($tbl,$fn,$type);
			} else if($bind===NULL) {
				$fields[] = "{$tbl}.\"{$type}\" as {$fn}";
			} else {	// リレーション先のBIND
				list($alias,$sep) = $alias_sep($fn);
				$fields[] = $this->dbDriver->fieldConcat($sep,$bind) . " as {$alias}";
			}
		}
	};
	foreach($this->Schema as $column => $defs) {
		list($dtype,$flag,$wd,$bind) = array_extract($defs,4);
		if(is_scalar($dtype)) {
			if($dtype === 'virtual') continue;
			if($bind === NULL) {
				$fields[] = "{$this->MyTable}.\"$column\"";
			} else if(is_array($bind)) {
				list($kk,$rel) = array_first_item($bind);
				if(is_int($kk) || is_scalar($rel)) {		// Self Bind
					list($alias,$sep) = $alias_sep($column);
		 			$fields[] = $this->dbDriver->fieldConcat($sep,$bind) . " as {$alias}";
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
debug_dump(['SQL'=>$sql]);
	return $sql;
}
//==============================================================================
// CSVデータが定義されていればデータを読み込む
public function ImportCSV($data_path) {
	if(isset($this->InitCSV)) {
		$path = "{$data_path}{$this->InitCSV}";
		if(is_file($path)) {
			$sql = $this->dbDriver->truncate_sql($this->MyTable);
			$this->doSQL(true,$sql);
			echo "Load CSV from '{$this->InitCSV}'\n";
			if (($handle = fcsvopen($path, "r")) !== FALSE) {
				$row_columns = array_keys($this->TableSchema);
				while (($data = fcsvget($handle))) {	// for Windows/UTF-8 trouble avoidance
					if(count($data) !== count($row_columns)) {
						debug_die(['CHECK-CSV'=>['FILE'=>$path,'COL'=>$row_columns,'CSV'=>$data]]);
					} else {
						$diff_arr = array_diff($data,$row_columns);
						if(empty($diff_arr)) continue;	// maybe CSV Header line
					}
					$row = array_combine($row_columns,$data);
					list($primary,$id) = array_first_item($row);
//					$this->dbDriver->updateRecord([$primary=>$id],$row);
					$this->dbDriver->insertRecord($row);
				}
				fclose($handle);
			}
		} else echo "NOT-FOUND:{$this->InitCSV}\n";
		list($ftype,$not_null) = $this->Schema[$this->Primary];
		if(strtolower($ftype) === 'serial') {
			$this->dbDriver->resetPrimary();
		}
	} else echo "NO-InitCSV\n";
}

}