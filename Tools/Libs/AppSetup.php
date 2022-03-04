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
		'model'		=> ['GenModel',		true],		// スキーマからモデルクラス作成、必須
		'setup'		=> ['SetupModel',	true],		// schemaコマンドとmodelコマンドの連続実行、必須
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
public function execute($cmd,$model) {
	// コマンドチェックk
	if(!array_key_exists($cmd,self::cmd_table)) {
		$this->Help("BAD COMMAND($cmd)");
	}
	list($func,$mod) = self::cmd_table[$cmd];
	// モジュール名の引数チェック
	if(empty($model)) {
		if($mod) $this->Help("'{$cmd}' Need module parameter");
	} else $model = ucfirst(strtolower($model));
	$this->$func($model);
}
//==============================================================================
// appフォルダツリーの作成、省略可
private function AppTree($model) {
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
private function SpecTree($model) {
	$folder = ROOT_DIR . "/appSpec/{$this->AppName}";
	if($this->createFolder($folder,NULL,self::SpecFolder['appSpec']) === false)
		echo "Create '{$this->AppName}' Spec-Folder\n";
	else echo "'{$this->AppName}' Spec-Folder allready exist.\n";
}
//==============================================================================
// モジュールフォルダツリーの作成、必須
private function ModTree($model) {
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
			$files = $this->get_files($csv_dir,'.csv');
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
			$files = $this->get_files($schema_dir,'.php',false);
			if($files !== false) $files = array_map(function($v) { return str_replace('Schema.php','',$v);},$files);
		}
		return $files;
	}
