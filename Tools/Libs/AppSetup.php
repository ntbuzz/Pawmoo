<?php
//==============================================================================
// Databas Table Create Class
class AppSetup  extends AppBase {
	// アプリケーションツリー構造
	const SpecFolder = [
		'app' => [
			'common' 	=> [],
			"Class"		=> [],
			"Config"	=> ['config.php'],
			"extends"	=> [],
			"Models"	=> [],
			"modules"	=> [],
			'View'	=> [
				'lang' => [ 'common.lng', 'resource.lng' ],
				'res' => ['css' => [],'js' => [],'template.mss'],
				'Layout.tpl',
				'Header.tpl',
				'Footer.tpl',
			],
			'webroot' => [
				'css'	=> [ 'style.css' ],
				'cssimg'=> [],
				'js'	=> [ 'funcs.js'],
				'images'=> [],
			],
		],
		'appSpec' => [
			'Setup' => [],		// 仕様CSVの格納フォルダ
			'Schema' => [],		// 仕様CSVから生成するモデルスキーマファイル格納
			'Models' => [],		// モデルスキーマから生成するモデルクラスファイル格納
			'Lang' => [],		// 仕様CSVから生成する言語リソース
			'InitCSV' => [],	// テーブルの初期データCSV
			"Config"	=> ['config.php'],	// GlobalConfig定義(aooフォルダへコピー)
		],
	];
    const Module = [
		'res' => ['css' => [ 'mystyle.css' ],'js' => ['myscript.js' ],'template.mss'],
		'View' => [ 'Layout.tpl' ],
		"*Controller.php",
		"*Helper.php",
		"*Model.php",
	];
	// 初期ファイル
	const Template = [
		'config.php'		=> true,
		'*Controller.php'	=> 'Controller.php',
		'*Helper.php'		=> 'Helper.php',
		'*Model.php'		=> 'Model.php',
		'Layout.tpl'		=> true,
		'Header.tpl'		=> true,
		'Footer.tpl'		=> true,
		'mystyle.css'		=> true,
		'myscript.js'		=> true,
		'template.mss'		=> true,
	];
	private $AppName;
	private $AppRoot;
	private $AppConfig;
	const cmd_table = [
		//コマンド		メソッド		module指定
		'create'	=> ['AppTree',		NULL],		// appフォルダツリーの作成、省略可
		'spec'		=> ['SpecTree',		NULL],		// appSpecフォルダツリーの作成、不要
		'module'	=> ['ModTree',		true],		// モジュールフォルダツリーの作成、必須
		'schema'	=> ['GenSchema',	NULL],		// 仕様CSVからスキーマ・言語リソース生成、省略可
		'model'		=> ['GenModel',		NULL],		// スキーマからモデルクラス作成、必須
		'setup'		=> ['SetupModel',	NULL],		// schemaコマンドとmodelコマンドの連続実行、必須
		'table'		=> ['MakeTable',	NULL],		// スキーマからテーブルとビューを作成、CSVインポート、省略可
		'csv'		=> ['ImportCSV',	NULL],		// CSVインポート、省略可
		'view'		=> ['MakeView',		NULL],		// スキーマからビューを作成、省略可
	];
//==============================================================================
//	constructor( object owner )
function __construct($appname) {
	$this->AppName = $appname;
	$this->AppRoot = ROOT_DIR . "/app/{$appname}";
	$this->AppConfig = ROOT_DIR . "/appSpec/{$appname}";
}
//==============================================================================
private function Help($msg) {
	echo "{$msg}\n";
	echo "See Help\n";
	exit(-1);
}
//==============================================================================
// Execute Create TABLE,VIEW, and INTIAL DATA
//	before check Dependent Table
public function execute($cmd,$model,$exec) {
	// コマンドチェックk
	if(!array_key_exists($cmd,self::cmd_table)) {
		$this->Help("BAD COMMAND($cmd)");
	}
	list($func,$mod) = self::cmd_table[$cmd];
	if($model === '-' || $model === 'all') $model = NULL;
	$exec = ($exec === 'false') ? false : true;
	// モジュール名の引数チェック
	if(empty($model) && $mod) {
		$this->Help("'{$cmd}' Need module parameter");
	}
	$this->$func($model,$exec);
}
//==============================================================================
// appフォルダツリーの作成、省略可
private function AppTree($model,$exec) {
	foreach(self::SpecFolder as $top => $tree) {
		$folder = ROOT_DIR . "/{$top}/{$this->AppName}";
		if($this->createFolder($folder,NULL,$tree) === false)
			echo "Create '{$this->AppName}' {$top}-Folder\n";
		else echo "{$top} '{$this->AppName}' allready exist.\n";
	}
	// モジュール指定があればモジュールフォルダも作成
	if(!empty($model)) $this->ModTree($model);
}
//==============================================================================
// appSpecフォルダツリーの作成、省略可
private function SpecTree($model,$exec) {
	$folder = ROOT_DIR . "/appSpec/{$this->AppName}";
	if($this->createFolder($folder,NULL,self::SpecFolder['appSpec']) === false)
		echo "Create '{$this->AppName}' Spec-Folder\n";
	else echo "'{$this->AppName}' Spec-Folder allready exist.\n";
}
//==============================================================================
// モジュールフォルダツリーの作成、必須
private function ModTree($model,$exec) {
	$path = "{$this->AppRoot}/modules/{$model}";
	if($this->createFolder($path,$model,self::Module) === false)
		echo "Create Module '{$model}'\n";
	else echo "Module '{$model}' allready exist.\n";
}
//==============================================================================
// 仕様CSVのファイル取得
	private function get_csv_files($model) {
		if($model) {
			$model = strtolower($model);
			$csv_file = "{$this->AppConfig}/Setup/{$model}_def.csv";
			$files = (file_exists($csv_file)) ? [$csv_file] : false;
		} else {
			$csv_dir = "{$this->AppConfig}/Setup/";
			$files = get_files($csv_dir,'.csv');
		}
		return $files;
	}
//==============================================================================
// スキーマファイルのリスト取得
	private function get_schema_files($model) {
		$schema_dir = "{$this->AppConfig}/Schema/";
		if($model) {
			$files = (is_file("{$schema_dir}{$model}Schema.php"))?[$model]:false;
		} else {
			$files = get_files($schema_dir,'.php',false);
			if($files !== false) $files = array_map(function($v) { return str_replace('Schema.php','',$v);},$files);
		}
		return $files;
	}
//==============================================================================
// スキーマファイルのダンプ
	private function dump_array($schema,$indent) {
		$line = [];
		$spc = str_repeat(' ',$indent*4);
		foreach($schema as $key=>$defs) {
			if($defs === NULL || $defs === []) continue;
			if(is_scalar($defs)) {
				if(substr($key,0,1)==='*') {
					$key = substr($key,1);
					$defs = "[\n{$defs}\n{$spc}]";
				} else if(is_bool($defs)) {
					$defs = ($defs) ? 'TRUE':'FALSE';
				} else if($defs === HANDLER) {
					$defs = 'HANDLER';
				} else if(!is_int($defs)) {
					$defs = "'{$defs}'";
				}
				$line[] = "{$spc}'{$key}'\t=> {$defs},";
			} else {
				list($k,$v) = array_first_item($defs);
				if(is_int($k)) {
					$val = implode(', ',array_map(function($k,$v) {
						if(is_int($k)) return "'{$v}'";
						return "'{$k}' => '{$v}'";
					},array_keys($defs),array_values($defs)));
					$line[] = "{$spc}'{$key}'\t=> [ {$val} ],";
				} else {
					$line[] = "{$spc}'{$key}'\t=> [";
					$line[] = $this->dump_array($defs,$indent+2);
					$line[] = "{$spc}],";
				}
			}
		}
		return implode("\n",$line);
	}
//==============================================================================
// 仕様CSVからスキーマ・言語リソース生成、省略可
private function GenSchema($model,$exec) {
	// 指定された、見つかったクラスファイルを全て処理する
	$files = $this->get_csv_files($model);
	if($files === false) {
		echo "Cannot Generated Schema.\n";
		return false;
	}
	$models = [];
	foreach($files as $target) $models[] = $this->makeModelSchema($target);
	$model = implode(', ',$models);
	echo "Module '{$model}' Schema Generated.\n";
}
//==============================================================================
// スキーマからモデルクラス作成、省略可
private function GenModel($model,$exec) {
	// 指定された、見つかったクラスファイルを全て処理する
	$files = $this->get_schema_files($model);
	if($files === false) {
		echo "Cannot Generated Model.\n";
		return false;
	}
	$models = [];
	foreach($files as $target) {
		$models[] = $this->makeModelClass($target);
	}
	$model = implode(', ',$models);
	echo "Module '{$model}' ModelClass Generated.\n";
}
//==============================================================================
// schemaコマンドとmodelコマンドの連続実行、必須
private function SetupModel($model,$exec) {
	// 指定された、見つかったクラスファイルを全て処理する
	$files = $this->get_csv_files($model);
	if($files === false) {
		echo "Cannot Setup '{$model}'.\n";
		return false;
	}
	$models = [];
	foreach($files as $target) {
		list($pp,$ff) = extract_path_filename($target);
		echo str_repeat("=", 10)."<< {$ff} >>".str_repeat("=", 10)."\n";
		$module = $this->makeModelSchema($target);
		if($module) {
			$models[] = $module;
			$this->GenModel($module,$exec);
			// $mod_folder = "{$this->AppRoot}/modules/{$module}";
			// if(!is_dir($mod_folder)) {
			// 	$this->createFolder("{$this->AppRoot}/modules/{$module}",$module,self::Module);
			// }
		} else {
			debug_die(['FAIL STOP'=>$model]);
		}
	}
	$model = implode(', ',$models);
	echo "Setup Success '{$model}' module.\n";
}
//==============================================================================
// スキーマからテーブルとビューを作成、CSVインポート、省略可
private function MakeTable($model,$exec) {
	// 指定された、見つかったクラスファイルを全て処理する
	$files = $this->get_schema_files($model);
	if($files === false) {
		echo "Cannot TABLE '{$model}'.\n";
		return false;
	}
	$csv_path = "{$this->AppConfig}/InitCSV/";
	foreach($files as $mm) {
		$schema = $this->$mm;
		$schema->CreateDatabase($exec);
		if($exec) $schema->ImportCSV($csv_path);
	}
	$this->MakeView($model,$exec);
}
//==============================================================================
// CSVインポート、省略可
private function ImportCSV($model) {
	// 指定された、見つかったクラスファイルを全て処理する
	$files = $this->get_schema_files($model);
	if($files === false) {
		echo "Cannot CSV '{$model}'.\n";
		return false;
	}
	$csv_path = "{$this->AppConfig}/InitCSV/";
	foreach($files as $model) {
		$schema = $this->$model;
		$schema->ImportCSV($csv_path);
	}
}
//==============================================================================
// スキーマからビューを作成、省略可
private function MakeView($model,$exec) {
	// 指定された、見つかったクラスファイルを全て処理する
	$files = $this->get_schema_files($model);
	foreach($files as $model) {
		$schema = $this->$model;
		$depend = $schema->DependList([]);
		if($depend !== []) {
debug_dump([$model => ['DEPEND-VIEW'=>$depend]]);
			foreach($depend as $sub) {
				$this->$sub->CreateTableView($exec);
			}
		}
		$schema->CreateTableView($exec);
	}
}
//==============================================================================
// フォルダーツリーを作成
private function createFolder($path,$module,$file) {
	debug_die(['MOD-TREE'=>$module]);
	$exist = true;
	if(is_scalar($file)) {
		$modfile = (substr($file,0,1)==='*') ? $module.substr($file,1) : $file;
		$target = "{$path}/{$modfile}";
		if(!is_file($target)) {
			if(array_key_exists($file,self::Template)) {
				$tmp_file = (self::Template[$file]===true)? $file : self::Template[$file];
				$tmp_file = "Tools/Template/{$tmp_file}";
		        $contents = file_get_contents($tmp_file);          // ファイルから全て読み込む
				$template = str_replace('%module%',$module,$contents);
				file_put_contents($target,$template);
			} else {
				touch($target);
			}
			$exist = false;
		}
	} else if(is_array($file)) {
		if(!is_dir($path)) {
			mkdir($path,0777,true);
			$exist = false;
		}
		foreach($file as $key => $val) {
			$dir = (is_int($key)) ? $path : "{$path}/{$key}";
			if($this->createFolder($dir,$module,$val)==false) $exist = false;

		}
	}
	return 	$exist;
}

//==============================================================================
// CSV file Load (must be UTF-8)
private function loadCSV($path) {
//	list($p,$f) = extract_path_filename($path);
//echo "CSV Load:{$f}\n";
	$columns = [ 'No','名前','フィールド名','言語','タイプ','リレーション','表示フラグ','CSV','メモ'];
	$Schema = [];
	$Database = [];
	if (($handle = fcsvopen($path, "r")) !== FALSE) {
		while (($data = fcsvget($handle))) {	// for Windows/UTF-8 trouble avoidance
			if($data[0] === 0) {
				list($ln,$key,$val,$opt) = array_alternative($data,4);
				if(!empty($opt)) $val = [ $val,$opt];
				$Database[$key] = $val;
			} else if(count($data) == count($columns)) {
				$data = array_combine($columns,$data);
				$no = array_shift($data);
				if(is_int($no)) $Schema[$no] = $data;
			} else {
				if($data[0] === '-') continue;	// skip
				debug_dump(['CHECK'=>$data]);
			}
		}
		fclose($handle);
	}
	$Database['Schema'] = $Schema;
	return $Database;
}
//==============================================================================
// CSV file Load (must be UTF-8)
private function createSchema($fields) {
	$DispFlags = [
		'L' => 00010,	// Left
		'C' => 00020,	// Center
		'R' => 00030,	// Right
		'S' => 00002,	// Sortable
		'N' => 00001,	// Non-Sortable
	];
	$col = [];
	$resource = [];
	$virtual = [];
	$depend = [];
	foreach($fields as $key => $column) {
		list($name,$field,$lang,$type,$rel,$disp,$csv,$note) = array_values($column);
		$type = strtolower($type);
		list($fname,$sep) = fix_explode('.',$field,2,NULL);
		$fname = strtolower($fname);
		$resource[$fname] = $name;
		$flag = 0;
        if(preg_match('/([LCR])?([SN]?)(\d+)/',$disp,$m)===1) {
			list($tmp,$align,$sort,$wd) = $m;
			$disp = 0;
			if(isset($DispFlags[$align])) $flag |= $DispFlags[$align];
			if(isset($DispFlags[$sort])) $flag |= $DispFlags[$sort];
		} else list($align,$sort,$wd) = [NULL,NULL,NULL];
		if(!empty($csv)) $flag |= 00100;
		if($lang) $flag |= 01000;
		switch($type) {
		case 'alias':
				if(empty($rel)) break;
				list($id,$rel_name,$rel_bind) = fix_explode('.',$rel,3,NULL);
				if(!empty($rel_bind)) {
					if($sep === NULL) $rel_name = [$rel_name,$rel_bind];
					else $rel_name = [$rel_name, $sep => $rel_bind ];
				}
				list($link,$rels) =	array_first_item(array_slice($col[$id], 3, 1, true));
				$rels[$fname] = [$rel_name, $flag, $wd ];
				if($lang) {
					foreach($this->Language as $lng) {
						if(is_array($rel_name)) $bstr = [ array_map(function($v) use(&$lng) {
							return "{$v}_{$lng}";
						},$rel_name),NULL,NULL ];
						else $bstr = "{$rel_name}_{$lng}";
						$rels["{$fname}_{$lng}"] = $bstr;
					}
				}
				$col[$id][$link] = $rels;
				if(is_scalar($this->DataTable)) $this->DataTable = [$this->DataTable,"{$this->DataTable}_view"];
				break;
		case 'bind':	// self-bind or Link-Bind
				$bind = bind_array($rel,$sep);
				$col[$fname] = [ $type, $flag, $wd ,$bind ];
				// self-bindは言語依存しない
				if(strpos($rel,'.') !== false && $lang) {
					$flag &= 00700;			// CSVのみ残す
					foreach($this->Language as $lng) {
						$bstr = array_map(function($v) use(&$lng) {
							return "{$v}_{$lng}";
						},$bind);
						$col["{$fname}_{$lng}"] = [ $type, $flag, NULL, $bstr];
					}
				}
				if(is_scalar($this->DataTable)) $this->DataTable = [$this->DataTable,"{$this->DataTable}_view"];
				break;
		case 'virtual':	// 仮想フィールドは言語依存しない
				$col[$fname] = [ $type, $flag, $wd ];
				$virtual[$fname] = bind_array($rel,$sep);
				break;
		default:
				$col[$fname] = [ $type, $flag, $wd];
				if($lang) {
					$flag &= 00700;			// CSVのみ残す
					foreach($this->Language as $lng) {
						$col["{$fname}_{$lng}"] = $type;//[ $type, $flag];
					}
				}
				if(!empty($rel)) {
					list($m,$ix) = explode('.',$rel);
					$depend[] = $m;
					$col[$fname][$rel] = [];
				}
		}
	}
	return [$resource,$col,$virtual,$depend];
}
//==============================================================================
// スキーマ構造の作成
private function makeSchema($Schema,$lang=NULL) {
	$dump_schema = function($schema,$indent) use(&$lang,&$dump_schema) {
		$line = [];
		foreach($schema as $key=>$defs) {
			if($defs === NULL) continue;
			if(is_scalar($defs)) {
				$ln = str_repeat(' ',$indent*4) . "'{$key}'\t=> '{$defs}',";
			} else {
				list($type,$flag,$wd,$rel) = array_extract($defs,4);
				$flag = oct_fix($flag);
				$ln = str_repeat(' ',$indent*4) . "'{$key}'\t=> [ '{$type}',\t{$flag}";
				if($wd===NULL) $wd = 'NULL';
				if(is_array($rel)) {
					$ln = "{$ln},\t{$wd},";
					list($link,$rels) = array_first_item($rel);
					if(is_int($link)) {
						$val = "'".implode("', '",$rel)."'";
						$ln = "{$ln}\t[ {$val} ] ],";
						if(array_key_exists($key,$lang)) $ln = "{$ln}\t// {$lang[$key]}";
					} else {
						$spc = str_repeat(' ',($indent+1)*4);
						if(array_key_exists($key,$lang)) $ln = "{$ln}\t// {$lang[$key]}";
						$ln = "{$ln}\n{$spc}'{$link}' => [\n". $dump_schema($rels,$indent+2)."\n{$spc}],\n".str_repeat(' ',$indent*4)."],";
					}
				} else {
					$ln = "{$ln}, {$wd} ],";
					if(array_key_exists($key,$lang)) $ln = "{$ln}\t// {$lang[$key]}";
				}
			}
			$line[] = $ln;
		}
		return implode("\n",$line);
	};
	return $dump_schema($Schema,2);
}
//==============================================================================
// モデルスキーマ定義ファイルをスキャンする
private function makeModelSchema($csv_file) {
	if(is_file($csv_file)) {
		$database = $this->loadCSV($csv_file);
		$this->unsetProperty([
			'Model'		=> 1,'Handler' => 1,'DataTable'	=> 1,'DataView'	=> 1,
			'InitCSV'	=> 1,'Primary' => 1,'Language'	=> 1,
		]);
		$this->SetProperty($database);
		if(empty($this->Handler)) $this->Handler = HANDLER;
		if(empty($this->Model)) {
			list($path,$fname,$ext) = extract_path_file_ext($csv_file);
			list($fname,$opt) = explode('_',$fname);
			$this->Model = ucfirst($fname);
		}
		if(isset($this->Language)) {
			$this->Language = str_explode([";",","],"{$this->Language}");
		} else $this->Language = [];
		list($lng,$schema,$virt,$depend) = $this->createSchema($this->Schema);
		$schema_txt = $this->makeSchema($schema,$lng,true);
		$this->DatabaseSchema = [
			'Handler' => $this->Handler,
			'DataTable' => $this->DataTable,
			'DataView' =>	$this->DataView,
			'Dependent' =>	$depend,
			'Primary' => $this->Primary,
			'InitCSV' => $this->InitCSV,
			'Lang_Alternate' => true,
			'*Schema' => $schema_txt,
			'Virtual' => $virt,
			'Language' => $this->Language,
			'Lang' => $lng,
		];
		$db_def = $this->dump_array($this->DatabaseSchema,1);
		$rep_array =[
			'%model%' => $this->Model,
			'%databasedefs%' => $db_def,
		];
		$tmp_file = "Tools/Template/AppSchema.php";
		$contents = file_get_contents($tmp_file);          // ファイルから全て読み込む
		$template = str_replace(array_keys($rep_array),array_values($rep_array),$contents);
		$target = "{$this->AppConfig}/Schema/{$this->Model}Schema.php";
		file_put_contents($target,$template);
		return $this->Model;
	} else echo "Bad CSV file({$csv_file})\n";
	return false;
}
//==============================================================================
// 言語リソース変換
private function makeResource($lang,$list) {
	array_unshift($list,'ja');
	$lng = "Schema => [\n";
	foreach($list as $defs) {
		$lng = "{$lng}\t.{$defs} => [\n";
		foreach($lang as $key => $val) {
			if(strpos($val,' ')!==false) $val = "'{$val}'";
			else if(strpos($val,'"')!==false) $val = "'{$val}'";
			else if(strpos($val,"'")!==false) $val = "\"{$val}\"";
			$lng = "${lng}\t\t{$key}\t=> {$val}\n";
		}
		$lng = "${lng}\t]\n";
	}
	$lng = "${lng}]\n";
	return $lng;
}
//==============================================================================
// モデルクラスのスキーマ変換
private function makeModelField($Schema,$lang=NULL) {
	$dump_schema = function($schema,$indent) use(&$lang,&$dump_schema) {
		$line = [];
		foreach($schema as $key=>$defs) {
			if($defs === NULL) continue;
			if(is_array($defs)) {
				list($type,$flag,$wd,$rel) = array_extract($defs,4);
				if(in_array($type,['alias','bind'])) $type = '____';
				$flag = oct_fix($flag);
				if($wd===NULL) $wd = 0;
				$spc_len = 18 - strlen($key);
				if($spc_len <= 0) $spc_len = 2;
				$spc = str_repeat(' ',$spc_len);
				$ln = str_repeat(' ',$indent*4) . "'{$key}'{$spc}=> [ '{$type}',\t{$flag},\t{$wd} ],";
				if(array_key_exists($key,$lang)) $ln = "{$ln}\t// {$lang[$key]}";
				$line[] = $ln;
			}
		}
		return implode("\n",$line);
	};
	return $dump_schema($Schema,2);
}
//==============================================================================
// スキーマからモデルクラス作成、必須
private function makeModelClass($model) {
	$schema = $this->$model;
	$lng_txt = $this->makeResource($schema->Lang,$schema->Language);
	$target = "{$this->AppConfig}/Lang/{$model}.lng";
	file_put_contents($target,$lng_txt);
	$gen_virtual = function($virt) {
		$ln = [];
		foreach($virt as $key => $col) {
			if(is_scalar($col)) $ln[] = "\$row['{$key}'] = \$row['{$col}'];";
			else {
				$ss = "";
				foreach($col as $k => $fn) {
					if(!is_int($k)) $ss = $ss.tag_body_name($k);
					$ss ="{$ss}{\$row['{$fn}']}";
				}
				$ln[] = "\$row['{$key}'] = \"{$ss}\";";
			}
		}
		$virt_field = implode("\n\t",$ln);
		$rep_array =[
			'%virt_field%' => $virt_field,
		];
		$tmp_file = "Tools/Template/virtul_method.php";
		$contents = file_get_contents($tmp_file);          // ファイルから全て読み込む
		$template = str_replace(array_keys($rep_array),array_values($rep_array),$contents);
		return $template;
	};
	$schema_txt = $this->makeModelField($schema->FieldSchema,$schema->Lang);
	$this->DatabaseSchema = [
		'Handler' => $schema->Handler,
		'DataTable' => $schema->DataTable,
		'DataView' =>	(isset($schema->DataView)) ? $schema->DataView : [],
		'Primary' => $schema->Primary,
		'Lang_Alternate' => $schema->Lang_Alternate,
		'*Schema' => $schema_txt,
		'Virtual' => (isset($schema->Virtual)) ? $schema->Virtual : [],
	];
	$db_def = $this->dump_array($this->DatabaseSchema,1);
	$virtula_method = (isset($schema->Virtual))?$gen_virtual($schema->Virtual):NULL;
	$rep_array =[
		'%model%' => $model,
		'%databasedefs%' => $db_def,
		'%virtual_class%' => $virtula_method,
	];
	$tmp_file = "Tools/Template/AppModel.php";
	$contents = file_get_contents($tmp_file);          // ファイルから全て読み込む
	$template = str_replace(array_keys($rep_array),array_values($rep_array),$contents);
	$target = "{$this->AppConfig}/Models/{$model}Model.php";
	file_put_contents($target,$template);
	return $model;
}


}
