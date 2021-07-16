<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  LangUI:  言語ファイルを操作するstaticクラス
 */
//==============================================================================
// 言語ファイルの操作クラス
class LangUI {
    public  static $STRINGS;        // 翻訳言語配列
    public  static $LocaleName;       // ロケール名
    private static $LangDir;        // 言語ファイルのパス
    private static $Locale;         // 言語識別子
    private static $LocaleFiles;    // for debug
    private static $controllers;     // コントローラー

//==============================================================================
// HTTP_ACCEPT_LANGUAGE を元にデフォルトの言語を決定する
    public static function construct($lang,$default,$initfiles) {
        // アプリケーションの言語リソースパス
        static::$LangDir = $default;      // App::Get_AppPath("View/lang/");
        static::$controllers = $initfiles;  // 初期ロードする言語
        self::SwitchLangs($lang);
    }
//==============================================================================
//  言語ファイルの切替え
public static function SwitchLangs($newlang) {
    $default = static::$LangDir;      // ロード先を保存
    $arr = array_unique(             // 重複行を取り除く
        array_filter(           // strlen を使って空行を取り除く
            array_map(          // 各要素に有効識別子の取り出し関数を適用
                function($a) {
                    if(($n=strpos($a,'-')) !== FALSE)       return substr($a,0,$n);     // en-US => en
                    else if(($n=strpos($a,';')) !== FALSE)  return substr($a,0,$n);     // en;q=0.9 => en
                    else return $a;
                },
                explode(',', $newlang)  // 言語受け入れリスト
            ),
            'strlen'));
    $langs = array_shift($arr);             // strict回避
    static::$Locale = ".{$langs}";            // 言語識別文字を付加
    static::$LocaleName = $langs;
    static::$STRINGS = [];
    // ディレクトリが NULL ならログ処理の最中
    if($default !== NULL) {
        log_reset(DBMSG_LOCALE);
        debug_log(DBMSG_LOCALE,["言語リスト" => $newlang]);
    }
    // フレームワークの言語リソースを読込む
    self::LangFiles('Core/Template/lang/','core');
    // アプリケーションの言語リソースパス
    self::LangFiles($default,static::$controllers);
}
//==============================================================================
//  言語ファイルの読み込み
private static function LangFiles($folder,$files) {
	static::$LangDir = $folder;
    static::$LocaleFiles = $files;
    if($files === NULL) return;
	if(is_scalar($files)) $files = [$files];	// 配列へ変換
    foreach($files as $lang_file) {
		self::LoadLang($folder,$lang_file);
    }
    self::LangDebug();
}
//==============================================================================
//  言語ファイルの読み込み
public static function LangDebug() {
    debug_log(DBMSG_LOCALE, [
		"#LocalInfo" => [
			'Locale' => static::$Locale,
			'File'   => static::$LocaleFiles,
			'Folder' => static::$LangDir,
			'STRING' => static::$STRINGS,
		]]);
    }
//==============================================================================
//  セクション要素から空配列を削除する
    private static function emptyDelete($arr) {
        foreach($arr as $key => $val) {
            if(is_array($val)) {
                $val = self::emptyDelete($val);        // 子要素の配列を整理する
                if(empty($val)) unset($arr[$key]);      // 空配列なら削除
            }
        }
        return $arr;
    }
//==============================================================================
//  言語ファイルの読み込み
    private static function LoadLang($folder,$lang_file) {
        if(empty($lang_file)) return;
        $is_global = ($lang_file[0] == '#');
        if($is_global) {
            $lang_file = mb_substr($lang_file,1);
        }
        if(isset(static::$STRINGS[$lang_file])) return TRUE;     // 連想キーが定義済なら処理しない
        $fullpath = "{$folder}{$lang_file}.lng";
		$lang_recursive = function($sec) use(&$lang_recursive) {
			if(is_scalar($sec)) return $sec;
			$new_sec = [];
			foreach($sec as $key => $val) {
				if($key[0] === '.') {
					if($key === static::$Locale) {			// 言語定義
						$vv = $lang_recursive($val);
						if(is_scalar($vv)) $new_sec[] = $vv;
						else $new_sec = array_override_recursive($new_sec,$vv);
					}
				} else {
					$new_sec[$key] = $lang_recursive($val);
				}
			}
			return $new_sec;
		};
        if(file_exists($fullpath)) {
            $parser = new SectionParser($fullpath);
            $section = $parser->getSectionDef(false);
            $import = [];           // インポートリスト
            $values = [];           // ロケール定義
            foreach($section as $key => $val) {
				switch($key[0]) {
				case '@': $import[] = mb_substr($key,1); break;
				case '#':
					$kk = mb_substr($key,1);
					$arr = (isset(static::$STRINGS[$kk])) ? static::$STRINGS[$kk] : [];
					static::$STRINGS[$kk] = array_override_recursive($arr,$lang_recursive($val));
					break;
				case '.':
                    if($key === static::$Locale) {         // モジュール名の直下に定義された言語
						$values = array_override_recursive($values,$lang_recursive($val));   // 最上位にマージ
                    }
					break;
				default:
					$values[$key] = (is_scalar($val)) ? $val : $lang_recursive($val);
				}
            }
            $values = self::emptyDelete($values);      // 空の要素を削除する
            if($is_global) {        // ファイル名がグローバル宣言ならトップレベルにマージする
                static::$STRINGS = array_override_recursive(static::$STRINGS,$values);   // 同じIDは上書き
            } else {
                static::$STRINGS[$lang_file] = $values;       // ファイル名をキーに言語配列
            }
            // インポート宣言されたファイルを読み込む
            foreach($import as $val) {                      // インポートリストの読み込み処理
                if(! isset(static::$STRINGS[$val])) {          // ループ回避のため未定義のものだけ処理する
                    self::LoadLang($folder,$val);                  // 再帰呼出
                }
            }
            unset($parser);
            unset($section);
            return TRUE;
        } else {
            debug_log(DBMSG_LOCALE,"UNDEFINED : {$lang_file}.lng");
        }
        return FALSE;
    }
//==============================================================================
// ネストされた配列変数を取得する、要素名はピリオドで連結したキー名
//  ex. Menu.File.OPEN  大文字小文字は区別される
public static function get_value($mod, $id, $allow = FALSE) {
    //-----------------------------------------
    // 無名関数を定義して配列内の探索を行う
    $array_finder = function ($lst, $arr, $allow) {
            foreach($lst as $val) {
                if(is_array($arr) && array_key_exists($val, $arr)) {
                    $val = str_replace(['　',' '],'',$val);
                    $arr = $arr[$val];
                } else return FALSE;        // 見つからなかった時
            }
            if(is_array($arr)) {          // 見つけた要素が配列なら
                return ($allow) ? $arr :    // 配列を要求されていれば配列で返す
                    ( (isset($arr[0])) ? $arr[0] :     // 0番目の要素があれば値を返す
                                        FALSE);         // そうでなければエラー
            }
            return $arr;        // スカラー値はそのまま返す
        };
    //-----------------------------------------
    if($id[0] === '.') {        // 相対検索ならモジュール名を使う
        $lst = explode('.', "{$mod}{$id}");
        if( ($a=$array_finder($lst,static::$STRINGS,$allow)) !== FALSE) {
            return $a;
        }
        array_shift($lst);      // 先頭のモジュール名要素を消す
    } else $lst = explode('.', $id);    // 絶対検索
    if( ($a=$array_finder($lst,static::$STRINGS,$allow)) !== FALSE) {     // 
        return $a;
    }
    return array_pop($lst);     // 見つからなければ識別子の末尾要素を返す
}
//==============================================================================
// ネストされた配列変数を取得する、要素名はピリオドで連結したキー名
//  ex. Menu.File.OPEN  大文字小文字は区別される
public static function get_array($arr, $mod, $var) {
    $element = self::get_value($mod,$var);
    return $arr[$element];
}

}
