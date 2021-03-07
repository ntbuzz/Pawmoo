<?php

class blogHelper extends AppHelper {

//==============================================================================
public function TitleList() {
    echo "<dl class='content'>\n";
    $n = 0;
    foreach($this->MyModel->Records as $columns) {
        $jmp = "./view/{$columns['id']}";
        $pub = ($columns['published']==='t') ? '' :' 【非公開】';
        $cls = ($columns['published']==='t') ? 'public' :'secret';
        $pdate = date('Y年m月d日',strtotime($columns['post_date']));
        $edate = (empty($columns['edit_date'])) ? '': ' 更新:'.date('Y年m月d日',strtotime($columns['edit_date']));
        echo "<hr>\n";
        echo "<dt class='{$cls}'><a href='{$jmp}'>{$columns['title']}</a>{$pub}<span class='date'>{$pdate}{$edate}</span></dt>\n";
        echo "<dd class='{$cls}'>{$columns['summary']}</dd>\n";
	}
    echo "</dl>\n";
}
//==============================================================================
public function CategorySelect() {
    $this->MyModel->GetValueList();
    echo $this->Select_str('category','category_id',$this->MyModel->RecData['category_id']);
}
//==============================================================================
public function BlogTOC() {
    $body = $this->MyModel->RecData;
    // 目次を作る
    if($body['toc_gen']==='t') {
        $toc = [];
        foreach($this->MyModel->BlogContents as $sec_data) {
            $section = $sec_data['sec'];
            if($section['published'] === 't') {
                $key = $section['title'];
                $sub = [];
                foreach($sec_data['paragraph'] as $contents) {
                    if($contents['published'] === 't' && !(empty($contents['title']))) {
                        $sub[] = [ "#para-{$contents['id']}", $contents['title'] ];
                    }
                }
                $toc[$key] = [ "#sec-{$section['id']}",$sub];
            }
        }
        $atext ="<ol>\n";
        foreach($toc as $key => $val) {
            list($lnk,$sub) = $val;
            $atext .= "<li><a href='{$lnk}'>{$key}</a>";
            if(!empty($sub)) {
                $atext .= "\n<ul>\n";
                foreach($sub as $vv) {
                    list($ll,$ttl) = $vv;
                    $atext .= "<li><a href='{$ll}'>{$ttl}</a>\n";
                }
                $atext .= "</ul>\n";
            }
        }
        $atext .= "</ol>\n";
        echo "<div class='TOC'><h3>目次</h3>{$atext}</div>";
    }
}
//==============================================================================
public function BlogWALK() {
    $_ = function($v) { return $this->_($v); };
    echo "<br><hr>\n";
    $th_width = " style='width:80px;'";
    echo "<table class='prev_next'>";
    echo "<tr><th{$th_width}>{$_('.PREV')}</th>";
    $text_align = ['left','right'];
    foreach($this->MyModel->NearData as $id => $val) {
        $cls = $text_align[$id];
        echo "<td style='text-align:{$cls};'>";
        if(!empty($val)) {
//            $ttl =$val['title'];
            $ttl = strlen_limit($val['title'],28);
            $this->ALink("./view/{$val['id']}",$ttl);
        }
        echo "</td>";
    }
    echo "<th{$th_width}>{$_('.NEXT')}</th></tr>";
    echo "</table>";
}

}
