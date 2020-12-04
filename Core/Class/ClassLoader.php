<?php
/**
 * Classが定義されていない場合に、ファイルを探すクラス
 */
class ClassLoader {
    const CLASSMAP = [
        'FlatFolder' => 'fileclass',
        'SectionParser' => 'Parser',
        'MySession' => 'session',
    ];
    private static $LoadDirs = [];
    private static function loadClass($className) {
        if(array_key_exists($className,self::CLASSMAP)) {
            $className = self::CLASSMAP[$className];
        }
        foreach(static::$LoadDirs as $directory) {
            $file_name = "{$directory}/{$className}.php";
            if(is_file($file_name)) {
                require_once($file_name);
                return true;
            }
        }
//        echo "Not found ClassName:{$className}\n";
    }
// =================================
// ロードパスの登録
public static function Setup($appname,$controller) {
    static::$LoadDirs = [
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