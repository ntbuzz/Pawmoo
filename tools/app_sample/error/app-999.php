<!DOCTYPE html>
<html>
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
            <tr><th><?= LangUI::get_value('core','.UserName') ?>:</th><td><input type="text" name="username" value=""></td></tr>
            <tr><th><?= LangUI::get_value('core','.Password') ?>：</th><td><input type="password" name="password" value=""></td></tr>
            <tr><td colspan="2" align="center"><input type="submit" value="<?= LangUI::get_value('core','.SEND') ?>"></td></tr>
        </table>
        </form>
    </div>
</body>
</html>

