<!DOCTYPE html>
<head>
    <title>Авторизация</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8" />
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
    <!-- Подключение Bootstrap. -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <!-- Собственные стили. -->
    <link rel="stylesheet" type="text/css" href="styles/styles.css"/>
</head>
<body>
<?php
    require_once 'classes/user.php';
    require_once 'classes/application.php';
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