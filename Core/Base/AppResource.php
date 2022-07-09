<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  AppResource:   css, js joined by template
 */
class AppResource extends AppObject {
    const ResourceList = [
        'css' => [
	        'section'	=> 'stylesheet',
            'folder' => 'css',
        ],
        'js' => [
	        'section'	=> 'javascript',
            'folder' => 'js',
        ],
    ];
    const FunctionList = [
        '@'    => [
            'compact'   => [ 'cmd_modeset','do_min' ],
            'comment'   => [ 'cmd_modeset','do_com' ],
            'message'   => [ 'cmd_modeset','do_msg' ],
            'charset'   => [ 'cmd_charset','charset'],
        ],
        '+'    => [
            'import'    => 'cmd_import',
            'section'   => 'cmd_section',
            'jquery'    => 'cmd_jquery',
			'style'    => 'cmd_style',
			'script'   => 'cmd_script',
        ],
        '*'  => 'do_comment',
	];
	private $debug_mode = false;	// for debug
	private $do_min = true;			// compact mode
	private $do_com = false;		// Import Message
	private $do_msg = false;		// Comment Line
	private $charset = '';
	const DebugModeSet = [
		'do_min' => false,		// No Compact
		'do_com' => true,		// Do Import Message
		'do_msg' => true,		// Do Comment Output
	];
//==============================================================================
// Constructor
    function __construct($owner) {
		parent::__construct($owner);
        // Module(res) will be Common URI Modele
		$this->debug_mode = !is_bool_false(MySession::getSysData('debugger'));
    }
//==============================================================================
// フォルダセットアップ
private function set_search_folders($name,$ext,$modname) {
	$appname = App::$AppName;
	switch(substr($name,0,1)) {
	case '!':					// include (app)/res/css/common.css
		$name = substr($name,1);
		$prefix = App::Get_AppRoot('res');
		$Folders = [
			"{$appname}共通" => App::Get_AppPath('View/res'),
			'Libs' => 'Core/Template/res',
		];
		break;
	case '^':					// include Core/Template
		$name = substr($name,1);
		$prefix = '/res/res';
		$Folders = [
			'Libs' => 'Core/Template/res',
		];
		break;
	default:					// include (app)/(module)/css/common.css
		$prefix = App::Get_AppRoot($modname);
		$modname = ucfirst($modname);
		$Folders = [
			"{$modname}固有" => App::Get_AppPath("modules/{$modname}/res"),
			"{$appname}共通" => App::Get_AppPath('View/res'),
			'Libs' => 'Core/Template/res',
		];
		break;
	}
	$path = "{$prefix}/{$ext}/{$name}.{$ext}";
	$templatelist = $this->get_exists_files($Folders,'template.mss');
	return [$path,$name,$templatelist];
}
//==============================================================================
// Search resource Folder, Module, Application, Framework
    private function get_exists_files($Folders,$name) {
        $arr = array();
        foreach($Folders as $key => $file) {
            $fn ="{$file}/{$name}";
            if(is_file($fn)) {
                $arr[$key] = $fn;
            }
        }
        return $arr;
    }
//==============================================================================
// Resource Mode Setup
public function ResourceMode($sec) {
	foreach($sec as $cmd => $mode) {
		if(substr($cmd,0,1)==='@' && is_scalar($mode)) {
			$cmd = substr($cmd,1);
			$funcs = self::FunctionList['@'];
			if(array_key_exists($cmd,$funcs)) {
				list($method,$arg) = $funcs[$cmd];
				if(method_exists($this,$method)) {
					$this->$method($arg,$mode);
				} else debug_log(DBMSG_RESOURCE,['NOT FOUND'=>$method]);
			}
		} else if(substr($cmd,0,1)==='*') {
			$cmd = substr($cmd,1);
		    $this->do_comment(NULL,NULL,NULL,$cmd,$vars);
		}
	}
}
//------------------------------------------------------------------------------
// compact/comment/message Command
// パラメータはプロパティ変数名
private function cmd_modeset($param,$mode) {
	if(substr($mode,0,1) === '@') {
		if($this->debug_mode) {
			$mode = self::DebugModeSet[$param];
		} else $mode = substr($mode,1);
	}
	$mode = !is_bool_false(strtolower($mode));
	$this->$param = $mode;            // 指定プロパティ変数にセット
}
//------------------------------------------------------------------------------
// charset
// パラメータはプロパティ変数名
private function cmd_charset($param,$mode) {
	$this->charset = $mode;            // 指定プロパティ変数にセット
}
//------------------------------------------------------------------------------
// Resource Te,plate Output
//	mod-rewrite
//		/(css|js|images)/(.*)$				vendor/webroot/$1/$2
//		/res/(css|js|images)/(.*)$			Core/Template/webroot/$1/$2
//		/(app)/(css|js|images)/(.*)$		app/$1/webroot/$2/$3
//	dynamic resource
//		/(app)/(module)/(css|js)/(.*)$		resource.(module).(css|js).*
//		/(app)/res/(css|js)/(.*)$			resource.res.(css|js).*
//		/res/res/(css|js)/(.*)$				resource.core.(css|js).*
public function ResourceSection($modname,$fname,$sec,$vars) {
	list($file,$ext) = fix_explode('.',$fname,2);
	$this->FolderInfo = self::ResourceList[$ext];
	list($path,$name,$templatelist) = $this->set_search_folders($file,$ext,$modname);
	$res = [ "{$name}.{$ext}" => $sec];
	array_unshift($templatelist,'Layout');
	$this->importFiles = [];
	$this->charset = '';
	ob_start();
	$this->RunResource($modname,$templatelist,$res,$fname,$vars);
	$resource = ob_get_contents();		// バッファ内容を取り出す
	ob_end_clean();						// バッファを消去
	if(!empty($this->charset)) {
		$resource = "@charset \"{$this->charset}\";\n{$resource}";
 	}
	MySession::resource_SetData($path,$resource);
	return $path;
}
//==============================================================================
// $templist から secname セクションが存在するものを探索する
private function RunResource($modname,$templatelist,$res,$name,$vars) {
	list($res_type,$ext) = array_keys_value($this->FolderInfo,['section','folder']);
	list($pp,$name,$tmplist) = $this->set_search_folders($name,$ext,$modname);
	$msslist = $templatelist;//array_intersect($templatelist,$tmplist);
	if($this->ExecResource($modname,$msslist,$res,$name,$vars) === false) {
		// templatelist からセクションを探索
		while(!empty($msslist)) {
			$template = reset($msslist);
            $parser = new SectionParser($template);
            $resource = array_change_key_case( $parser->getSectionDef(FALSE), CASE_LOWER);
            unset($parser);         // 解放
            if(array_key_exists($res_type,$resource)) {		// css or js section
				$res = $resource[$res_type];
				if($this->ExecResource($modname,$msslist,$res,$name,$vars)) return;
            }
			array_shift($msslist);
        }
		debug_log(DBMSG_RESOURCE,['NOT-FOUND-SECTION'=>$name]);
	}
}
//==============================================================================
// ExecResource:
//	$modname		モジュール名
//	$templist		template.mss のリスト配列
//	$resource		処理中のファイルセクション
//	$name			処理するセクション名
//	$vars			環境変数
private function ExecResource($modname,$templatelist,$resource,$name,$vars) {
	if(!array_key_exists($name,$resource))  return false;
	$mySec = $resource[$name];
	if(is_scalar($mySec)) $mySec = [$mySec];
	foreach($mySec as $key => $val) {
		if(is_int($key)) {
			if(!is_scalar($val)) {    // 単純配列は認めない
				debug_log(DBMSG_RESOURCE,['BAD SECTION'=>$val]);
				continue;
			}
			if($name !== $val) {
				if($this->ExecResource($modname,$templatelist,$resource,$val,$vars)) continue;
			}
			$sublist = array_slice($templatelist,1);
			$this->RunResource($modname,$sublist,[],$val,$vars);
		} else {
			$key = tag_body_name($key);         // 重複回避用の文字を削除
			$top_char = $key[0];
			if(array_key_exists($top_char,self::FunctionList)) {
				$cmd_tag = mb_substr($key,1);      // 先頭文字を削除
				$func = self::FunctionList[$top_char];
				if(is_array($func)) {       // サブコマンドテーブルがある
					if(array_key_exists($cmd_tag,$func)) {
						$def_func = $func[$cmd_tag];
						list($cmd,$param) = is_array($def_func) ? $def_func:[$def_func,''];
						if((method_exists($this, $cmd))) {
							if($top_char === '@') $this->$cmd($param,$val);
							else $this->$cmd($modname,$templatelist, $resource,$val,$vars);
						} else debug_log(DBMSG_RESOURCE,['+++ Method Not Found'=>$cmd]);
					} else debug_log(DBMSG_RESOURCE,['*** In Feature Command...'=>$cmd_tag]);
				} else if(method_exists($this, $func)) {
					$this->$func($modname,$templatelist, $resource,$cmd_tag,$vars);
				} else debug_log(DBMSG_RESOURCE,["Undefined Func CALL:{$func}"=>[$cmd_tag,$val]]);  // 未定義のコマンド
			} else {
				$mod = ucfirst($key);
				$ext = $this->FolderInfo['folder'];
				list($tmp,$name,$sublist) = $this->set_search_folders($val,$ext,$mod);
				$this->RunResource($mod,$sublist,[],$name,$vars);
			}
		}
	}
	return true;
}
//==============================================================================
//  文字列の変数置換を行う
    private function expand_Strings($str,$vars) {
		if(!is_scalar($str)) return $str;
		return expand_text($this,$str,$this->RecData,$vars);
}
//------------------------------------------------------------------------------
// * comment Command
    private function do_comment($modname,$tmplist, $resource,$sec,$vars) {
		if($this->do_com) {         // コメントを出力する
        	$vv = trim($this->expand_Strings($sec,$vars));
        	echo "/* {$vv} */\n";
		}
    }
//------------------------------------------------------------------------------
// +section Command
//
    private function cmd_section($modname,$tmplist, $resource,$sec,$vars) {
		if($this->ExecResource($modname,$tmplist,$resource,$sec,$vars)) return;
		$sublist = array_slice($tmplist,1);
		$this->RunResource($modname,$sublist,[],$sec,$vars);
    }
//------------------------------------------------------------------------------
// +import Command
//		+import => [ ... ] or +import => file or @file
    private function cmd_import($modname,$tmplist, $resource,$sec,$vars) {
		$this->filesImport('',$tmplist, $sec);
    }
//------------------------------------------------------------------------------
// +jquery Command
//		+jquery => [ ... ] or +import => file or @file
    private function cmd_jquery($modname,$tmplist, $resource,$sec,$vars) {
		if($this->FolderInfo['folder']!=='js') return;
		echo "$(function() { ";
		$this->filesImport('jquery-',$tmplist,array_filter($sec,function($v) { return is_scalar($v);}));
		echo "});\n";
		$plugins = array_filter($sec,function($v) { return is_array($v);});
		if(!empty($plugins)) {
			echo "(function ($) {\n";
			foreach($plugins as $subdir => $files) {
				$files = array_map(function($v) use(&$subdir) { return "{$subdir}/{$v}"; },$files);
				$this->filesImport('plugins-',$tmplist,$files);
			}
			echo "})(jQuery);\n";
		}
    }
//------------------------------------------------------------------------------
// ファイルのインポート処理
    private function filesImport($scope,$tmplist, $files) {
		if(empty($files)) return;
		if(is_scalar($files)) $files = [$files];
debug_log(7,['IMPORT-FILE'=>$tmplist,'FILES'=>$files]);
        foreach($files as $key=>$vv) {
            $imported = FALSE;
            if(empty($vv)) {
				$p = '/@(?:\$(.+)|(\w+):(.+))$/';
                if(preg_match($p,$key,$m)) {
					if(count($m)===2) {
		                list($tmp,$id_name) = $m;
						$id = "{$this->ModuleName}.{$id_name}";
						$data = MySession::syslog_GetData($id,TRUE);
                        if($this->do_msg) echo "/* {$scope} import from session '{$id}' */\n";
						$this->outputContents($data);
			            $imported = true;
		                $this->importFiles[] = $vv; // for-DEBUG
						continue;
					} else {
		                list($tmp,$id_name,$flag,$vv) = $m;
						$test_value = MySession::getSysData($flag);
						if(is_bool_false($test_value)) continue;
					}
				}
            }
            if(get_protocol($vv) !== NULL) {    // IMPORT from INTERNET URL
				list($filename,$v_str) = fix_explode(';',$vv,2);
                parse_str($v_str, $vars);
                if($this->do_msg) echo "/* {$scope}import from {$filename} */\n";
                $content = file_get_contents($filename);
                $replace_keys   = array_keys($vars);
                $replace_values = array_values($vars);
                if(!empty($replace_keys)) $content = str_replace($replace_keys,$replace_values, $content);
                echo "{$content}\n";
                $imported = TRUE;
                $this->importFiles[] = $vv; // for-DEBUG
                continue;
            }
			$ext = $this->FolderInfo['folder'];
			list($filename,$v_str) = fix_explode('?',$vv,2);	// クエリ文字列を変数セットとして扱う
            parse_str($v_str, $vars);
            foreach($tmplist as $key => $file) {
                list($path,$tmp) = extract_path_filename($file);
                $fn ="{$path}{$ext}/{$filename}";
	debug_log(7,['IMPORT'=>[$key,$file],'FILE'=>$fn]);
                if(is_file($fn)) {
                	if(!in_array($fn,$this->importFiles)) {
						$this->importFiles[] = $fn; // for-DEBUG
						list($name,$ext) = extract_base_name($fn);
						if($ext === 'php') {        // PHPファイルをインポートする
							$self_name = $this->myname; // 処理中のファイル名を渡す
							$extention = $ext;  		// 拡張子
							require($fn);           	// PHPファイルを読み込む
						} else {
							// @charset を削除して読み込む
							$content = preg_replace('/(@charset.*;)/','/* $1 */',trim(file_get_contents($fn)) );
							// C++ 風コメントを許す
							$content = preg_replace('/\/\/.*$/','',$content);
							$content = $this->expand_Strings($content,$vars);
							if($this->do_msg) echo "/* {$scope}import({$filename}) in {$key} */\n";
							$this->outputContents($content);
						}
					}
                    $imported = TRUE;
					debug_log(7,['**** INCLUDE'=>$fn]);
                    break;
                }
				debug_log(7,['NOT-EXIST'=>$fn]);
            }
            if(!$imported) {
                echo "/* FILE NOT FOUND '{$filename}'@{$fn} */\n";
                debug_log(DBMSG_RESOURCE,['FILE NOT FOUND'=>$filename,'LIST'=>$tmplist]);
            }
        }
    }
//==============================================================================
//    ファイルをコンパクト化して出力
    private function outputContents($content) {
        if($this->do_min) {         // コメント・改行を削除して最小化して出力する
			$content = remove_space_comment_str($content);
        } else if(!$this->do_com) {         // コメントと不要な改行を削除して出力する
			$content = remove_comment_str($content);
        }
        echo "{$content}\n";
    }
//==============================================================================
// Resource Command
//==============================================================================
// +style => [ css-defs ]
	private function cmd_style($modname,$tmplist, $res_sec,$sec,$vars) {
		if($this->FolderInfo['folder']!=='css') return;
		$val = array_to_text($sec);
		// C++ 風コメントをCSSに許す
		$content = preg_replace('/\s*\/\/.*$/','',$val);
		$content = $this->expand_Strings($content,$vars);
		$this->outputContents($content);
	}
//==============================================================================
// +stript => [ javascript & jquery defs ]
	private function cmd_script($modname,$tmplist, $res_sec,$sec,$vars) {
		if($this->FolderInfo['folder']!=='js') return;
		$script = array_filter($sec,function($v) { return is_scalar($v);});
		$jquery = array_filter($sec,function($v) { return is_array($v);});
		if(!empty($jquery)) {
			$script = [$script, '$(function() {',$jquery,'});'];
		}
		$val = array_to_text($script);
		$content = $this->expand_Strings($val,$vars);
		$this->outputContents($content);
	}

}
