<?php
/*
	プロトタイプ・テストクラス
*/
define('ROOT_DIR', __DIR__ . '/../../');
define('IND_DIR', __DIR__ . '/../../Core/');
// デバッグ用のクラス
require_once(IND_DIR . 'AppDebug.php');
require_once(IND_DIR . 'Config/appConfig.php');
require_once(IND_DIR . 'Common/coreLibs.php');
require_once(IND_DIR . 'Common/appLibs.php');
require_once(IND_DIR . 'Common/arrayLibs.php');
// require_once(IND_DIR . 'Common/markdown.php');
// require_once(IND_DIR . 'Class/XParser.php');
// require_once(IND_DIR . 'App.php');
// require_once(IND_DIR . 'Base/AppObject.php');
require_once(IND_DIR . 'Base/LangUI.php');
// require_once(IND_DIR . 'Base/AppView.php');
// require_once(IND_DIR . 'Base/AppHelper.php');
// require_once(IND_DIR . 'Class/session.php');
// require_once(IND_DIR . 'Class/ClassManager.php');
require_once('Core/Handler/DatabaseHandler.php');
require_once('AppBase.php');
require_once('AppSchema.php');
require_once('database.php');

date_default_timezone_set('Asia/Tokyo');

if(!defined('DEFAULT_LANG'))	 define('DEFAULT_LANG', 'ja');				// Language
if(!defined('DEFAULT_REGION'))	 define('DEFAULT_REGION', 'jp');			// Region code

