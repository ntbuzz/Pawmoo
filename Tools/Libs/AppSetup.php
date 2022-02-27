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
		'config.php'		=> 'config.php',
		'*Controller.php'	=> 'Controller.php',
		'*Helper.php'		=> 'Helper.php',
		'*Model.php'		=> 'Model.php',
		'Layout.tpl'		=> 'Layout.tpl',
		'mystyle.css'		=> 'mystyle.css',
		'myscript.js'		=> 'myscript.js',
		'template.mss'		=> 'template.mss',
	];

//==============================================================================
// アプリケーションツリーを作成
private function CreateFile($path,$module,$file) {
	if(is_scalar($file)) {
		$modfile = (substr($file,0,1)==='*') ? $module.substr($file,1) : $file;
		$target = "{$path}/{$modfile}";
		if(!is_file($target)) {
			if(array_key_exists($file,self::Template)) {
				$tmp_file = self::Template[$file];
				$tmp_file = "Tools/Template/".self::Template[$file];
		        $contents = file_get_contents($tmp_file);          // ファイルから全て読み込む
				$template = str_replace('%module%',$module,$contents);
				file_put_contents($target,$template);
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
			$this->CreateFile($dir,$module,$val);
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
xdebug_dump(['Make'=>$Schema]);
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
		$schema_txt = $this->makeSchema($col,$lng,true);
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
		file_put_contents("{$targetdir}{$this->Model}Schema.php",$template);
		echo $template;
	}
}
//==============================================================================
// フォルダ内のスキーマ定義ファイルをスキャンする
private function GenModel($path,$target,$model) {
	if(empty($model)) return;
	$schema = $this->$model;
	$schema->SchemaSetup();
	$lng_txt = $this->makeResource($schema->Lang);
	file_put_contents("{$target}Lang/{$model}.lng",$lng_txt);
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
	file_put_contents("{$target}Models/{$model}Model.php",$template);
	echo $template;
}
//==============================================================================
// Execute Create TABLE,VIEW, and INTIAL DATA
//	before check Dependent Table
public function execute($cmd,$appname,$model) {
	$path = realpath(ROOT_DIR . "app/{$appname}");
	$config = "{$path}/Config";
	if(!empty($model)) $model = ucfirst(strtolower($model));
	switch($cmd) {
	case 'create':
		$this->CreateFile($path,NULL,self::PathList);
	case 'module':
		if(empty($model)) break;
		$this->CreateFile("{$path}/modules/{$model}",$model,self::Module);
		break;
	case 'setup':
		echo "GENERATE: {$path}\n";
		break;
	case 'schema':
		$this->GenSchema("{$config}/Setup","{$config}/Schema/","{$config}/Proto/Lang/");
		break;
	case 'model':
		$this->GenModel("{$config}/Schema","{$config}/Proto/",$model);
		break;
	default:
		echo "BAD COMMAND($cmd)\n";
    }
}

}
