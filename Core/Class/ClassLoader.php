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
public static function Setup($appname) {
	$auto = (SHARE_FOLDER_USE) ? [ $appname,'.share'] : [$appname];
	$auto_folder = [
        "Class",
        "extends",
        "Models",
        "Models/Misc",
        "Models/Common",
	];
	$folders = [];
	foreach($auto as $key) {
		foreach($auto_folder as $folder) $folders[] = "app/{$key}/{$folder}";
	}
	$folders[] = 'Core/Class';
	static::$LoadDirs = $folders;
    spl_autoload_register(array('ClassLoader','loadClass'));
}

}