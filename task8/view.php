<?php
require 'vendor/autoload.php';

use AppClasses\{Drive, HtmlHelper, FileHelper};

echo HtmlHelper::showProlog('Просмотр файла');
?>
<body>
<?php
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
            if (isset($file['sizes'])) {
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
        <div class="col-md-6" class="file-menu">
            <h3><?=$file['name']?></h3>
            <p>Дата создания: <?=FileHelper::getFileDate($file->created)?></p>
            <p>Последнее изменение: <?=FileHelper::getFileDate($file->modified)?></p>
            <p>Размер: <?=FileHelper::getFileSize($file->size)?></p>
            <p>Тип файла: <?=$file->mime_type?></p>
            <a href="<?=$file['docviewer']?>" target="_blank" class="btn btn-secondary yandex_button">Открыть в Яндекс.Диске</a>
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