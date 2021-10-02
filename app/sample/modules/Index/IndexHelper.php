<?php
/*
    extends でアプリ用にカスタマイズした blogHelper を継承します。
    複数のモジュールで使う必要がなければ、blogHelperのメソッドをそのままここに実装します
*/
class IndexHelper extends blogHelper {

//==============================================================================
public function BlogHeading() {
    $body = $this->Model->RecData;
    $itemdate = (empty($body['edit_date'])) ? $body['post_date']: "{$body['post_date']} 更新:{$body['edit_date']}";
    // ブログのタイトルとリード文
    $body_header = (empty($body['preface']))?'':pseudo_markdown($body['preface']);
    $atext = <<<EOF
    <h2>{$body['title']}
    </h2>
    <div style="position:absolute;right:10px;z-index:1;font-size:9pt;font-weight:bold;">{$itemdate}</div>
    {$body_header}
EOF;
    echo $atext;
    // 目次を作る
    $this->BlogTOC();
}
//==============================================================================
public function BlogBody() {
    $Contents = $this->Model->BlogContents;
    // 本文のセクション
    foreach($Contents as $sec_data) {
        $section = $sec_data['sec'];
        $disable  = ($section['published'] === 't') ? '' : ' disable';
        $hastitle = (empty($section['title'])) ? '':"<h3>{$section['title']}</h3>";
        $txtcol = (empty($section['title'])) ? ' style="color:black;"':"";
        $body_contents = pseudo_markdown($section['contents']);
        $atext = <<<EOF
<a name="sec-{$section['id']}"></a>
<div class='blog_section{$disable}' id={$section['id']}>
    {$hastitle}
    {$body_contents}
EOF;
        echo $atext;
        foreach($sec_data['paragraph'] as $contents) {
            $disable  = ($contents['published'] === 't') ? '' : ' disable';
            $hastitle = (empty($contents['title'])) ? '':"<a name='para-{$contents['id']}'></a><h4>■ {$contents['title']}</h3>";
            $txtcol = (empty($contents['title'])) ? ' style="color:black;"':"";
            $body_contents = pseudo_markdown($contents['contents']);
            $body_contents = $this->expand_var($body_contents);

            $atext = <<<EOF
    <div class='blog_content{$disable}' id={$contents['id']}>
        {$hastitle}
        {$body_contents}
    </div>
EOF;
            echo $atext;
        }
        echo "</div>\n";
    }
    echo "<br><hr>";
    echo $this->_('.Category') . $this->Model->RecData['category'];
    $this->BlogWALK();
}
//==============================================================================
function ChainSelect() {
    return <<<EOS
好きなアプリ： <select id="fav-list" data-element="fav-name"></select>
    <select id="fav-name"></select>
EOS;
}
//==============================================================================
// RankingLog
public function Ranking($max = 0) {
	$this->Access->LogRanking($max);
	echo "<table class='thintable'>\n";
	echo "<tr><th>No.</th>";
	foreach($this->Access->Headers as $head) {
		echo "<th>{$head}</th>";
	}
	echo "</tr>\n";
	$no = 1;
	foreach($this->Access->Records as $record) {
		echo "<tr>";
		echo "<td class='num'>{$no}</td>";
		$lnk = "/sample/".strtolower($record['logid']);
		$title = "";
		if($record['method'] === 'View') {
			$model = $record['page'];
			$id = $record['contents'];
			$title = $this->$model->getRecordField('id',$id,'title');
		}
		if(empty($title)) $title = $record['logid'];
		$record['logid'] = "<a href='{$lnk}'>{$title}</a>";
		foreach($record as $column) {
			$cls = (is_numeric($column)) ? ' class="num"' : '';
			echo "<td{$cls}>{$column}</td>";
		}
		echo "<tr>\n";
		++$no;
	}
	echo "</table>\n";
}

}
