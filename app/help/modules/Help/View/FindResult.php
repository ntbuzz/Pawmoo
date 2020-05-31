<div class="find_result">

<?php
function MarkContents($ttl,$cont,$wd) {
	$t1 = mark_active_words($ttl,$wd,"hit_word");
	$t2 = mark_active_words($cont,$wd,"hit_word");
	return [$t1,$t2];
}
echo "<h1>【検索結果】: '$Helper->QUERY'</h1>\n";
echo "<hr>\n";
debug_dump(0,["FindResult" => $this->Model->outline]);
debug_dump(0,["MyModel Dump" => $MyModel->outline]);
// パートツリー
foreach($MyModel->outline as $key => $part) {
	list($title,$contents) = MarkContents($part['title'],$part['contents'],$Helper->QUERY);
	echo "<H2>{$title}</H2>\n";
	echo "<blockquote>{$contents}</blockquote>\n";
	echo "<DL>\n";
	// チャプターツリーを表示
	foreach($part['chapter'] as $kk => $chap) {
		list($title,$contents) = MarkContents($chap['title'],$chap['contents'],$Helper->QUERY);
		$lnk = "{$key}/{$kk}/";			// PART-ID/CHAP-ID
		$ttl = $chap['title'];
		if(empty($title)) $title = $lnk;
		echo "<DT>"; $Helper->ALink("index/view/{$lnk}","■ {$title}"); echo "</DT>\n";
		echo "<DD>{$contents}\n";
		// セクションツリーを表示
		echo "<DL>\n";
		foreach($chap['section'] as $kkk => $sec) {
			list($title,$contents) = MarkContents($sec['title'],$sec['contents'],$Helper->QUERY);
			$tab = intval($sec['disp_id']) - 1;
			if($tab < 0) $tab = 0;
			echo "<DT>"; $Helper->ALink("index/view/{$lnk}{$tab}","・{$title}");echo "</DT>\n";
			echo "<DD>{$contents}\n";
			echo "<DL>\n";
			foreach($sec['paragraph'] as $kkkk => $para) {
				list($title,$contents) = MarkContents($para['title'],$para['contents'],$Helper->QUERY);
				echo "<dt>{$title}</dt>\n";
				echo "<dd>{$contents}</dd>\n";
			}
			echo "</DL>\n";
			echo "</DD>\n";
		}
		echo "</DL>\n";
		echo "</DD>\n";
		
	}
	echo "</DL>\n";
}
?>
</div>
