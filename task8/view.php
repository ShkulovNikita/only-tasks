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
    <div class="row">
        <?php 
        if ($file !== '') {
            ?>
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
                <h3><?=$file['name']?></h3>
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
                <form action="download.php" method="POST">
                    <input type="hidden" name="download" value="<?=$file['name']?>">
                    <input type="submit" class="btn btn-info" value="Скачать">
                </form>
                <form action="delete.php" method="POST">
                    <input type="hidden" name="fileForDelete" value="<?=$file['name']?>">
                    <input type="submit" class="btn btn-danger" value="Удалить">
                </form>
                <a href="edit.php?name=<?=$file['name']?>" class="btn btn-primary">Редактировать</a>
                <a href="index.php" class="btn btn-primary">Назад</a>
            </div>
            <?php
        }
        ?>
    </div>
    <div class="row">
        <?=HtmlHelper::showFooter();?>
    </div>
</div>
</body>
</html>
