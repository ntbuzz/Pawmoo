<?php
//==============================================================================
// Databas Table Create Class
class AppSchema extends AppBase {
   static $DatabaseSchema = [
        'Handler' => HANDLER,
        'DataTable' => 'host_lists',
        'Primary' => 'id',
		'Lang_Alternate' => TRUE,
		'Schema' => [
            'id'				=> ['serial',	00100,	32],
            'active'			=> ['boolean',  00122,	22],
            'status_id'			=> ['integer',  00122,	22],
			'name_list_id'		=> ['integer',	00100,	NULL,
				// 連想配列で定義されたものはVIEWで定義、WHERE句の使用可
				'Names.id' => [
					'host_name'		=> ['name', 	01002,  50],
					'host_name_en'	=> 'name_en',
					'host_name_desc'=> ['description',  	01002,  50],
					'host_name_desc_en'=> 'description_en',
					'bind_name'		=> [ ['source','name'],  01002,  50],
					'bind_name_en'	=> [ ['name','source'] ],
				],
			],
            'product_name'			=> ['text',		01102,	0],
            'product_name_en'		=> ['text',		00100],
			'provider_id'	=> ['integer',  00102,  NULL,
				'Providers.id' => [
					'provider_name' => [ 'name',  01102,  50],
					'provider_name_en' => 'name_en',
				],
			],
            'operating_system_id'=> ['integer',	00102,	 NULL,
				'Os.id' => [
					'os_name'		=> ['name',				00002,	 0],
					'os_name_en'	=> 'name_en',
					'os_family_id'	=> ['os_family_id',		00002,	 0],
					'os_family_name'=> ['os_family_name',	00002,	 0],
				],
			],
            'location'			=> ['text', 	00101],
            'entity'			=> ['text',   	00101],
            'locationentity'	=> ['text',		00001,	 0,	
				['location','／' => 'entity']
			],
			'license_id' => ['integer',	00100,			NULL,
				'Licenses.id' => [
					'os_license'	=> ['license',	00002,	 0],
					'os_license_en'	=> 'license_en',
				],
			],
			'desktop_id' => ['integer',	00100,	NULL,
				'Desktops.id' => [
					'desktop' => [	'desktop', 00002,	0],
					'desktop_en' => 'desktop',
				],
			],
			'multibind_name' => [ 'text',		00000,	 0,	
				[ ['Licenses.license','Os.name'] ]
			],
		],
    ];
	public $FieldSchema;
	public $columns;
	public $raw_columns;
	public $locale_columns;
	public $bind_columns;
	public $ViewSchema;
	public $TableName;
	public $ViewName;
	public $ViewSet;

//==============================================================================
// setup table-name and view-table list
	function __construct() {
	    parent::__construct();                    // call parent
		$this->ClassName = get_class($this);
        foreach(static::$DatabaseSchema as $key => $val) $this->$key = $val;
		$viewset = (isset($this->DataView)) ? ((is_array($this->DataView)) ? $this->DataView : [$this->DataView]) : [];
		if(is_array($this->DataTable)) {
			list($table,$view) = $this->DataTable;
			if($table !== $view) array_unshift($viewset, $view);
		} else $table = $this->DataTable;
		$this->MyTable = $table;
		$this->ViewSet = $viewset;
        $driver = $this->Handler . 'Handler';
        $this->dbDriver = new $driver($this->DataTable,$this->Primary);        // connect Database Driver
    }
//==============================================================================
// Switch Schema Language
public function SchemaSetup() {
    $this->SchemaAnalyzer();
	$this->ResetLocation();
	debug_log(DBMSG_MODEL,[             // DEBUG LOG information
        $this->ModuleName => [
			'FIELD' => $this->FieldSchema,
			'COLUMN' => $this->columns,
			'RAW-COLUMN' => $this->raw_columns,
			// 'LOCALE' => $this->locale_columns,
			// 'BIND' => $this->bind_columns,
			'VIEW' => $this->ViewSchema,
			"Relations"     => $this->dbDriver->relations,
//            "Select-Defs"   => $this->SelectionDef,
			"Locale-Bind"   => $this->dbDriver->fieldAlias->GetAlias(),
		],
	]);
}
//==============================================================================
//	Constructor: Owner
public function SchemaAnalyzer() {
	$this->FieldSchema=$this->ViewSchema = [];
	$this->columns=$this->raw_columns=[];
	$this->bind_columns=[];
	$setup_field = function($key,$defs) {
		if(is_scalar($defs)) $defs = [$defs];
		list($dtype,$flag,$width,$rel) = array_extract($defs,4);
		list($disphead,$align,$csv,$lang) = oct_extract($flag,4);
		if(is_int($width))
		$this->FieldSchema[$key] = [$disphead,$width,$align,$csv,$lang];
		return [$dtype,$rel];
	};
	foreach($this->Schema as $key => $defs) {
		list($dtype,$rel) = $setup_field($key,$defs);
		list($dtype,$flag,$width,$rel) = array_extract($defs,4);
		$dtype = strtolower($dtype);
		$this->columns[$key] = [$dtype,$flag,$width];
		if(is_array($rel)) {
			list($link,$def) = array_first_item($rel);
			list($dtype,$flag,$width,$rel) = array_extract($defs,4);
			if(is_int($link)) {
				$this->columns[$key] = ['bind',$flag,$width];
				if(is_array($def)) $this->ViewSchema[] = [ $key => $def];
				else $this->bind_columns[$key] = $rel;
				//unset($this->raw_columns[$key]);
			} else{
				$view = [];
				$this->raw_columns[$key] = $dtype;
				foreach($def as $kk => $vv) {
					list($alias,$rr) = $setup_field($kk,$vv);
					list($dtype,$flag,$width,$rel) = array_extract($vv,4);
//					$this->columns[$kk] = (is_array($alias))?'alias-bind':'alias';
					$atype = (is_array($alias))?'alias-bind':'alias';
					$this->columns[$kk] = ($width===NULL)?$atype:[$atype,$flag,$width];
					$view[$kk] = $alias;
				}
				$this->ViewSchema[$link] = $view;
			}
		} else $this->raw_columns[$key] = $dtype;
	}
}
//==============================================================================
//	Constructor: Owner
protected function ResetLocation() {
	$this->locale_columns=[];
	foreach($this->FieldSchema as $key => $defs) {
		if($defs[4]) {
			$locale_name = "{$key}_" . LangUI::$LocaleName;
			if(array_key_exists($locale_name,$this->columns)) {
				$this->locale_columns[$key] = $locale_name;
			}
		}
	}
	$this->dbDriver->fieldAlias->SetupAlias($this->locale_columns,$this->bind_columns);
}

}