<div class="debugBar">
    <div class='debugtab'>
        <ul id="debugMenu">
        <li><?= $this->Helper->ALink('/index/list','【HOME】'); ?></li>
<?php
        foreach(APPDEBUG::$LevelMsg as $key => $msg) {
            echo "<li>レベル:{$key}</li>\n";
        }
?>
    	</ul>
    </div>
    <div class='debug-panel'>
    	<ul class="dbcontent">
        <li></li>
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
