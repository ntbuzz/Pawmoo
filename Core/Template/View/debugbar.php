<div class="debugBar">
    <div class='debugtab'>
        <span class="closeButton"></span>
        <ul id="debugMenu">
<?php
        $level_msg = $this->_("debug.Level");
        $category_msg = $this->_("debug.Level.Category",TRUE);
        $debug_log_str = get_debug_logs();
        foreach($debug_log_str as $key => $msg) {
            $title = (isset($category_msg[$key])) ? $category_msg[$key]:"{$level_msg}:{$key}";
            echo "<li>{$title}</li>\n";
        }
?>
    	</ul>
    </div>
    <div class='debug-panel'>
    	<ul class="dbcontent">
<?php
        foreach($debug_log_str as $key => $msg) {
            echo "<li><div class=\"debug_srcollbox\">\n";
            echo $msg;
            echo "</div></li>\n";
        }
?>
        </ul>
    </div>
</div>
<div class="debugBK"></div>
