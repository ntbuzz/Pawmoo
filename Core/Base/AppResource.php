<?php
/* -------------------------------------------------------------
 * Object Oriented PHP MVC Framework
 *  AppResource:   css, js joined by template
 */
class AppResource extends AppObject {
    const ResourceList = [
        'stylesheet' => [
            'folder' => 'css',
            'head' => 'text/css',
            'extention' => '.css',
        ],
        'javascript' => [
            'folder' => 'js',
            'head' => 'text/javascript',
            'extention' => '.js',
        ],
    ];
    const FunctionList = array(
        '@'    => [
            'compact'   => [ 'cmd_modeset','do_min' ],
            'comment'   => [ 'cmd_modeset','do_com' ],
            'message'   => [ 'cmd_modeset','do_msg' ],
            'charset'   => 'cmd_charset',
        ],
        '+'    => [
            'import'    => 'cmd_import',
            'section'   => 'cmd_section',
            'jquery'    => 'cmd_jquery',
            'style'    => 'cmd_style',
        ],
        '*'  => 'do_comment',
    );
	private $debug_mode = false;	// for debug
	const DebugModeSet = [
            'do_min' => false,		// Is Compact Output?
            'do_com' => true,		// Is Import Message?
            'do_msg' => true,		// Is Comment Output?
	];
//==============================================================================
// Constructor
    function __construct($owner) {
		parent::__construct($owner);
        // Module(res) will be Common URI Modele
        $this->Folders = [];
		$this->debug_mode = !is_bool_false(MySession::getSysData('debugger'));
    }
//==============================================================================
// Search resource Folder, Module, Application, Framework
    private function get_exists_files($name) {
        $arr = array();
        foreach($this->Folders as $key => $file) {
            $fn ="{$file}/{$name}";
            if(file_exists($fn)) {
                $arr[$key] = $fn;
            }
        }
        return $arr;
    }
//==============================================================================
public function SetTemplate($template) {
	$path = explode('/',$template);
	$appname = App::$AppName;
	$modname = $this->ModuleName;
	$this->Folders = [
		"{$appname}共通" => App::Get_AppPath('View/res'),
		'Libs' => 'Core/Template/res',
	];
	if($path[2]!=='View') {
        array_unshift($this->Folders,
			["{$modname}固有" => App::Get_AppPath("modules/{$modname}/res")]);
	}
	debug_log(0,['TEMPLATE'=>$template,'IMPORT-FOLDER'=>$this->Folders]);
}
//==============================================================================
// Style Template Output
public function SectionAnalyz($filetype,$defs) {
	if(!array_key_exists($filetype,self::ResourceList)) return;
	$this->FolderInfo = self::ResourceList[$filetype];
	foreach($defs as $id => $val) {
		if(is_int($id)) {
			$this->Template($val);
		} else {
			$top_char = mb_substr($id,0,1);
			$funcs = self::FunctionList[$top_char];
			$cmd = mb_substr($id,1);
			switch($top_char) {
			case '+':
					if(array_key_exists($cmd,$funcs)) {
						$method = $funcs[$cmd];
						if(method_exists($this,$method)) {
							$this->$method($val);
						} else echo "Method({$method}): NOT FOUND\n";
					} else echo "ERROR: NOT IMPLEMENTED ({$id})\n";
					break;
			case '@':
					$file = $cmd.$this->FolderInfo['extention'];
					echo "IMPORT FILE ({$file})\n";
					break;
			case '*':
					echo "/* {$cmd} */\n";
					break;
			}
		}
	}
}
//==============================================================================
// Style Template Output
	private function Template($val) {
		echo "resource IMPORT ({$val})\n";
	}
//==============================================================================
// Style Template Output
	private function cmd_style($val) {
		$val = array_to_text($val);
		echo $val;
	}

}
