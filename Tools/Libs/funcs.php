<?php
//==============================================================================
function array_extract($arr,$n) {
	if(is_array($arr)) {
		$slice = [];
		foreach($arr as $key => $val) {
			if(is_int($key)) $slice[] = $val;//(is_array($val))?[$val]:$val;
			else $slice[] = [$key => $val];
			--$n;
		}
	} else $slice = [$arr];
	while($n-- > 0)$slice[]=NULL;
	return $slice;
}
//==============================================================================
function oct_extract($val,$n) {
	$oct = [];
	while($n--) { $oct[] = ($val & 07); $val >>= 3; }
	return $oct;
}
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
	if($dec) $dec = substr("0000".decoct($dec),-5);
	return $dec;
};
//==============================================================================
// バインド配列変換
function bind_array($rel,$sep=NULL) {
	$bind = str_explode(["\r\n","\n"],$rel);
	if(count($bind)===1)	$bind = $rel;
	else {
		if($sep !== NULL) {
			$item = [array_pop($bind)];
			foreach($bind as $nm) {
				set_array_key_unique($item,$sep,$nm);
			}
			$bind = $item;
		}
	}
	return $bind;
}