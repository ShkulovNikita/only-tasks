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
            <div class="authorization-form">
                <form method="POST">
                    <div class="text-center authorization-form__token-button">
                        <a href="<?=$tokenLink?>" target="_blank" class="btn btn-secondary yandex-button">Получить токен</a>
                    </div>
                    <div class="text-center">
                        <label for="token">Введите токен:</label>
                        <input type="text" name="token" value="<?=User::getToken()?>" class="form-control"/>
                        <input type="submit" class="btn btn-secondary button_yellow authorization-form__submit" value="Войти"/>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
