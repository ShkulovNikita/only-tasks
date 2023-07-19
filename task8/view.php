<!DOCTYPE html>
<head>
    <title>Просмотр файла</title>
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

use AppClasses\{Drive, HtmlHelper, FileHelper};

$file;
if (isset($_GET['name'])) {
    $file = Drive::viewFile(htmlspecialchars($_GET['name']));
}
?>

<div class="container">
    <div class="row">
        <?=HtmlHelper::showHeader();?>
    </div>
    <div class="row">
        <!-- Изображение для файла. -->
        <div class="col-md-6" class="file-image">
            <?php
            if (str_contains($file->mime_type, 'image') && isset($file['sizes'])) {
                foreach ($file['sizes'] as $fileSize) {
                    if ($fileSize['name'] == 'XL') { 
                        ?>
                        <img class="file-image__image" src="<?=$fileSize['url']?>">
                        <?php
                        break;
                    }
                }
            }
            ?>
        </div>
        <!-- Вывести информацию о файле. -->
        <div class="col-md-6" class="file-menu">
            <h3><?=$file['name']?></h3>
            <p>Дата создания: <?=FileHelper::getFileDate($file->created)?></p>
            <p>Последнее изменение: <?=FileHelper::getFileDate($file->modified)?></p>
            <p>Размер: <?=FileHelper::getFileSize($file->size)?></p>
            <p>Тип файла: <?=$file->mime_type?></p>
            <a href="<?=$file['docviewer']?>" target="_blank" class="btn btn-primary">Открыть в Яндекс.Диске</a>
            <form action="download.php" method="POST">
                <input type="hidden" name="download" value="<?=$file['name']?>">
                <input type="submit" class="btn btn-info" value="Скачать">
            </form>
            <form action="delete.php" method="POST">
                <input type="hidden" name="fileForDelete" value="<?=$file['name']?>">
                <input type="submit" class="btn btn-danger" value="Удалить">
            </form>
            <a href="index.php" class="btn btn-primary">Назад</a>
        </div>
    </div>
    <div class="row">
        <?=HtmlHelper::showFooter();?>
    </div>
</div>
</body>