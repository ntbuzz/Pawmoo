<div class="debugBar">
    <div class='debugtab'>
        <span class="closeButton"></span>
        <ul id="debugMenu">
<?php
        $level_msg = $this->_("core.Debug.Level");
        foreach(APPDEBUG::$LevelMsg as $key => $msg) {
            echo "<li>{$level_msg}:{$key}</li>\n";
        }
?>
    	</ul>
    </div>
    <div class='debug-panel'>
    	<ul class="dbcontent">
<?php
        foreach(APPDEBUG::$LevelMsg as $key => $msg) {
            echo "<li><div class=\"debug_srcollbox\">\n";
            echo $msg;
            echo "</div></li>\n";
        }
?>
        </ul>
    </div>
</div>
<div class="debugBK"></div>
