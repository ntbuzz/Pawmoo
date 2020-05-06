<?php

//===============================================================================
// ファイルパスを分解する
function str_parts($path,$sep) {
    $nn = strrpos($path,$sep);    // セパレータをチェック
    return ($nn === FALSE) ? '' : substr($path,$nn+1);     // セパレータを抽出
}
//===============================================================================
// 拡張子なしのファイル名
function GetFileName($fn) {
    $ext = str_parts($fn,'.');    // 拡張子を確認
    if($ext !== '') $fn = str_replace('.'.$ext,'',$fn);
    return $fn;
}

//===============================================================================
// URLの特殊文字エスケープ
function CHREncode($str) {
    $str = str_replace('#','%23',$str);
    $str = str_replace("'",'%27',$str);
    $str = str_replace("&",'%26',$str);
    $str = str_replace("&",'%26',$str);
    $str = str_replace("?",'%3f',$str);
    $str = str_replace(" ",'%20',$str);
    return $str;
}
//===============================================================================
// 共通Libに移動したほうが良い
//===============================================================================
// 日付行のマーク
function MarkActivity($atext) {
	$ln = explode("\n", $atext);	// とりあえず行に分割
	$ln = array_map('trim', $ln);	// 各要素をtrim()にかける
	$ln = array_values($ln);		// これはキーを連番に振りなおしてるだけ
	$ret = array();
	foreach($ln as $ll) {
    	$ll = preg_replace("/((https?|ftp)(:\/\/[-_.!~*\'()a-z0-9;\/?:\@&=+\$,%#]+))/i",'<a target="new" href="\\1">\\1</a>', $ll);
		$ll = preg_replace('/^(\d{4}\/\d{2}\/\d{2} \d{1,2}:\d{1,2}(:\d{1,2})*　PSSC:.+)$/iu','<span class="respons">\\1</span>', $ll);
		$ll = preg_replace('/^(\d{4}\/\d{2}\/\d{2} \d{1,2}:\d{1,2}((:\d{1,2})*|(:\d{1,2}[ 　].+)))$/iu','<span class="activity">\\1</span>', $ll);
		$ret[] = $ll;//preg_replace('/^(\d{4}\/\d{2}\/\d{2} \d{1,2}:\d{1,2}:\d{1,2}[ 　].+)$/iu','<span class="activity">\\1</span>', $ll);
	}
	return implode("<br />\n",$ret);
}
//===============================================================================
// 週初めの日付を計算
function get_beginning_week_date($pday) {
    $monday = (strtotime('monday') == strtotime('today'))? strtotime('monday'):strtotime('last monday');
    $friday = strtotime('monday', $monday);
    $pday *= 60*60*24;
    
    $beginning_week_date = date('m/d/Y',$monday-$pday) . '...' . date('m/d/Y',$friday-$pday);
    APPDEBUG::MSG(23,":WEEKBEG >> " . $beginning_week_date);
    return $beginning_week_date;
}