//==============================================================================
// 仕様CSVからスキーマ・言語リソース生成、省略可
private function GenSchema($model) {
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
// スキーマからモデルクラス作成、必須
private function GenModel($model) {
	$schema = $this->$model;
	$lng_txt = $this->makeResource($schema->Lang);
	$target = "{$this->AppConfig}/Lang/{$model}.lng";
	file_put_contents($target,$lng_txt);
	$schema_txt = $this->makeSchema($schema->FieldSchema,$schema->Lang);
	if(is_array($schema->DataTable)) $table = "['".implode("','",$schema->DataTable)."']";
	else $table = "'{$schema->DataTable}'";
	if(!empty($schema->DataView)) {
		$view = "'DataView' => ['".implode("','",$schema->DataView)."'],";
	} else $view = '';
	$csv_file = (isset($this->InitCSV)) ? "'InitCSV' => '{$this->InitCSV}',":'';
	$rep_array =[
		'%model%' => $model,
		'%handler%' => $schema->Handler,
		'%table%' => $table,
		'%view%' => $view,
		'%primary%' => $schema->Primary,
		'%schema%' => $schema_txt,
		'%csv%' => $csv_file,
	];
	$tmp_file = "Tools/Template/AppModel.php";
	$contents = file_get_contents($tmp_file);          // ファイルから全て読み込む
	$template = str_replace(array_keys($rep_array),array_values($rep_array),$contents);
	$target = "{$this->AppConfig}/Models/{$model}Model.php";
	file_put_contents($target,$template);
	return $model;
}
//==============================================================================
// schemaコマンドとmodelコマンドの連続実行、必須
private function SetupModel($model) {
	$files = $this->get_csv_files($model);
	if($files === false) {
		echo "Cannot Setup '{$model}'.\n";
		return false;
	}
	$models = [];
	foreach($files as $target) {
		echo str_repeat("-", 20)." {$target} ".str_repeat("-", 20)."\n";
		$module = $this->makeModelSchema($target);
		if($module) {
			$models[] = $module;
			$this->GenModel($module);
			$mod_folder = "{$this->AppRoot}/modules/{$module}";
			if(!is_dir($mod_folder)) {
				$this->createFolder("{$this->AppRoot}/modules/{$module}",$module,self::Module);
			}
		}
	}
	$model = implode(', ',$models);
	echo "Setup Success '{$model}' module.\n";
}
//==============================================================================
// スキーマからテーブルとビューを作成、CSVインポート、省略可
private function MakeTable($model) {
	$files = $this->get_schema_files($model);
	// 指定された、見つかったクラスファイルを全て処理する
	$csv_path = "{$this->AppConfig}/InitCSV/";
	foreach($files as $model) {
		$schema = $this->$model;
		$schema->CreateDatabase(true);
		$schema->ImportCSV($csv_path);
		$schema->CreateTableView(true);
	}
}
//==============================================================================
// CSVインポート、省略可
private function ImportCSV($model) {
	$files = $this->get_schema_files($model);
	// 指定された、見つかったクラスファイルを全て処理する
	$csv_path = "{$this->AppConfig}/InitCSV/";
	foreach($files as $model) {
		$schema = $this->$model;
		$schema->ImportCSV($csv_path);
	}
}
//==============================================================================
// スキーマからビューを作成、省略可
private function MakeView($model) {
	$files = $this->get_schema_files($model);
	// 指定された、見つかったクラスファイルを全て処理する
	$csv_path = "{$this->AppConfig}/InitCSV/";
	foreach($files as $model) {
		$schema = $this->$model;
		$schema->CreateTableView(true);
	}
}
//==============================================================================
// フォルダーツリーを作成
private function createFolder($path,$module,$file) {
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
	$columns = [ 'No','名前','フィールド名','言語','タイプ','表示フラグ','CSV','リレーション','メモ'];
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
	foreach($fields as $key => $column) {
		list($name,$field,$lang,$type,$disp,$csv,$rel,$note) = array_values($column);
		$type = strtolower($type);
		list($fname,$sep) = fix_explode('.',$field,2,NULL);
		$resource[$fname] = $name;
		$flag = 0;
        if(preg_match('/([LCR])?([SN]?)(\d+)/',$disp,$m)===1) {
			list($tmp,$align,$sort,$wd) = $m;
			$disp = 0;
			if(isset($DispFlags[$align])) $flag |= $DispFlags[$align];
			if(isset($DispFlags[$sort])) $flag |= $DispFlags[$sort];
		} else list($align,$sort,$wd) = [NULL,NULL,NULL,NULL];
		if(!empty($csv)) $flag |= 00100;
		$langs = empty($lang) ? NULL :explode(';',$lang);
		if(!empty($langs)) $flag |= 01000;
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
				if(is_array($langs)) {
					foreach($langs as $lng) {
						if(is_array($rel_name)) $bstr = [ array_map(function($v) use(&$lng) {
							return "{$v}_{$lng}";
						},$rel_name),NULL,NULL ];
						else $bstr = "{$rel_name}_{$lng}";
						$rels["{$fname}_{$lng}"] = $bstr;
					}
				}
				$col[$id][$link] = $rels;
				break;
		case 'bind':
				list($b1,$b2) = explode("\r\n",$rel);
				if($sep === NULL) $bind = [$b1,$b2];
				else $bind = [$b1, $sep => $b2 ];
				$col[$fname] = [ $type, $flag, $wd, $bind ];
				// self-bind は言語置換されたものをbindするので置換は不要
				if(strpos($rel,'.') != false && is_array($langs)) {
					$flag &= 00700;			// CSVのみ残す
					foreach($langs as $lng) {
						$bstr = array_map(function($v) use(&$lng) {
							return "{$v}_{$lng}";
						},$bind);
						$col["{$fname}_{$lng}"] = [ $type, $flag, NULL, $bstr];
					}
				}
				break;
		case 'virtual':	// 仮想フィールドは言語依存しない
				$col[$fname] = [ $type, $flag, $wd ];
				break;
		default:
				$col[$fname] = [ $type, $flag, $wd];
				if(is_array($langs)) {
					$flag &= 00700;			// CSVのみ残す
					foreach($langs as $lng) {
						$col["{$fname}_{$lng}"] = $type;//[ $type, $flag];
					}
				}
				if(!empty($rel)) {
					$col[$fname][$rel] = [];
				}
		}
	}
	return [$resource,$col];
}
//==============================================================================
// CSV file Load (must be UTF-8)
private function makeSchema($Schema,$lang=NULL,$lang_def=false) {
	$oct_fix = function($dec) {
		if($dec) $dec = substr("0000".decoct($dec),-5);
		return $dec;
	};
	$rel_bind = function($rel) {
		$bind = [];
		foreach($rel as $kk => $vv) {
			$bind[] = (is_int($kk)) ? "'{$vv}'" :"'{$kk}' => '{$vv}'";
		}
		return '[ '.implode(',',$bind).' ]';		// self-bind
	};
	$line = ["'Schema' => ["];
	foreach($Schema as $key=>$defs) {
		list($type,$flag,$wd,$rel) = array_extract($defs,4);
		list($link,$rels) = array_first_item($rel);
		if(is_array($lang) && array_key_exists($key,$lang)) {
			$comm = "\t// {$lang[$key]}";
		} else $comm = '';
		$flag = $oct_fix($flag);
		if(empty($rels)) {
			$wd = ($wd === NULL) ?'':",\t{$wd}";
			if(is_scalar($defs)) $line[] ="\t'{$key}'\t=> '{$type}',{$comm}";
			else $line[] ="\t'{$key}'\t=> [ '{$type}',\t{$flag}{$wd} ],{$comm}";
		} else if(is_int($link)) {
			if($wd === NULL) $wd = 'NULL';
			if(!is_array($rels)) $rels = $rel;	// self-bind
			$bstr = $rel_bind($rels);		
			$val = implode('',array_values($rels));
			if(strpos($val,'.') !== false) $bstr = "\n\t\t\t[ {$bstr} ]\n\t\t"; // view-bind
			$line[] ="\t'{$key}'\t=> [ 'bind',\t{$flag},\t{$wd},{$bstr} ],{$comm}";
		} else {
			if($wd === NULL) $wd = 'NULL';
			$line[] ="\t'{$key}'\t=> [ '{$type}',\t{$flag},\t{$wd},{$comm}";
			$line[] ="\t\t'{$link}'\t=> [\t\t// View-Relation";
			foreach($rels as $kk => $vv) {
				if(is_array($lang) && array_key_exists($kk,$lang)) {
					$comm = "\t// {$lang[$kk]}";
				} else $comm = '';
				if(is_array($vv)) {
					list($name,$flag,$wd) = $vv;
					$wd = ($wd === NULL) ?'':",\t{$wd}";
					$name = (is_array($name)) ? $rel_bind($name):"'{$name}'";	// alias-bind
					if($flag === NULL) {
						$line[] ="\t\t\t'{$kk}'\t=> [ {$name} ],{$comm}";
					}else{
						$flag = $oct_fix($flag);
						$line[] ="\t\t\t'{$kk}'\t=> [ {$name},\t{$flag}{$wd} ],{$comm}";
					}
				} else $line[] ="\t\t\t'{$kk}'\t=> '{$vv}',{$comm}";
			}
			$line[] ="\t\t],";
			$line[] ="\t],";
		}
	}
	$line[] ="],";
	if($lang !== NULL && $lang_def) {
		$line[] = "'Lang' => [";
		foreach($lang as $key=>$defs) {
			$line[] ="\t'{$key}'\t=> '{$defs}',";
		}
		$line[] ="],";
	}
	return implode("\n\t",$line);
}
//==============================================================================
// モデルスキーマ定義ファイルをスキャンする
private function makeModelSchema($csv_file) {
	if(is_file($csv_file)) {
		$database = $this->loadCSV($csv_file);
		unset($this->Model,$this->Handler,$this->DataView);
		$this->SetProperty($database);
		if(empty($this->Model)) {
			list($path,$fname,$ext) = extract_path_file_ext($csv_file);
			list($fname,$opt) = explode('_',$fname);
			$this->Model = ucfirst($fname);
		}
		if(empty($this->Handler)) $this->Handler = HANDLER;
		list($lng,$schema) = $this->createSchema($this->Schema);
		$schema_txt = $this->makeSchema($schema,$lng,true);
		if(is_array($this->DataTable)) $table = "['".implode("','",$this->DataTable)."']";
		else $table = "'{$this->DataTable}'";
		if(!empty($this->DataView)) {
			if(is_scalar($this->DataView)) $this->DataView = [$this->DataView];
			$view = "'DataView' => ['".implode("','",$this->DataView)."'],";
		} else $view = '';
		$rep_array =[
			'%model%' => $this->Model,
			'%handler%' => $this->Handler,
			'%table%' => $table,
			'%view%' => $view,
			'%primary%' => $this->Primary,
			'%schema%' => $schema_txt,
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
private function makeResource($lang) {
	$lng = "Schema => [\n\t.ja => [\n";
	foreach($lang as $key => $val) {
		if(strpos($val,' ')!==false) $val = "'{$val}'";
		else if(strpos($val,'"')!==false) $val = "'{$val}'";
		else if(strpos($val,"'")!==false) $val = "\"{$val}\"";
		$lng = "${lng}\t\t{$key}\t=> {$val}\n";
	}
	$lng = "${lng}\t]\n]\n";
	return $lng;
}


}
