<!DOCTYPE html>
<head>
    <title>Главная страница</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="utf-8" />
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
    <!-- Подключение Bootstrap. -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <!-- Собственные стили. -->
    <link rel="stylesheet" type="text/css" href="styles/styles.css"/>
</head>
<body>
<?php
require 'vendor/autoload.php';

use AppClasses\{User, Drive, HtmlHelper};

if (
    $_FILES && $_FILES["filename"]["error"] == UPLOAD_ERR_OK 
    || isset($_POST['fileurl']) && !empty($_POST['fileurl'])
) {
    Drive::uploadFile();
}
?>
<div class="container">
    <div class="row">
        <div class="col-md-6">
            <?=HtmlHelper::showMessage();?>
        </div>
    </div>
    <?=HtmlHelper::showHeader()?>
    <form method="POST" enctype="multipart/form-data">
        <label for="filename">Выберите файл</label>
        <input type="file" name="filename" size="10"><br />
        <label for="fileurl">Либо введите URL</label>
        <input type="url" name="fileurl"><br />
        <input type="submit" class="btn btn-primary" value="Отправить">
    </form>
    <?=HtmlHelper::showFooter()?>
</div>