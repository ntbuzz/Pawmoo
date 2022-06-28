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
            'charset'   => [ 'cmd_modeset','charset'],
        ],
        '+'    => [
            'import'    => 'cmd_import',
            'section'   => 'cmd_section',
            'jquery'    => 'cmd_jquery',
            'style'    => 'cmd_style',
        ],
        '*'  => 'do_comment',
	];
    const ResourceFunctions = [
		'import'   => 'res_import',
		'section'  => 'res_section',
		'jquery'   => 'res_jquery',
		'style'    => 'res_style',
		'script'   => 'res_script0',
    ];
	private $debug_mode = false;	// for debug
	private $do_min = false;		// no-compact
	private $do_com = true;			// echo Import Message
	private $do_msg = true;			// echo Comment Line
	private $charset = 'UTF-8';
	const DebugModeSet = [
            'do_min' => false,		// Is Compact Output?
            'do_com' => true,		// Is Import Message?
            'do_msg' => true,		// Is Comment Output?
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
		$prefix = ':res';
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
		$prefix = ":{$modname}";
		$Folders = [
			"{$modname}固有" => App::Get_AppPath("modules/{$modname}/res"),
			"{$appname}共通" => App::Get_AppPath('View/res'),
			'Libs' => 'Core/Template/res',
		];
		break;
	}
	$path = "{$prefix}/{$ext}/{$name}.{$ext}";
	$templatelist = $this->get_exists_files($Folders,'template.mss');
	return [$path,$templatelist];
}
//==============================================================================
// Search resource Folder, Module, Application, Framework
    private function get_exists_files($Folders,$name) {
        $arr = array();
        foreach($Folders as $key => $file) {
            $fn ="{$file}/{$name}";
            if(file_exists($fn)) {
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
			if(!array_key_exists($cmd,$funcs)) {
				$method = $funcs[$cmd];
				if(is_array($method)) {
					list($method,$arg) = $method;
				} else $arg = '';
				if(method_exists($this,$method)) {
					$this->$method($arg,$mode);
				}
			}
		}
	}
}
//------------------------------------------------------------------------------
// charset/compact/comment/message Command
// パラメータはプロパティ変数名
    private function cmd_modeset($param,$mode) {
        $mode = !is_bool_false(strtolower($mode));
		if(substr($param,0,1) === '@' && $this->debug_mode) {
			$param = substr($param,1);
			$mode = self::DebugModeSet[$param];
		}
        $this->$param = $mode;            // 指定プロパティ変数にセット
    }
//------------------------------------------------------------------------------
// Resource Te,plate Output
//	mod-rewrite
//		/(css|js|images)/(.*)$				vendor/webroot/$1/$2
//		/res/(css|js|images)/(.*)$			Core/Template/webroot/$1/$2
//		/(app)/(css|js|images)/(.*)$		app/$1/webroot/$2/$3
//	dynamic resource
//		/(app)/(module)/(css|js)/(.*)$		resource,(module).(css|js).*
//		/(app)/res/(css|js)/(.*)$			resource,res.(css|js).*
//		/res/res/(css|js)/(.*)$				resource,core.(css|js).*
public function ResourceSection($modname,$fname,$defs,$vars) {
	if(is_scalar($defs)) $defs = [$defs];
	list($file,$ext) = fix_explode('.',$fname,2);
	list($path,$templatelist) = $this->set_search_folders($file,$ext,$modname);
	debug_log(7,['FILE'=>$fname,'DEF'=>$defs,'PATH'=>$path]);
	$this->FolderInfo = self::ResourceList[$ext];
	$this->FolderInfo['template_dir'] = $templatelist;
	ob_start();
	foreach($defs as $id => $val) {
		if(is_int($id)) {
			$this->Template($templatelist,$val,$vars);
		} else {
			$id = tag_body_name($id);			// 重複回避用の識別子を除去
			$top_char = mb_substr($id,0,1);
			$cmd = mb_substr($id,1);
			$funcs = self::ResourceFunctions;
			switch($top_char) {
			case '*': echo "/* {$cmd} */\n"; break;		// コメント出力
			case '@':		// インポートコマンドに読替え
					$val = $cmd;
					$cmd = 'import';
			case '+':		// コマンド
					if(array_key_exists($cmd,$funcs)) {
						$method = $funcs[$cmd];
						if(method_exists($this,$method)) {
							$this->$method($val,$vars);
						} else echo "Method({$method}): NOT IMPLEMENTED\n";
					} else echo "ERROR: NOT FOUND CMD({$id})\n";
					break;
			default:
					$mod = ucfirst($id);
					list($tmp,$sublist) = $this->set_search_folders($file,$ext,$mod);
					$this->Template($sublist,$val,$vars);
			}
		}
	}
	$resource = ob_get_contents();		// バッファ内容を取り出す
	ob_end_clean();						// バッファを消去
	debug_log(8,['RESOURCE'=>$$resource]);
	return $path;
}
//==============================================================================
//  文字列の変数置換を行う
    private function expand_Strings($str,$vars) {
		if(!is_scalar($str)) return $str;
		return expand_text($this,$str,$this->RecData,$vars);
}
//------------------------------------------------------------------------------
// section Command
// +section => [ files , ... ] or section => scalar
    private function cmd_section($secParam, $param,$sec,$vars) {
		debug_dump(['TEMPLATE-SECTION'=>$sec,'VARS'=>$vars,'TEMPLATE'=>$this->FolderInfo]);
    }
