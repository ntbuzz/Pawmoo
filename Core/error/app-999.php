<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<link rel='stylesheet' href='/res/css/errmsg.css' />
</head>
<body>
    <div class='error_box'>
        <h2><?= $app_module ?></h2>
        <?= $page_name ?>
        <hr>
        <form method="POST">
        <table>
            <tr><th>ユーザー名:</th><td><input type="text" name="userid" value=""></td></tr>
            <tr><th>パスワード：</th><td><input type="password" name="password" value=""></td></tr>
            <tr><td colspan="2" align="center"><input type="submit" value="SEND"></td></tr>
        </table>
        </form>
    </div>
</body>
</html>

