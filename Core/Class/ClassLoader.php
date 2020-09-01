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
                echo "Load Class File:{$file_name}\n";
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
        "app/{$appname}/Models",
        "app/{$appname}/modules/{$controller}",
    ];
    spl_autoload_register(array('ClassLoader','loadClass'));
}

}