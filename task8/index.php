<!DOCTYPE html>
<html>
<head>
<?php
require 'vendor/autoload.php';

use AppClasses\{User, Drive, HtmlHelper, FileHelper};

echo HtmlHelper::showProlog('Главная страница');
?>
</head>
<body>
<?php 
/*
 * Получение списка файлов. 
 */
if (User::isAuthorized() === true) {
    $files = Drive::getFiles();
}
?>
<div class="container-fluid">
    <?=HtmlHelper::showHeader()?>
    <!-- Кнопки для входа либо работы с файлами. -->
    <div class="row">
        <?php
        if (User::isAuthorized() === false) {
            ?>
            <div class="col-md-4">
                <a id="signin-button" class="btn btn-primary" href="signin.php">Войти</a>
            </div>
            <?php
        }
        ?>
    </div>
    <!-- Вывод списка файлов. -->
    <?php
    if (User::isAuthorized() === true) {
        ?>
        <!-- Вывод списка файлов -->
        <div class="row">
            <?php
            if (count($files) == 0) {
                ?>
                <div class="col-md-6">
                    <p>Нет загруженных файлов приложения на Диске.</p>
                </div>
                <?php
            }
            foreach ($files as $file) { ?>
                <div class="col-md-3 file_item">
                    <?php 
                    if (isset($file['preview'])) { 
                        ?>
                        <img src="<?=$file['preview']?>" class="file-list__preview-image">
                        <?php 
                    } else {
                        ?>
                        <img src="<?=FileHelper::getFilePreview($file->mime_type)?>" class="file-list__preview-image">
                        <?php
                    }
                    ?>
                    <p class="file_name"><?=$file['name']?></p>
                    <a href="view.php?name=<?=$file['name']?>" class="btn btn-success">Просмотр</a>
                    <form action="download.php" method="POST">
                        <input type="hidden" name="download" value="<?=$file['name']?>">
                        <input type="submit" class="btn btn-info" value="Скачать">
                    </form>
                    <form action="delete.php" method="POST">
                        <input type="hidden" name="fileForDelete" value="<?=$file['name']?>">
                        <input type="submit" class="btn btn-danger" value="Удалить">
                    </form>
                </div>
                <?php
            } 
            ?>
        </div>
        <?php
    }
    ?>
    <?=HtmlHelper::showFooter()?>
</div>
</body>
</html>
