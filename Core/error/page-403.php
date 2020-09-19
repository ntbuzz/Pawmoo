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
        debug_log(-1,['info' => $_SESSION]);
        ?>
        <hr>
        return to <a href='/index.html'>TOP</a>
    </div>
</body>
</html>