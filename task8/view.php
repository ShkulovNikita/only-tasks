<!DOCTYPE html>
<html>
<head>
<?php
require 'vendor/autoload.php';

use AppClasses\{HtmlHelper, FileHelper};
use Controllers\FileController;

echo HtmlHelper::showProlog('Просмотр файла');
?>
</head>
<body>
<?php
$file = FileController::view();
?>
<div class="container-fluid">
    <?=HtmlHelper::showHeader();?>
    <div class="row wide-errors">
        <?=HtmlHelper::showMessage();?>
    </div>
    <?php 
    if ($file !== '') {
    ?>
        <div class="row main-content view-content">
            <!-- Изображение для файла. -->
            <div class="col-6" class="file-image">
                <?php
                if ($file->has('sizes')) {
                    foreach ($file['sizes'] as $fileSize) {
                        if ($fileSize['name'] == 'XL') { 
                            ?>
                            <img src="<?=$fileSize['url']?>" class="file-image__image">
                            <?php
                            break;
                        }
                    }
                } else {
                    ?>
                    <img src="<?=FileHelper::getFilePreview($file->mime_type)?>" class="file-image__image">
                    <?php
                }
                ?>
            </div>
            <!-- Вывести информацию о файле. -->
            <div class="col-6" class="file-menu">
                <h3 class="view-content__file-name"><?=$file['name']?></h3>
                <div class="view-content__buttons-menu">
                    <div class="view-content__edit-button">
                        <a href="edit.php?name=<?=$file['name']?>" class="btn btn-secondary button_yellow">Редактировать</a>
                    </div>
                    <div class="view-content__download-button">
                        <form action="download.php" method="POST">
                            <input type="hidden" name="download" value="<?=$file['name']?>">
                            <input type="submit" class="btn btn-secondary button_yellow" value="Скачать">
                        </form>
                    </div>
                    <div class="view-content__delete-button">
                        <form action="delete.php" method="POST">
                            <input type="hidden" name="fileForDelete" value="<?=$file['name']?>">
                            <input type="submit" class="btn btn-danger" value="Удалить">
                        </form>
                    </div>
                </div>
                <div class="view-content__file-info">
                    <h5>Информация о файле:</h5>
                    <p>Дата создания: <?=FileHelper::getFileDate($file->created)?></p>
                    <p>Последнее изменение: <?=FileHelper::getFileDate($file->modified)?></p>
                    <p>Размер: <?=FileHelper::getFileSize($file->size)?></p>
                    <p>Тип файла: <?=$file->mime_type?></p>
                    <?php
                    $properties = $file->getProperties();
                    if ($properties) {
                        ?>
                        <h5>Метаинформация:</h5>
                        <?php
                        foreach ($properties as $propertyKey => $propertyValue) {
                            ?>
                            <p><?=$propertyKey?>: <?=$propertyValue?></p>
                            <?php
                        }
                    }
                    ?>
                    <a href="<?=$file['docviewer']?>" target="_blank" class="btn btn-secondary yandex-button">Открыть в Яндекс.Диске</a>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
    <?=HtmlHelper::showFooter();?>
</div>
</body>
</html>
