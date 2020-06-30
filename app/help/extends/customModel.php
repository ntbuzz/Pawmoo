<?php

//==============================================================================
class customModel  extends AppModel {

function findKeyword($qstr,$filter) {
    $this->RecordFinder([['title' => "{$qstr}",'contents' => "{$qstr}"] ],$filter,'disp_id' );
    $outline= array();
    foreach($this->Records as $record) {
        $line = explode("\n",str_replace("\r\n","\n",$record['contents']));
        array_splice($line,8);
        $record['contents'] = implode("\n",$line);
        $outline[] = $record;
    }
    debug_dump(0,["outline" => $outline]);
    $this->outline = $outline;
}

}
