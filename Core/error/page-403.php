<!DOCTYPE html>
<html>
<head>
<link rel='stylesheet' href='/res/css/errmsg.css' />
</head>
<body>
    <div class='error_box'>
        <h1>403</h1>
        <hr>
        <p>Page: '<?= $app_name; ?>' Forbidden.</p>
        <p>Your Permission is not allows this Application.</p>
        <?php
        debug_log(100,['info' => $_SESSION,'POST' => $_REQUEST]);
        ?>
        <hr>
        return to <a href='<?= $sys_root; ?>index.html'>TOP</a>
    </div>
</body>
</html>