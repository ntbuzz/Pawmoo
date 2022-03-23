<?php

//==============================================================================
// フォルダ内のファイルを取得する
function get_files($path,$ext,$full=true) {
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
// 8進数に変換
function oct_fix($dec) {
	$dec = ($dec) ? substr("0000".decoct($dec),-5) : '    0';
	return $dec;
};
//==============================================================================
// fix count explode
function bind_explode($delm,$string,$max,$pad = '') {
	$arr = explode($delm,$string);
	for($n=count($arr); $n < $max ; ++$n ) $arr[] = $pad;
    return $arr;
}
//==============================================================================
// バインド配列変換
function bind_array($rel,$sep=0) {
	$bind = str_explode(["\r\n","\n"],$rel);
	if(count($bind)===1)	$bind = [$sep => $rel ];
	else $bind = [ $sep => $bind];
	// else {
	// 	if($sep !== NULL) {
	// 		$item = [array_pop($bind)];
	// 		foreach($bind as $nm) {
	// 			set_array_key_unique($item,$sep,$nm);
	// 		}
	// 		$bind = $item;
	// 	}
	// }
	return $bind;
}