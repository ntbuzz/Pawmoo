<!DOCTYPE html>
<html lang="ja">
<head>
<title><?= $page_title ?></title>
<link rel='stylesheet' href='/res/css/errmsg.css' />
</head>
<body>
    <div class='message_box'>
        <h2><?= $msg_title ?></h2>
        <?= $msg_body ?>
        <hr>
        <form method="POST">
        <table>
            <tr><th><?= $user_title; ?>:</th><td><input type="text" name="userid" value="<?= $login_user; ?>"></td></tr>
            <tr><th><?= $pass_title; ?>：</th><td><input type="password" name="password" value=""></td></tr>
            <tr><td colspan="2" align="center"><input type="submit" value="<?= $send_button; ?>"></td></tr>
        </table>
        </form>
    </div>
</body>
</html>

