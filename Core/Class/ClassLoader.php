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
//==============================================================================
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
// modules/XXXX のファイルは AppObject に任せる
    }
//==============================================================================
// ロードパスが固定されているものを登録
public static function Setup($appname,$controller=NULL) {
    static::$LoadDirs = [
        'Core/Class',
        "app/.share/Class",
        "app/.share/extends",
        "app/.share/Models",
        "app/{$appname}/Class",
        "app/{$appname}/extends",
        "app/{$appname}/Models",
        "app/{$appname}/Models/Asst",
        "app/{$appname}/Models/Misc",
    ];
	if($controller !== NULL) static::$LoadDirs[] = "app/{$appname}/modules/{$controller}";
    spl_autoload_register(array('ClassLoader','loadClass'));
}

}