//==============================================================================
// Style Template Output
	private function Template($tmplist,$secname,$vars) {
		$secType = $this->FolderInfo['section'];
        foreach($tmplist as $category => $file) {
            $parser = new SectionParser($file);
            $SecTemplate = array_change_key_case( $parser->getSectionDef(FALSE), CASE_LOWER);
            unset($parser);         // 解放
            if(array_key_exists($secType,$SecTemplate)) {
                $secData = $SecTemplate[$secType];
                $secParam = array($secname,$secData,$tmplist);
                if(array_key_exists($secname,$secData)) {
					foreach($secData[$secname] as $key => $sec) {
	                    $this->function_Dispath($secParam, $key, $sec, $vars);
					}
                    return TRUE;
                }
            }
        }
        return FALSE;
	}
//==============================================================================
// key 文字列を元に処理関数へディスパッチする
// key => sec (vars)
    private function function_Dispath($secParam, $key,$sec,$vars) {
        if(is_numeric($key)) {
            if(!is_scalar($sec)) return FALSE;    // 単純配列は認めない
            $key = $sec;
        } else $key = tag_body_name($key);         // 重複回避用の文字を削除
        $top_char = $key[0];
        if(array_key_exists($top_char,self::FunctionList)) {
            $tag = mb_substr($key,1);      // 先頭文字を削除
            $func = self::FunctionList[$top_char];
            if(is_array($func)) {       // サブコマンドテーブルがある
				$dbg_tag = (substr($tag,0,1) === '@');
				$cmd_tag = ($dbg_tag) ? substr($tag,1):$tag;
                if(array_key_exists($cmd_tag,$func)) {
                    $def_func = $func[$cmd_tag];
                    // 配列ならパラメータ要素を取出す
                    list($cmd,$param) = (is_array($def_func)) ? $def_func:[$def_func,''];
					if($dbg_tag) $param = "@{$param}";
                    if((method_exists($this, $cmd))) {
                        $this->$cmd($secParam,$param,$sec,$vars);
                    } else debug_log(DBMSG_RESOURCE,['+++ Method Not Found'=>$cmd]);
                } else debug_log(DBMSG_RESOURCE,['*** In Feature Command...'=>$tag]);
            } else if(method_exists($this, $func)) {
                $this->$func($tag,$sec,$vars);        // ダイレクトコマンド
            } else debug_log(DBMSG_RESOURCE,["Undefined Func CALL:{$func}"=>[$tag,$sec]]);  // 未定義のコマンド
            return TRUE;    // コマンド処理を実行
        } else {
            return FALSE;   // コマンド処理ではない
        }
    }
//------------------------------------------------------------------------------
// ファイルのインポート処理
    private function filesImport($scope,$tmplist, $files) {
		if(is_scalar($files)) $files = [$files];
        foreach($files as $key=>$vv) {
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
                continue;
            }
			list($filename,$v_str) = fix_explode('?',$vv,2);	// クエリ文字列を変数セットとして扱う
            parse_str($v_str, $vars);
            $vars = is_array($vars) ? array_merge($this->repVARS,$vars) : $this->repVARS;
            $imported = FALSE;
			$ext = $this->FolderInfo['folder'];
            foreach($tmplist as $key => $file) {
                list($path,$tmp) = extract_path_filename($file);
                $fn ="{$path}{$this->Filetype}/{$filename}";
                if(file_exists($fn)) {
                    list($name,$ext) = extract_base_name($fn);
                    if($ext === 'php') {        // PHPファイルをインポートする
                        $self_name = $this->myname; // 処理中のファイル名を渡す
                        $extention = $this->Template['extention'];  // 拡張子
                        require($fn);           // PHPファイルを読み込む
                    } else {
                        // @charset を削除して読み込む
                        $content = preg_replace('/(@charset.*;)/','/* $1 */',trim(file_get_contents($fn)) );
						// C++ 風コメントを許す
                        $content = preg_replace('/\/\/.*$/','',$content);
                        $content = $this->expand_Strings($content,$vars);
                        if($this->do_msg) echo "/* {$scope}import({$filename}) in {$key} */\n";
                        $this->outputContents($content);
                    }
                    $imported = TRUE;
                    array_push($this->importFiles,$fn); // for-DEBUG
                    break;
                }
            }
            if(!$imported) {
                echo "/* NOT FOUND {$filename} */\n";
                debug_log(DBMSG_RESOURCE,['LIST'=>$tmplist,'NOT FOUND'=>$filename]);
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
	private function res_style($val, $vars) {
		$val = array_to_text($val);
		// C++ 風コメントを許す
		$content = preg_replace('/\/\/.*$/','',$val);
		$content = $this->expand_Strings($content,$vars);
		$this->outputContents($content);
	}

//==============================================================================
// +stript => [ javascript & jquery defs ]
	private function res_script($sec, $vars) {
		$script = array_filter($sec,function($v) { return is_scalar($v);});
		$jquery = array_filter($sec,function($v) { return is_array($v);});
		if(!empty($jquery)) {
			$script = [$script, '$(function() {',$jquery,'});'];
		}
		$val = array_to_text($script);
		$content = $this->expand_Strings($content,$vars);
		$this->outputContents($content);
	}
//------------------------------------------------------------------------------
// Import Command
// +import => [ files , ... ] or import => scalar or @import-file
    private function res_import($sec, $vars) {
		debug_dump(['IMPORT'=>$sec,'VARS'=>$vars,'TEMPLATE'=>$this->FolderInfo],false);
    }

}
