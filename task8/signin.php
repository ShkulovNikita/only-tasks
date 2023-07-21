<!DOCTYPE html>
<html>
<head>
<?php
require 'vendor/autoload.php';

use AppClasses\{User, HtmlHelper, Application};
use Controllers\AuthorizationController;

echo HtmlHelper::showProlog('Авторизация');
?>
</head>
<body>
<?php
$tokenLink = Application::getAppTokenLink();
AuthorizationController::signin();
?>
<?=HtmlHelper::showMessage('error');?>
<form method="POST">
    <a href="<?=$tokenLink?>" target="_blank" class="btn btn-secondary yandex-button">Получить токен</a>
    <input type="text" name="token" value="<?=User::getToken()?>"/>
    <input type="submit">
</form>
<a href="index.php">Назад</a>
</body>
</html>
