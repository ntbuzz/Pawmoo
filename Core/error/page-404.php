<html>
<head>
<style>
.error_box {
    position:relative;
    width:50%;
    height: auto;
    padding: 0 50px;
    margin: 0 auto;
    background-color: whitesmoke;
}
</style>
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
            debug_dump(1,['dbinfo' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,3)]);
        ?>
    </div>
</body>
</html>