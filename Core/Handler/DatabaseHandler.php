<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  DatabaseHandler: データベースの接続情報
 */
define('SORTBY_ASCEND', 0);
define('SORTBY_DESCEND', 1);

require_once('Core/Handler/SqlHandler.php');
// 各種データベースへのアクセスハンドラ
require_once('Core/Handler/SQLiteHandler.php');
require_once('Core/Handler/PostgreHandler.php');
require_once('Core/Handler/NullHandler.php');
require_once('Core/Handler/FMDBHandler.php');

//==============================================================================
// データベースの接続情報を保持するクラス
// 全体で１度だけ呼び出す
class DatabaseHandler {
    const DatabaseSpec = [
        // SQLite3データベースへの接続情報
        'SQLite' => [
            'datasource' => 'Database/SQLite3',
            'callback' => 'SQLiteDatabase',
        ],
        // PostgreSQLデータベースへの接続情報
        'Postgre' => [
            'datasource' => 'Database/Postgres',
            'callback' => 'PgDatabase',
        ],
        // MariaDB(MySQL)データベースへの接続情報
        'MySQL' => [
            'datasource' => 'Database/MariaDB',
            'callback' => 'MySQLDatabase',
        ],
    ];
    private static $dbHandle = [];
//==============================================================================
// データベースへ接続してハンドルを返す
public static function get_database_handle($handler) {
    if(array_key_exists($handler,self::DatabaseSpec)) {
        $defs = self::DatabaseSpec[$handler];
        $func = $defs['callback'];      // 呼び出し関数
        self::$dbHandle[$handler] = self::$func(DatabaseParameter[$handler],'open');
        return self::$dbHandle[$handler];
    }
    return NULL;
}
//==============================================================================
// デストラクタ
public static function CloseConnection() {
    self::closeDb();
}
//==============================================================================
// ハンドルの取得
public function get_callback_func($handler) {
    return self::DatabaseSpec[$handler]['callback'];      // 呼び出し関数
}
//==============================================================================
// クローズ処理
private static function closeDb() {
    APPDEBUG::MSG(13, self::$dbHandle);
    foreach(self::$dbHandle as $key => $handle) {
        $func = self::DatabaseSpec[$key]['callback'];      // 呼び出し関数
        self::$func($handle,NULL,'close');
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
            echo "{$conn}\n";
            die('Postgres 接続失敗' . pg_last_error()."\n");
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
// MariaDB(MySQL) データベース
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
    $dbb = self::$dbHandle[$handler];
}

}
