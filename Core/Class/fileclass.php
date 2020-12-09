<?php
/*
	使い方の案
	$this->GetFolderList(パス)
		return array(fileList)
			[0..n]	通常ファイル
			[dir]	サブディレクトリ: array(fileList)
	$this->GetAttribute(ファイルパス)
		filename
		ext
		dirname
		size
		date
*/
class FlatFolder {
	private $TopFolder;		// フォルダトップ
//==============================================================================
// コンストラクタでフォルダ内を探索
	function __construct(){
	}
//==============================================================================
// デストラクタ
	function __destruct() {
	}
//==============================================================================
// トップフォルダの設定
	public function SetRoot($root) {
		$this->TopFolder = $root;
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
// フォルダ内を探査する
public function getFolderName($dirtop) {
	$path = explode('/',$dirtop);
	$path = array_map('trim', $path);	// 各要素をtrim()にかける
	$path = array_filter($path, 'strlen'); // 文字数が0のやつを取り除く
	return array_pop($path);
}
//==============================================================================
// フォルダ内を探査する
public function getParentPath($dirtop) {
	$path = explode('/',$dirtop);
	$path = array_map('trim', $path);	// 各要素をtrim()にかける
	$path = array_filter($path, 'strlen'); // 文字数が0のやつを取り除く
	array_pop($path);
	return "/" . implode('/',$path);
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
// フォルダ内を探査する
	private function GetFolderList($dirtop,$lvl) {
		$top = LocalCharset($dirtop);		// Windows（SJIS)対策
		$drc = dir($top);
		$files = array();
		while(false !== ($fl=$drc->read())) {
			$sysfl = SysCharset($fl);		// Windows対策
			if(! in_array($sysfl,IgnoreFiles,true)) {
				$lfl = "{$top}{$fl}";				// Windows(SJIS)対策
				$syslfl = SysCharset($lfl);		// Windows対策
				$ext = substr($sysfl,strrpos($sysfl,'.') + 1);    // 拡張子を確認
				if(is_dir($lfl)) {
					if($lvl < 2) {
						$dir[$syslfl] = $this->GetFolderList($syslfl.'/',$lvl+1);
					} else {
						$dir[$syslfl] = $sysfl;
					}
					$files = array_merge($files, $dir);
				} else { // if($ext == 'mp4') {
					$files[] = $sysfl;
				}
			}
		}
		$drc->close();
		return $files;
	}
//==============================================================================
// フォルダ内を探査する
public function GetFiles($dirtop,$lvl) {
	$top = LocalCharset(path_complete($dirtop));		// Windows対策
	$drc = dir($top);
	$files = array('FILE' => array(), 'DIR' => array() );
	while(false !== ($fl=$drc->read())) {
		$sysfl = SysCharset($fl);			// Windows 対策
		if(! in_array($sysfl,IgnoreFiles,true)) {
			$lfl = "{$top}{$fl}";				// Windows(SJIS)対策
			$syslfl = SysCharset($lfl);		// Windows対策
			$ext = substr($sysfl,strrpos($sysfl,'.') + 1);    // 拡張子を確認
			if(is_dir($lfl)) {
				if($lvl < 2) {
					$dir['DIR'][$syslfl] = $this->GetFolderList($syslfl.'/',$lvl+1);
				} else {
					$dir['DIR'][$syslfl] = $sysfl;
				}
				$files = array_merge($files, $dir);
			} else { // if($ext == 'mp4') {
				$files['FILE'][] = $sysfl;
			}
		}
	}
	$drc->close();
	return $files;
}
//==============================================================================
// ツリー形式ダンプ
	public function TreeDump($files) {
		echo '<pre>';
		$this->fileDump(0,$files,$this->TopFolder);
		echo '</pre>';
	}
	private function fileDump($lvl,$files,$top) {
		foreach($files as $k =>$v) {
			echo str_repeat('│　　',$lvl) . '├';
			$wd = 80 - $lvl*5 - 2;
			if(is_array($v)) {
				echo str_pad($k,16," ",STR_PAD_RIGHT) . "/ (".count($v).") files\n";
				if($lvl < 3) {
					$this->fileDump($lvl+1,$v,$top.$k.'/');
				}
			} else {
				$fn = $top . $v;
				$sz = round(filesize($fn)/1024);
				$dt = date('Y-m-d -H:i:s',filemtime($fn));
				echo str_pad($v,$wd," ",STR_PAD_RIGHT) . str_pad($sz,14," ",STR_PAD_LEFT) . " KB/{$dt}\n";
			}
		}
	}
//==============================================================================
// ファイル移動
	public function MoveFile($fromfile,$tofile) {
		$srcname = LocalCharset($fromfile);		// 移動元ファイルパス
		$tagname = LocalCharset($tofile);		// 移動先ファイルパス
		file_move($srcname, $tagname);			// ファイル移動、移動先のフォルダがなければ作成
		echo $fromname . ' を移動しました';
	}
//==============================================================================
// フォルダ内の全ファイル移動
	public function MoveAllFiles($fromdir,$todir) {
		$this->GetSubFolder($frcat);			// 移動もとのファイル一覧を取得
		foreach($this->Files as $fval) {
			$srcname = LocalCharset($fval['fullname']);	// 対象ファイルパス
			$tagname = LocalCharset($this->Get_Fullpath($tocat,$fval['filename']));
			file_move($srcname, $tagname);			// ファイル移動、移動先のフォルダがなければ作成
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
		}
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
		$filelist = $this->GetFiles($filepath,1);			// ファイル一覧を取得
		// 取得ファイルをZipに追加していく
		foreach($filelist['FILE'] as $key => $fval) {
			$fullname = "{$filepath}/{$fval}";
			$filename = $fval;
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
