<?php
require 'vendor/autoload.php';

use AppClasses\{User, HtmlHelper, Application};

echo HtmlHelper::showProlog('Авторизация');
?>
<body>
<?php
$tokenLink = Application::GetAppTokenLink();
/*
 * Если в форму было введено значение токена, то сохранить его. 
 */
if (isset($_POST['token']) && !empty($_POST['token'])) {
    User::authorizeUser($_POST['token']);
}
?>
<form method="POST">
    <a class="btn btn-primary" href="<?=$tokenLink?>" target="_blank">Получить токен</a>
    <input type="text" name="token" value="<?=User::getToken()?>"/>
    <input type="submit">
</form>
<a href="index.php">Назад</a>
</body>