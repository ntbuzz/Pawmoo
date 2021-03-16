<?php
/*
	AppFilesModel を廃止
	ファイル操作は全部 FlatFolder クラスを使うように変更
*/
class FlatFolder {
	private $TopFolder;		// フォルダトップ
//==============================================================================
// コンストラクタでデフォルトのフォルダを設定
	function __construct($root = '') {
		$this->TopFolder = $root;
	}
//==============================================================================
// トップフォルダの再設定
public function SetRoot($root) {
	$this->TopFolder = $root;
}
//==============================================================================
// トップフォルダに指定されたフォルダのファイル一覧を取得する
//	$this->Files[] の連想配列に格納
public function GetSubFolder() {
	$this->get_FolderLists($this->TopFolder);
}
//==============================================================================
// テキストファイルを読み込む
public function LoadContents($fname) {
	$fullpath = "{$this->TopFolder}/{$fname}";
	clearstatcache(TRUE);
	if(file_exists($fullpath)) {
		$lines = file($fullpath);
		if($lines !== FALSE) {
			mb_convert_variables("UTF-8","sjis-win",$lines);
			return $lines;
		}
	}
	return FALSE;
}
//==============================================================================
// ファイルの属性を取得
public function GetAttribute($path) {
	$din = pathinfo(LocalCharset($path));
	$arrts = array(
		'fullname' => SysCharset($path),
		'filename' => SysCharset($din["basename"]),
		'name' => SysCharset($din["filename"]),
		'size' => round(filesize($path)/1024),
		'date' => date("Y-m-d H:i:s",filemtime($path)),
		'ext' => empty($din["extension"]) ? "" : SysCharset($din["extension"])
	);
	return $attrs;
}
//==============================================================================
// ファイル移動
public function MoveFile($fromfile,$tofile) {
	$srcname = LocalCharset($fromfile);		// 移動元ファイルパス
	$tagname = LocalCharset($tofile);		// 移動先ファイルパス
	if(file_move($srcname, $tagname)) {		// ファイル移動、移動先のフォルダがなければ作成
		echo "{$srcname} を\n{$tagname} へ移動しました\n";
	} else {
		echo "{$srcname} の移動に失敗しました\n";
	}
}
//==============================================================================
// トップフォルダの下へ移動
public function FileMoveTo($fromfile,$tofile) {
	return $this->MoveFile($fromfile,"{$this->TopFolder}{$tofile}");
}
//==============================================================================
// フォルダ内の全ファイル移動
public function MoveAllFiles($fromdir,$todir) {
	$this->get_FolderLists($fromdir);			// 移動元のファイルリスト
	foreach($this->Files as $filelist) {
		$srcname = LocalCharset($filelist['fullname']);	// 対象ファイルパス
		$filename = $filelist['filename'];
		$tagname = LocalCharset("{$todir}{$filename}");
		file_move($srcname, $tagname);			// ファイル移動、移動先のフォルダがなければ作成
	}
}
//==============================================================================
// ファイル削除
public function DeleteFile($fullname) {
	$srcname = LocalCharset($fullname);
	if(file_exists($srcname)) unlink($srcname);         // 移動先に同名ファイルがあれば削除
	echo $fname . ' を削除しました';
}
//==============================================================================
// 指定フォルダのファイル一括削除
public function DeleteAllFiles($topdir) {
	$this->get_FolderLists($topdir);	// 削除フォルダのファイルリスト
	foreach($this->Files as $filelist) {
		$srcname = LocalCharset($filelist['fullname']);	// 対象ファイルパス
		if(file_exists($srcname)) unlink($srcname);         // 移動先に同名ファイルがあれば削除
	}
}
//==============================================================================
// フォルダ内を探査する
    private function get_FolderLists($dirs) {
        $this->Files = array();
        if(!file_exists ($dirs)) {
            return false;
        }
        $drc=dir($dirs);
        setlocale(LC_ALL,"ja_JP.UTF-8");
        while(false !== ($fl=$drc->read())) {
            if(! in_array($fl,IgnoreFiles,true)) {
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
        return true;
    }
//==============================================================================
// ZIPファイルの作成
	protected function Make_ZipFile($filepath,$zipname) {
		// Zipクラスロード
		$zip = new ZipArchive();
		// Zipファイル一時保存ディレクトリ
		$zipTmpDir = ZIPTEMP;
		// Zipファイル名
		$zipFilePath = "{$zipTmpDir}{$zipname}";
		// Zipファイルオープン
		$result = $zip->open($zipFilePath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
		if ($result !== true) {
			echo "{$zipname} Download ERROR!!!";
			exit(-1);
			// 失敗した時の処理
		}
		set_time_limit(0);			// 処理制限時間を外す
		// ディレクトリ指定なら一括ZIP
		if(is_dir($filepath)) {
			// 指定パスのファイルリストを取得する
			$this->get_FolderLists($filepath);
			// 取得ファイルをZipに追加していく
			foreach($filelist['FILE'] as $filelist) {
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