//==============================================================================
// Databas Table Create Class
class MakeDatabase  extends AppBase {
	// アプリケーションツリー構造
	const PathList = [
		'common' 	=> [],
        "Class"		=> [],
        "Config"	=> [
				'Setup' => [],
				'Schema' => [],
				'Proto' => [ 'Models' => [],'Lang' => [] ],
				'config.php',
		],
        "extends"	=> [],
        "Models"	=> [],
        "modules"	=> [
			'Index' => [
				'res' => ['css' => [],'js' => [],'template.mss'],
				'View' => [ 'Layout.tpl' ],
        		"IndexController.php",
		        "IndexHelper.php",
		        "IndexModel.php",
			],
		],
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
	// 初期ファイル
	const Template = [
		"config.php"			=> "<?php\n\n",
		"IndexController.php"	=> "<?php\n\nclass IndexController extends AppController {\n\n\n}\n",
		"IndexHelper.php"		=> "<?php\n\nclass IndexHelper extends AppHelper {\n\n\n}\n",
		"IndexModel.php"		=> "<?php\n\nclass IndexModel extends AppModel {\n\n\n}\n",
		'Layout.tpl'			=> "@Header\n.appWindow=>[\n\n]\n@Footer\n",
	];
//==============================================================================
// アプリケーションツリーを作成
private function CreateFile($path,$file) {
	if(is_scalar($file)) {
		$target = "{$path}/{$file}";
		if(!is_file($target)) {
			if(array_key_exists($file,self::Template)) {
				file_put_contents($target,self::Template[$file]);
			} else {
				touch($target);
			}
			echo "Create({$target})\n";
		}
	} else if(is_array($file)) {
		if(!is_dir($path)) {
			echo "MKDIR({$path})\n";
			mkdir($path,0777,true);
		}
		foreach($file as $key => $val) {
			$dir = (is_int($key)) ? $path : "{$path}/{$key}";
			$this->CreateFile($dir,$val);
		}
	}
}
//==============================================================================
// フォルダ内のファイルを取得する
private function get_files($path,$ext) {
    if(!file_exists ($path)) return false;
    setlocale(LC_ALL,"ja_JP.UTF-8");
    $drc=dir($path);
	$files = [];
    while(false !== ($fl=$drc->read())) {
		if(! in_array($fl,IgnoreFiles,true)) {
			$fullpath = "{$path}/{$fl}";
			$ex = substr($fl,strrpos($fl,'.'));
			if(is_file($fullpath) && ($ex === $ext)) {
				$files[] = $fullpath;
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
		$resource[$field] = $name;
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
					list($field,$sep) = fix_explode('.',$field,2,NULL);
					if($sep === NULL) $rel_name = [$rel_name,$rel_bind];
					else $rel_name = [$rel_name, $sep => $rel_bind ];
				}
				list($link,$rels) =	array_first_item(array_slice($col[$id], 3, 1, true));
				$rels[$field] = [$rel_name, $flag, $wd ];
				if(is_array($langs)) {
					foreach($langs as $lng) {
						if(is_array($rel_name)) $bstr = [ array_map(function($v) use(&$lng) {
							return "{$v}_{$lng}";
						},$rel_name),NULL,NULL ];
						else $bstr = "{$rel_name}_{$lng}";
						$rels["{$field}_{$lng}"] = $bstr;
					}
				}
				$col[$id][$link] = $rels;
				break;
		case 'bind':
				list($b1,$b2) = explode("\r\n",$rel);
				list($name,$sep) = fix_explode('.',$field,2,NULL);
				if($sep === NULL) $bind = [$b1,$b2];
				else $bind = [$b1, $sep => $b2 ];
				$col[$name] = [ $type, $flag, $wd, $bind ];
				// self-bind は言語置換されたものをbindするので置換は不要
				if(strpos($rel,'.') != false && is_array($langs)) {
					$flag &= 00700;			// CSVのみ残す
					foreach($langs as $lng) {
						$bstr = array_map(function($v) use(&$lng) {
							return "{$v}_{$lng}";
						},$bind);
						$col["{$name}_{$lng}"] = [ $type, $flag, NULL, $bstr ];
					}
				}
				break;
		default:
				$col[$field] = [ $type, $flag, $wd];
				if(is_array($langs)) {
					$flag &= 00700;			// CSVのみ残す
					foreach($langs as $lng) {
						$col["{$field}_{$lng}"] = [ $type, $flag];
					}
				}
				if(!empty($rel)) {
					$col[$field][$rel] = [];
				}
		}
	}
	return [$resource,$col];
}
//==============================================================================
// CSV file Load (must be UTF-8)
private function makeSchema($Schema) {
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
		list($type,$flag,$wd) = array_alternative($defs,3);
		list($link,$rels) =	array_first_item(array_slice($defs, 3, 1, true));
		$flag = $oct_fix($flag);
		if(empty($rels)) {
			$wd = ($wd === NULL) ?'':",\t{$wd}";
			$line[] ="\t'{$key}'\t=> [ '{$type}',\t{$flag}{$wd} ],";
		} else if(is_int($link)) {
			if($wd === NULL) $wd = 'NULL';
			$bstr = $rel_bind($rels);		// self-bind
			$val = implode('',array_values($rels));
			if(strpos($val,'.') !== false) $bstr = "\n\t\t\t[ {$bstr} ]\n\t\t"; // view-bind
			$line[] ="\t'{$key}'\t=> [ 'bind',\t{$flag},\t{$wd},{$bstr} ],";
		} else {
			if($wd === NULL) $wd = 'NULL';
			$line[] ="\t'{$key}'\t=> [ '{$type}',\t{$flag},\t{$wd},";
			$line[] ="\t\t'{$link}'\t=> [\t\t// View-Relation";
			foreach($rels as $kk => $vv) {
				if(is_array($vv)) {
					list($name,$flag,$wd) = $vv;
					$wd = ($wd === NULL) ?'':",\t{$wd}";
					$name = (is_array($name)) ? $rel_bind($name):"'{$name}'";	// alias-bind
					if($flag === NULL) {
						$line[] ="\t\t\t'{$kk}'\t=> [ {$name} ],";
					}else{
						$flag = $oct_fix($flag);
						$line[] ="\t\t\t'{$kk}'\t=> [ {$name},\t{$flag}{$wd} ],";
					}
				} else $line[] ="\t\t\t'{$kk}'\t=> '{$vv}',";
			}
			$line[] ="\t\t],";
			$line[] ="\t],";
		}
	}
	$line[] ="],";
	return implode("\n\t",$line);
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
// フォルダ内のスキーマ定義ファイルをスキャンする
private function GenSchema($path,$targetdir,$langdir) {
	$files = $this->get_files($path,'.csv');
	if($files === false) return;
	foreach($files as $target) {
		$database = $this->loadCSV($target);
		$this->SetProperty($database);
		list($lng,$col) = $this->createSchema($this->Schema);
		$lng_txt = $this->makeResource($lng);
		file_put_contents("{$langdir}{$this->Model}.lng",$lng_txt);
		$schema_txt = $this->makeSchema($col);
		if(is_array($this->DataTable)) $table = "['".implode("','",$this->DataTable)."']";
		else $table = "'{$this->DataTable}'";
		if(!empty($this->DataView)) {
			if(is_scalar($this->DataView)) $this->DataView = [$this->DataView];
			$view = "'DataView' => ['".implode("','",$this->DataView)."'],";
		} else $view = '';
		$header = <<<EOT
<?php
//==============================================================================
// Databas Table Create Class
class {$this->Model}Schema extends AppSchema {
  static \$DatabaseSchema = [
	'Handler' => '{$this->Handler}',
	'DataTable' => {$table},
	{$view}
	'Primary' => '{$this->Primary}',
	'Lang_Alternate' => TRUE,
	{$schema_txt}
  ];
}

EOT;
		file_put_contents("{$targetdir}{$this->Model}Schema.php",$header);
		echo $header;
	}
}
//==============================================================================
// フォルダ内のスキーマ定義ファイルをスキャンする
private function GenModel($path,$target,$model) {
echo "Model($model)\n";
	$this->$model->SchemaSetup();
}
//==============================================================================
// Execute Create TABLE,VIEW, and INTIAL DATA
//	before check Dependent Table
public function execute($cmd,$appname,$model) {
	$path = realpath(ROOT_DIR . "app/{$appname}");
	$config = "{$path}/Config";
	switch($cmd) {
	case 'create':
		$this->CreateFile($path,self::PathList);
		break;
	case 'setup':
		echo "GENERATE: {$path}\n";
		$this->loadCSV($config);
		break;
	case 'schema':
		$this->GenSchema("{$config}/Setup","{$config}/Schema/","{$config}/Proto/Lang/");
		break;
	case 'model':
		$this->GenModel("{$config}/Schema","{$config}/Proto/Models/",$model);
		break;
	default:
		echo "BAD COMMAND($cmd)\n";
    }
}

}
//==============================================================================
// Execute Create TABLE,VIEW, and INTIAL DATA

$ln = str_repeat("=", 50);
print_r($argv);
echo "{$ln} START HERE ${ln}\n";

list($self,$cmd,$appname,$model) = array_alternative($argv,4);

SetupLoader::Setup($appname);

$config->Setup(GlobalConfig,'development');

$pawmoo = new MakeDatabase();
$pawmoo->execute($cmd,$appname,$model);

