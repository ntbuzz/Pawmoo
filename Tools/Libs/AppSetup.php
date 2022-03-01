<?php
//==============================================================================
// Databas Table Create Class
class AppSetup  extends AppBase {
	// アプリケーションツリー構造
	const PathList = [
		'common' 	=> [],
        "Class"		=> [],
        "Config"	=> [
				'Setup' => [],
				'Schema' => [],
				'Proto' => [ 'Models' => [],'Lang' => [] ],
				'InitCSV' => [],
				'config.php',
		],
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
//==============================================================================
//	constructor( object owner )
function __construct($appname) {
	$approot = ROOT_DIR . "/app/";
	$this->AppName = $appname;
	$this->AppRoot = "{$approot}{$appname}";
	$this->AppConfig = "{$this->AppRoot}/Config";
}
//==============================================================================
// Execute Create TABLE,VIEW, and INTIAL DATA
//	before check Dependent Table
public function execute($cmd,$model) {
	if(!empty($model)) $model = ucfirst(strtolower($model));
	switch($cmd) {
	case 'create':	// アプリケーションフォルダツリー作成
		if($this->CreateFile($this->AppRoot,NULL,self::PathList) === false)
			echo "Create '{$this->AppName}'\n";
		else echo "Application '{$this->AppName}' allready exist.\n";
	case 'module':	// アプリケーションモジュールフォルダ作成
		if(empty($model)) break;
		if($this->CreateFile("{$this->AppRoot}/modules/{$model}",$model,self::Module) === false)
			echo "Create Module '{$model}'\n";
		else echo "Module '{$model}' allready exist.\n";
		break;
	case 'schema':	// CSVからスキーマファイルを生成
		$model = $this->GenSchema($model);
		if($model === false) echo "Cannot Generated Schema.\n";
		else echo "Module '{$model}' Schema Generated.\n";
		break;
	case 'model':	// スキーマからモデルクラスと言語リソース生成
		if(empty($model)) break;
		$this->MakeModel($model);
		break;
	case 'setup':	// CSVからスキーマ生成〜モデルクラス〜モジュールフォルダの生成
		$model = $this->makeModule($model);
		if($model === false) echo "Cannot Module Setup.\n";
		else echo "Setup '{$model}' module.\n";
		break;
	case 'database':	//スキーマファイルからテーブルとビューを作成
		$this->MakeDatabase($model);
		break;
	case 'view':	//スキーマファイルからテーブルとビューを作成
		$this->MakeTableView($model);
		break;
	default:
		echo "BAD COMMAND($cmd)\n";
    }
}
//==============================================================================
// アプリケーションツリーを作成
private function CreateFile($path,$module,$file) {
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
			if($this->CreateFile($dir,$module,$val)==false) $exist = false;

		}
	}
	return 	$exist;
}
//==============================================================================
// フォルダ内のファイルを取得する
private function get_files($path,$ext,$full=true) {
    if(!file_exists ($path)) return false;
    setlocale(LC_ALL,"ja_JP.UTF-8");
    $drc=dir($path);
	$files = [];
    while(false !== ($fl=$drc->read())) {
		if(! in_array($fl,IgnoreFiles,true)) {
			$fullpath = "{$path}{$fl}";
			$ex = substr($fl,strrpos($fl,'.'));
			if(is_file($fullpath) && ($ex === $ext)) {
				$files[] = ($full) ? $fullpath : $fl;
			}
		}
     }
     $drc->close();
	return $files;
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
		'L' => 00010,
		'C' => 00020,
		'R' => 00030,
		'S' => 00002,
	];
	$col = [];
	$resource = [];
	foreach($fields as $key => $column) {
		list($name,$field,$lang,$type,$disp,$csv,$rel,$note) = array_values($column);
		$type = strtolower($type);
		list($fname,$sep) = fix_explode('.',$field,2,NULL);
		$resource[$fname] = $name;
		$flag = 0;
        if(preg_match('/([LCR])?(S?)(\d+)/',$disp,$m)===1) {
			list($tmp,$align,$sort,$wd) = $m;
			$disp = 0;
			if(isset($DispFlags[$align])) $flag |= $DispFlags[$align];
			if(isset($DispFlags[$sort])) $flag |= $DispFlags[$sort];
		} else list($$align,$sort,$wd) = [NULL,NULL,NULL,NULL];
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
private function MakeModelSchema($csv_file) {
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
//==============================================================================
// モデルスキーマ定義ファイルからクラスファイルと言語リソースを生成する
private function MakeModel($model) {
	if(empty($model)) return false;
	$schema = $this->$model;
	$lng_txt = $this->makeResource($schema->Lang);
	$target = "{$this->AppConfig}/Proto/Lang/{$model}.lng";
	file_put_contents($target,$lng_txt);
	$schema_txt = $this->makeSchema($schema->FieldSchema,$schema->Lang);
	if(is_array($schema->DataTable)) $table = "['".implode("','",$schema->DataTable)."']";
	else $table = "'{$schema->DataTable}'";
	if(!empty($schema->DataView)) {
		$view = "'DataView' => ['".implode("','",$schema->DataView)."'],";
	} else $view = '';
	$rep_array =[
		'%model%' => $model,
		'%handler%' => $schema->Handler,
		'%table%' => $table,
		'%view%' => $view,
		'%primary%' => $schema->Primary,
		'%schema%' => $schema_txt,
	];
	$tmp_file = "Tools/Template/AppModel.php";
	$contents = file_get_contents($tmp_file);          // ファイルから全て読み込む
	$template = str_replace(array_keys($rep_array),array_values($rep_array),$contents);
	$target = "{$this->AppConfig}/Proto/Models/{$model}Model.php";
	file_put_contents($target,$template);
	return $model;
}
//==============================================================================
// モデルスキーマ定義ファイル(CSV)からスキーマ、モデルクラス、言語リソースを生成
private function makeModule($model) {
	if($model) {
		$model = strtolower($model);
		$csv_file = "{$this->AppConfig}/Setup/{$model}_def.csv";
	    if(!file_exists($csv_file)) return false;
		$files = [$csv_file];
	} else {
		$csv_dir = "{$this->AppConfig}/Setup/";
		$files = $this->get_files($csv_dir,'.csv');
		if($files === false) return false;
	}
	$models = [];
	foreach($files as $target) {
echo str_repeat("-", 20)." {$target} ".str_repeat("-", 20)."\n";
		$module = $this->MakeModelSchema($target);
		if($module) {
			$models[] = $module;
			$this->MakeModel($module);
			$mod_folder = "{$this->AppRoot}/modules/{$module}";
			if(!is_dir($mod_folder)) {
				$this->CreateFile("{$this->AppRoot}/modules/{$module}",$module,self::Module);
			}
		}
	}
	return implode(', ',$models);
}
//==============================================================================
// フォルダ内のスキーマ定義ファイル(CSV)をスキャンする
private function GenSchema($model) {
	if($model) {
		$model = strtolower($model);
		$csv_file = "{$this->AppConfig}/Setup/{$model}_def.csv";
	    if(!file_exists($csv_file)) return false;
		$files = [$csv_file];
	} else {
		$csv_dir = "{$this->AppConfig}/Setup/";
		$files = $this->get_files($csv_dir,'.csv');
		if($files === false) return false;
	}
	$models = [];
	foreach($files as $target) {
		$models[] = $this->MakeModelSchema($target);
	}
	return implode(', ',$models);
}
//==============================================================================
// スキーマ定義ファイルからデータベースを作成
private function MakeDatabase($model) {
	if(empty($model)) {
		$schema_dir = "{$this->AppConfig}/Schema/";
		$files = $this->get_files($schema_dir,'.php',false);
		if($files === false) return;
		$files = array_map(function($v) { return str_replace('Schema.php','',$v);},$files);
	} else $files = [$model];
	// 指定された、見つかったクラスファイルを全て処理する
	foreach($files as $model) {
		$schema = $this->$model;
		$schema->CreateDatabase(true);
		$schema->CreateTableView(true);
	}
}
//==============================================================================
// スキーマ定義ファイルからビューを作成
private function MakeTableView($model) {
	if(empty($model)) {
		$schema_dir = "{$this->AppConfig}/Schema/";
		$files = $this->get_files($schema_dir,'.php',false);
		if($files === false) return;
		$files = array_map(function($v) { return str_replace('Schema.php','',$v);},$files);
	} else $files = [$model];
	// 指定された、見つかったクラスファイルを全て処理する
	foreach($files as $model) {
		$schema = $this->$model;
		$schema->CreateTableView(true);
	}
}

}
