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
<div class="container-fluid">
    <?=HtmlHelper::showHeader();?>
    <div class="row wide-errors">
        <?=HtmlHelper::showMessage();?>
    </div>
    <div class="row main-content">
        <div class="col-12">
            <h3 class="authorization__title">Вход в систему</h3>
        </div>
        <div class="col-12">
            <form method="POST">
                <a href="<?=$tokenLink?>" target="_blank" class="btn btn-secondary yandex-button">Получить токен</a>
                <input type="text" name="token" value="<?=User::getToken()?>"/>
                <input type="submit">
            </form>
            <a href="index.php">Назад</a>
        </div>
    </div>
</div>







</body>
</html>
