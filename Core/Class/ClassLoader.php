<?php
/**
 * Classが定義されていない場合に、ファイルを探すクラス
 */
class ClassLoader {
    private static $LoadDirs = [];
    private static function loadClass($className) {
        foreach(static::$LoadDirs as $directory) {
            $file_name = "{$directory}/{$className}.php";
            if(is_file($file_name)) {
//echo "ClassLoader File:{$file_name}<br>\n";
                require_once($file_name);
                return true;
            }
        }
    }
// =================================
// ロードパスの登録
public static function Setup($appname,$controller) {
    static::$LoadDirs = [
        'Core',
        'Core/Base',
        'Core/Class',
        "app/{$appname}/Class",
        "app/{$appname}/extends",
//  以下の2つはAppObjectのマジックメソッドでロードする
//        "app/{$appname}/Models",
//        "app/{$appname}/modules/{$controller}",
    ];
    spl_autoload_register(array('ClassLoader','loadClass'));
}

}