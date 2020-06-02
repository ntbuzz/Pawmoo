<html>
<body>
<pre>
<?php
var_dump($_SESSION);
?>
</pre>
<form method="POST" action="index">
<ul>
<li> ユーザー名: <input type="text" name="user" value=""></li>
<li> パスワード：<input type="password" name="password" value=""></li>
<li> EMaile: <input type="text" name="email" value=""></li>
</ul>
<input type="hidden" name="Login" value="1">
<input type="submit" value="SEND">
</fomr>
</body>
</html>

