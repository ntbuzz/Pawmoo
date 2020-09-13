<html>
<head>
<link rel='stylesheet' href='/res/css/errmsg.css' />
</head>
<body>
    <div class='error_box'>
        <h1>403</h1>
        <hr>
        <p>Page: '<?= $page_name; ?>' Forbidden.</p>
        <hr>
        return to <a href='<?= $app_root; ?>'>TOP</a>
    </div>
</body>
</html>