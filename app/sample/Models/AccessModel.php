<?php
/*
 SELECT logdate,access,page,method,contents,repeat,COUNT(id) AS count,SUM(repeat) AS sum  
FROM 'accesslog' 
GROUP BY 
logdate,page,method ,contents
            ORDER BY sum ASC
*/
class AccessModel extends AppModel {
    static $DatabaseSchema = [
        'Handler' => 'SQLite',
        'DataTable' => 'accesslog',
        'Primary' => 'id',
		'Schema' => [
			'id'		=> [ '', 0,0 ],
			'logdate'	=> [ '', 0,0 ],
			'access'	=> [ '', 0,0 ],
			'last_access'	=> [ '', 0,0 ],
			'userid'	=> [ '', 0,0 ],
			'logid'		=> [ '', 0,0 ],
			'page'		=> [ '', 0,0 ],
			'method'	=> [ '', 0,0 ],
			'contents'	=> [ '', 0,0 ],
			'repeat'	=> [ '', 0,0 ],
			'query'		=> [ '', 0,0 ],
			'post'		=> [ '', 0,0 ],
		],
    ];
	const same_access = (60*5);				// 同一アクセスとみなすインターバル
	const aggregate_period = (60*60*24*3);	// 何日前まで集計する
//==============================================================================
// メソッド実行後にAppController から呼び出されて、ログを記録する
// 引数は Controllerインスタンスとアクションメソッド名
public function Logged($instance,$action) {
    $rec_id = App::$Params[0];           // レコードID/ページ番号
	$this->Logging($instance->ModuleName,$action,$rec_id);
}
//==============================================================================
private function Logging($module,$method,$rec_id) {
	$logtime = time();
	$now	= date($this->TimeFormat ,$logtime);
	$query	= array_key_value(App::$Query,'&');
	$post	= array_key_value(App::$Post,'&');
	if($rec_id === 0) $rec_id = '';
	// 記録ログレコードを作成
	$row = [
			'logdate'	=> date($this->DateFormat,$logtime),
			'access'	=> $now,
			'last_access'=> $now,
			'userid'	=> MySession::get_LoginValue('userid'),
			'logid'		=> "{$module}/{$method}/{$rec_id}",
			'page'		=> $module,
			'method'	=> $method,
			'contents'	=> $rec_id,
			'repeat'	=> 1,
			'query'		=> $query,
			'post'		=> $post,
	];
	// 繰り返しアクセスされたときにリピート数を更新する
	$period = date($this->TimeFormat ,$logtime - self::same_access)."...".date($this->TimeFormat ,$logtime);
	$cond 	= [
			'logdate='	=> $row['logdate'],
			'userid='	=> $row['userid'],
			'logid='	=> $row['logid'],
			'last_access'=> $period,
	];
	if(($cols = $this->firstRecord($cond))) {
		$id = $cols['id'];
		$rep = intval($cols['repeat']);
		$row = [
			'last_access'=> $now,
			'repeat'	=> $rep+1,
			'query'		=> $query,
			'post'		=> $post,
		];
		$this->UpdateRecord($id,$row);
		return;
	}
	$this->AddRecord($row);
}
//==============================================================================
// ログ集計テーブルを作成する
public function LogRanking($max = 0) {
	$groups = [ 'logid','page','method','contents'];
	$calc = [
		'id.COUNT' => 'uniq',
		'repeat.SUM' => 'total',
	];
	$sortby = [
		'total'		=> SORTBY_DESCEND,
		'uniq'		=> SORTBY_DESCEND,
	];
	$today = time();
	$start = $today - self::aggregate_period;
	$period = date($this->DateFormat,$start)."...".date($this->DateFormat,$today);
	$cond = ['logdate' => $period];
	$this->tableAggregate($cond,$groups,$calc,NULL,$sortby,$max);
}


}
