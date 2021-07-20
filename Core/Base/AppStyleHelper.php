<?php

class AppStyleHelper {
    protected $AOwner;      // Object Owner
    protected $ClassName;   // Class Name
//==============================================================================
//	constructor( object owner )
	function __construct($owner = NULL) {
        $this->AOwner = $owner;
        $this->ClassName = get_class($this);
	}
//==============================================================================
//	Calendar Month Array to commaText
public function monthNameText() {
    $month = LangUI::get_value('core', '.monthNameArray', true);
	return "'".implode("','",$month)."'";
debug_die(['MONTH'=>$month]);
}


}
