<?php
/*
    BLOGデータベースの構造
        blogContens             記事のタイトル、投稿日を記録
            blogSection             記事の内容を章ごとに記録、複数登録できる
                blogParagraph           章のなかにある段落、複数登録可能
    記事の内容は独自のマークダウン記法で記述できる
*/
class IndexModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DataTable' => 'blogContents',
        'Primary' => 'id',
        'Schema' => [
            'id'        => ['',0],
            'post_date' => ['',0],
            'edit_date' => ['',0],
            'category_id'=> ['',0,0,'Category.id.title'],
            'published' => ['',0],
            'toc_gen'   => ['',0],
            'title'     => ['',100],
            'summary'   => ['',100],
            'preface'   => ['',100],
        ],
        'PostRenames' => [],
    ];
//==============================================================================
// 記事のIDからセクションデータとパラグラフデータを読み込む
//==============================================================================
function ReadContents($id) {
    $this->RecData = $this->getRecordByKey($id);
    $this->GetValueList();
    // セクションを読み込む
    $this->Section->RecordFinder(['blog_id=' => $id],NULL,'seq_no');
    $this->Records = $this->Section->Records;
    // パラブラフを読み込む
    $section = [];
    foreach($this->Section->Records as $columns) {
        $id = $columns['id'];
        $sec_item['sec'] = $columns;
        $this->Paragraph->RecordFinder(['section_id=' => $id],NULL,'seq_no');
        $sec_item['paragraph'] = $this->Paragraph->Records;
        $section[] = $sec_item;
    };
    $this->BlogContents = $section;
    debug_log(1,['BlogData'=>$this->RecData,'Section'=>$this->Records,'Para'=>$this->BlogContents,"SELECT"=>$this->Select]);
}
//==============================================================================
// 月間ブログをリストする
public function BlogMonth($cond) {
	$this->RecordFinder($cond,[],['post_date'=>SORTBY_DESCEND]);
    $this->Monthly = [];
    foreach($this->Records as $columns) {
        $dtm = strtotime($columns['post_date']);
        list($Y,$M,$D) = explode(',',date("Y,m,d",$dtm));
        $this->Monthly[date("Y/m/d",$dtm)] = 1;
	}
}

}
