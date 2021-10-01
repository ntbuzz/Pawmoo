<!DOCTYPE html>
<html lang="ja">
<head>
<title><?= $page_title ?></title>
<script src='/js/jquery-3.2.1.min.js' charset='UTF-8'></script>
<link rel='stylesheet' href='/res/css/errmsg.css' />
<link rel='stylesheet' href='res/css/debugbar.css' />
<script src='res/js/debugbar.js' charset='UTF-8'></script>
</head>
<body>
    <div class='message_box'>
        <h2><?= $msg_title ?></h2>
        <?= $msg_body ?>
        <hr>
    <div style="display:flex;">
        <form method="POST">
        <table>
            <tr><th><?= $user_title; ?>:</th><td><input type="text" name="userid" value="<?= $login_user; ?>"></td></tr>
            <tr><th><?= $pass_title; ?>：</th><td>
				<input type="password" name="password" value=""><br>
				<hr>
				<input type="submit" name="login" value="<?= $send_button; ?>">
			</td>
			<td><input type="submit" name="reset" value="<?= $reset_button; ?>" formaction="./pass-reset.html"></td>
			</tr>
        </table>
        </form>
    </div>
    </div>

</body>
</html>

