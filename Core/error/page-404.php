<html>
<head>
<style>
.error_box {
    position:relative;
    width:50%;
    height: 15em;
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
    </div>
</body>
</html>