<?php
/* -------------------------------------------------------------
 * PHPフレームワーク
 *  DatabaseHandler: データベースの接続情報
 */
require_once('Core/Handler/SqlHandler.php');
// 各種データベースへのアクセスハンドラ
require_once('Core/Handler/SQLiteHandler.php');
require_once('Core/Handler/PostgreHandler.php');
require_once('Core/Handler/FMDBHandler.php');

//==========================================================================================
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
    ];
    private static $dbHandle = [];
//==========================================================================================
// コンストラクタ
public static function InitConnection() {
    APPDEBUG::MSG(13, self::DatabaseSpec);
    foreach(self::DatabaseSpec as $dbName => $defs) {
        $func = $defs['callback'];      // 呼び出し関数
        self::$dbHandle[$dbName] = self::$func(DatabaseParameter[$dbName],'open');
        APPDEBUG::MSG(13, self::$dbHandle);
    }
}
//==========================================================================================
// デストラクタ
public static function CloseConnection() {
    self::closeDb();
}
//==========================================================================================
// ハンドルの取得
public static function getDataSource($dbName) {
    return self::$dbHandle[$dbName];
}
//==========================================================================================
// ハンドルの取得
public function getCallbackFunc($dbName) {
    return self::DatabaseSpec[$dbName]['callback'];      // 呼び出し関数
}
//==========================================================================================
// クローズ処理
private static function closeDb() {
    APPDEBUG::MSG(13, self::$dbHandle);
    foreach(self::$dbHandle as $key => $handle) {
        $func = self::DatabaseSpec[$key]['callback'];      // 呼び出し関数
        self::$func($handle,'close');
    }
    self:$dbHandle = [];
}
//==========================================================================================
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
//==========================================================================================
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
//==========================================================================================
// 動作確認用(FMDB)
static function Connect($dbName,$layout) {
    $dbb = self::$dbHandle[$dbName];
    // 先頭レコードを読み込む
}

}
