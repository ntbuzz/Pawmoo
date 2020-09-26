<!DOCTYPE html>
<html>
<head>
<link rel='stylesheet' href='/res/css/errmsg.css' />
</head>
<body>
    <div class='error_box'>
        <h1>404</h1>
        <hr>
        <p>Page: '<?= $page_name; ?>' not found.</p>
        <hr>
        return to <a href='<?= $app_root; ?>'>TOP</a>
        <?php
            // backtrace
            debug_log(-1,['dbinfo' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,3)]);
        ?>
    </div>
</body>
</html>