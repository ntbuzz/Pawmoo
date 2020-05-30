<div class="find_result">

<?php
echo "<h1>【検索結果】: '$Helper->QUERY'</h1>\n";
echo "<hr>\n";
debug_dump(0,["FindResult" => $this->Model->outline]);
debug_dump(0,["MyModel Dump" => $MyModel->outline]);
// パートツリー
foreach($MyModel->outline as $key => $part) {
//var_dump($part);
	$ttl = $part['title'];
	$contents = $part['contents'];
	echo "<H2>{$ttl}</H2>\n";
	echo "<blockquote>{$contents}</blockquote>\n";
	echo "<DL>\n";
	// チャプターツリーを表示
	foreach($part['chapter'] as $kk => $chap) {
		$lnk = "{$key}/{$kk}/";			// PART-ID/CHAP-ID
		$ttl = $chap['title'];
		if(empty($ttl)) $ttl = $lnk;
		echo "<DT>";
		$Helper->ALink("index/view/{$lnk}","■ {$ttl}");
		echo "</DT>\n";
		echo "<DD>{$chap[contents]}\n";
		// セクションツリーを表示
		echo "<DL>\n";
		foreach($chap['section'] as $kkk => $sec) {
			$tab = $sec['disp_id'];
			if(empty($tab)) $tab = 1;
			echo "<DT>";
			$Helper->ALink("index/view/{$lnk}{$tab}","・$sec[title]");
			echo "</DT>\n";
			echo "<DD>$sec[contents]\n";
			echo "<DL>\n";
			foreach($sec['paragraph'] as $kkkk => $para) {
				$title = mark_active_words($para['title'],$Helper->QUERY,"hit_word");
				$contents = mark_active_words($para['contents'],$Helper->QUERY,"hit_word");
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
