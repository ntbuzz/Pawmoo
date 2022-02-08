<?php
/**
 * Classが定義されていない場合に、ファイルを探すクラス
 */
class ClassLoader {
    const CLASSMAP = [
        'FlatFolder' => 'fileclass',
        'SectionParser' => 'XParser',
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
        "Models/Common",
        "Models/Misc",
	];
	$folders = [];
	foreach($auto as $key) {
		foreach($auto_folder as $folder) {
			$dir = "app/{$key}/{$folder}";
			if(is_dir($dir)) $folders[] = $dir;		// フォルダがあるか確かめる
		}
	}
	$folders[] = 'Core/Class';
	static::$LoadDirs = $folders;
    spl_autoload_register(array('ClassLoader','loadClass'));
}

}