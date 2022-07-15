<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  DatabaseHandler: Database Connection Handling
 */
define('SORTBY_ASCEND', 1);
define('SORTBY_DESCEND', 2);

require_once('Core/Handler/SqlHandler.php');
// 各種データベースへのアクセスハンドラ
require_once('Core/Handler/SQLiteHandler.php');
require_once('Core/Handler/PostgreHandler.php');
require_once('Core/Handler/NullHandler.php');

//==============================================================================
// データベースの接続情報を保持するクラス
// 全体で１度だけ呼び出す
class DatabaseHandler {
    const DatabaseSpec = [
        // SQLite3データベースへの接続情報
        'SQLite' => [
            'datasource' => 'Database/SQLite3',
            'callback' => 'SQLiteDatabase',
            'offset' => false,
        ],
        // PostgreSQLデータベースへの接続情報
        'Postgre' => [
            'datasource' => 'Database/Postgres',
            'callback' => 'PgDatabase',
            'offset' => TRUE,
        ],
        // MariaDB(MySQL)データベースへの接続情報
        'MySQL' => [
            'datasource' => 'Database/MariaDB',
            'callback' => 'MySQLDatabase',
            'offset' => false,
        ],
    ];
    private static $dbHandle = [];
    public static $have_offset = TRUE;
//==============================================================================
// データベースへ接続してハンドルを返す
public static function get_database_handle($handler,$connectDB=NULL) {
	$config = $GLOBALS['config'];
	$handle_key = ($connectDB===NULL)?$handler:"{$handler}.{$connectDB}";
    if(array_key_exists($handle_key,static::$dbHandle)) {
        list($handler,$dd) = static::$dbHandle[$handle_key];
		return $dd;
    }
    if(array_key_exists($handler,static::DatabaseSpec)) {
		$db = $config->$handler;
		if($connectDB!==NULL) $db['database'] = $connectDB;
        debug_log(DBMSG_HANDLER|DBMSG_NOTRACE,[
				'HANDLER' => $handler,
				'DB-HOST' => $db['host'],
				'DB-NAME' => $db['database'],
				'ENVIROMENT'=>"{$config->hostname} ({$config->Enviroment})",
			]);
        $defs = static::DatabaseSpec[$handler];
        $func = $defs['callback'];      // 呼び出し関数
        static::$have_offset = $defs['offset'];     // DBMS has OFFSET command?
        $dd = static::$func($db,'open');
        static::$dbHandle[$handle_key] = [$handler,$dd];
        return $dd;
    }
    return NULL;
}
//==============================================================================
// デストラクタ
public static function CloseConnection() {
    static::closeDb();
}
//==============================================================================
// ハンドルの取得
public function get_callback_func($handler) {
    return static::DatabaseSpec[$handler]['callback'];      // 呼び出し関数
}
//==============================================================================
// クローズ処理
private static function closeDb() {
    foreach(static::$dbHandle as $key => $handle) {
        list($handler,$dd) = $handle;
        $func = static::DatabaseSpec[$handler]['callback'];      // 呼び出し関数
        static::$func($dd,NULL,'close');
    }
    self:$dbHandle = [];
}
//==============================================================================
// PostgreSQL データベース
private static function PgDatabase($dbdef,$action) {
    switch($action) {       //   [ .ptl, .tpl, .inc, .twg ]
    case 'open':
        $conn = "host={$dbdef['host']} dbname={$dbdef['database']} port={$dbdef['port']}";
        $conn .= " user={$dbdef['login']} password={$dbdef['password']};";
        $dbb = pg_connect($conn);
        if(!$dbb) {
            debug_log(-1,['DEF'=>$dbdef,'CONNECT'=>$conn]);
            die('Postgres 接続失敗' . pg_result_error($dbb)."\n");
        }
        return $dbb;
    case 'close':
        pg_close($dbdef);
        break;
    case 'query':
        break;
    }
}
//==============================================================================
// SQlite3 データベース
private static function SQLiteDatabase($dbdef,$action) {
    switch($action) {       //   [ .ptl, .tpl, .inc, .twg ]
    case 'open':
        $dbb = new SQLite3($dbdef['database']);
        if(!$dbb) {
            die('SQLite3 接続失敗');
        }
        return $dbb;
    case 'close':
        $dbdef->close();
        break;
    case 'query':
        break;
    }
}
//==============================================================================
// MariaDB(MySQL) データベース(試験的)
private static function MySQLDatabase($dbdef,$action) {
    switch($action) {       //   [ .ptl, .tpl, .inc, .twg ]
    case 'open':
        // DB接続：mysqliクラスをオブジェクト化してから使う
        $dbb = new mysqli($dbdef['host'], $dbdef['login'], $dbdef['password'], $dbdef['database']);
        if($dbb->connect_error) {
            echo $dbb->connect_error;
            die('MariaDB 接続失敗');
        }
        return $dbb;
    case 'close':
        $dbdef->close();
        break;
    case 'query':
        break;
    }
}
//==============================================================================
// 動作確認用(FMDB)
static function Connect($handler,$layout) {
    $dbb = static::$dbHandle[$handler];
}

}
