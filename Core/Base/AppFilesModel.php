<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  AppFilesModel: ファイル構造をデータベース代わりにアクセスするモデル
 *  これは暫定実装で、最終的には fileclass をベースにする
 */

class AppFilesModel extends AppObject {
    protected static $DatabaseSchema = [];
    public $TopFolder;		// フォルダトップ
    public $SubFolder;		// 付加文字列
    private $Folders;       // フォルダ操作クラス
//==============================================================================
// コンストラクタでフォルダ内を探索
    function __construct($owner) {
        // 継承元クラスのコンストラクターを呼ぶ
	    parent::__construct($owner);
        $this->setProperty(static::$DatabaseSchema);
        $this->__InitClass();                       // クラス固有の初期化メソッド
    }
//==============================================================================
// クラス変数の初期化(override)
    protected function __InitClass() {
        $this->Folders = new FlatFolder();
        parent::__InitClass();                    // 継承元クラスのメソッドを呼ぶ
    }
//==============================================================================
// 指定フォルダを変更する
    public function SelectFolder($home) {
        $this->ActiveHome = $home;
        $this->TopFolder = $this->Home[$home] . $this->SubFolder;
//        if(!file_exists($this->TopFolder)) mkdir($this->TopFolder);
    }
//==============================================================================
// 指定フォルダの一覧
    public function GetSubFolder($home) {
        $this->TopFolder = $this->Home[$home] . $this->SubFolder;
        if(! $this->get_FolderLists($this->TopFolder)) {
            // フォルダが無ければ作成する
//            mkdir($this->TopFolder);
        }
    }
//==============================================================================
// 指定フォルダの存在確認
    public function CheckFolder($home) {
        $this->TopFolder = $this->Home[$home] . $this->SubFolder;
//        if(!file_exists($this->TopFolder)) mkdir($this->TopFolder);
    }
//==============================================================================
// 指定ファイルのフルパス
    public function Get_Fullpath($home,$fname, $mkdir = FALSE) {
        $tagdir = $this->Home[$home] . "{$this->SubFolder}";
        if($mkdir && !file_exists($tagdir)) {
            mkdir($tagdir);             // フォルダが無く、作成が指定されていれば作成する
        }
        return "{$tagdir}/{$fname}";
    }
//==============================================================================
// ファイル移動
    public function MoveFile($fcat,$fname,$tocat) {
        $frname = $this->Get_Fullpath($fcat,$fname);
        $toname = $this->Get_Fullpath($tocat,$fname);
        $srcname = LocalCharset($frname);	// 移動元ファイルパス
        $tagname = LocalCharset($toname);		// 移動先ファイルパス
        file_move($srcname, $tagname);			// ファイル移動、移動先のフォルダがなければ作成
        echo $fname . ' を移動しました';
    }
//==============================================================================
// フォルダ内の全ファイル移動
    public function MoveAllFiles($frcat,$tocat) {
        $this->GetSubFolder($frcat);			// 移動もとのファイル一覧を取得
        foreach($this->Files as $fval) {
            $srcname = LocalCharset($fval['fullname']);	// 対象ファイルパス
            $tagname = LocalCharset($this->Get_Fullpath($tocat,$fval['filename']));
			file_move($srcname, $tagname);			// ファイル移動、移動先のフォルダがなければ作成
//			echo $srcname . "=>" . $tagname ."\n";
        }
    }
//==============================================================================
// ファイル削除
    public function DeleteFile($fcat,$fname) {
        $srcname = LocalCharset($this->Get_Fullpath($fcat,$fname));
        if(file_exists($srcname)) unlink($srcname);         // 移動先に同名ファイルがあれば削除
        echo $fname . ' を削除しました';
    }
//==============================================================================
// 指定フォルダのファイル一括削除
    public function DeleteAllFiles($home) {
        $this->GetSubFolder($home);			// 一覧を取得
        foreach($this->Files as $fval) {
            $srcname = LocalCharset($fval['fullname']);	// 対象ファイルパス
            if(file_exists($srcname)) unlink($srcname);         // 移動先に同名ファイルがあれば削除
//			echo $srcname;
        }
    }
//==============================================================================
// フォルダ内を探査する
    private function get_FolderLists($dirs) {
//    echo "GET::{$dirs}\n";
        $this->Files = array();
        if(!file_exists ($dirs)) {
            return false;
        }
        $drc=dir($dirs);
        setlocale(LC_ALL,"ja_JP.UTF-8");
        while(false !== ($fl=$drc->read())) {
            if(! in_array($fl,IgnoreFiles,FALSE)) {
//                clearstatcache();
                $lfl = "{$dirs}/{$fl}";
                $din = pathinfo($lfl);
                if(is_dir($lfl)) {
                    $this->Folder[] = SysCharset($din["basename"]);
                } else if(file_exists ($lfl)) {
                // ファイル名
                    $this->Files[] = array(
                        'fullname' => SysCharset($lfl),
                        'filename' => SysCharset($din["basename"]),
                        'name' => SysCharset($din["filename"]),
                        'size' => round(filesize($lfl)/1024),
                        'date' => date("Y-m-d H:i:s",filemtime($lfl)),
                        'ext' => empty($din["extension"]) ? "" : SysCharset($din["extension"])
                    );
                } else {
                    echo "fail:" .$lfl;
                }
            }
        }
        $drc->close();
		APPDEBUG::MSG(13,$this->Files);
        return true;
    }
//==============================================================================
// ZIPファイルの作成
    public function Make_ZipFile($filepath,$zipname) {
	    // Zipクラスロード
        $zip = new ZipArchive();
		// Zipファイル一時保存ディレクトリ
        $zipTmpDir = ZIPTEMP;
 		// Zipファイル名
        $zipFilePath = $zipTmpDir . $zipname;
    	// Zipファイルオープン
   		$result = $zip->open($zipFilePath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
   		if ($result !== true) {
    		echo "Download ERROR!!!";
   			exit(-1);
    	    // 失敗した時の処理
   		}
	    set_time_limit(0);			// 処理制限時間を外す
        // ディレクトリ指定なら一括ZIP
        if(is_dir($filepath)) {
            // 指定パスのファイルリストを取得する
            $this->get_FolderLists($filepath);
            // 取得ファイルをZipに追加していく
	        foreach($this->Files as $filelist) {
		        $fullname = $filelist['fullname'];
    		    $filename = $filelist['filename'];
    	    	// 取得ファイルをZipに追加していく
	    	    $pathname= addslashes(LocalCharset($fullname));
    	    	$filename= LocalCharset($filename);
	    	    $zip->addFromString($filename,file_get_contents($pathname));
            }
        } else {
        	// 指定ファイルをZip化する
	    	$pathname= addslashes(LocalCharset($filepath));
		    $filename= LocalCharset(pathinfo($filepath,PATHINFO_BASENAME));
    		$zip->addFromString($filename,file_get_contents($pathname));
        }
    	$zip->close();
        // 作成したZIPファイルを返す
        return $zipFilePath;
    }

}
