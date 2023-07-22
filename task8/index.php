<!DOCTYPE html>
<html>
<head>
<?php
require 'vendor/autoload.php';

use AppClasses\{User, HtmlHelper, FileHelper, PageNavigator};
use Controllers\FileController;

echo HtmlHelper::showProlog('Главная страница');
?>
</head>
<body>
<?php 
/*
 * Получение списка файлов. 
 */
$files = FileController::index();
/*
 * Параметры постранички. 
 */
$pageNavigator = [];
if ($files) {
    $pageNavigator = PageNavigator::getPageNavigator($files);
}
?>
<div class="container-fluid">
    <?=HtmlHelper::showHeader()?>
    <!-- Кнопка для авторизации. -->
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
                <div class="col-6">
                    <p>Нет загруженных файлов приложения на Диске.</p>
                </div>
                <?php
            }
            /*
             * Последний из файлов не выводится, так как используется
             * для постранички. 
             */
            $counter = 0;
            $limit = PageNavigator::getPublicLimit();
            foreach ($files as $file) { 
                if ($counter < $limit) {
                    ?>
                    <div class="col-3 file_item">
                        <?php 
                        if ($file->has('preview')) { 
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
                <?php
                $counter++;
            } 
            ?>
            <!-- Вывести кнопки постраничной навигации -->
            <form method="GET">
                <?php
                if (isset($pageNavigator['previous'])) {
                    ?>
                    <input type="submit" class="btn btn-primary" name="current_page" value="<?=$pageNavigator['previous']?>">
                    <?php
                }
                if (isset($pageNavigator['current'])) {
                    ?>
                    <p><?=$pageNavigator['current']?></p>
                    <?php
                }
                if (isset($pageNavigator['next'])) {
                    ?>
                    <input type="submit" class="btn btn-primary" name="current_page" value="<?=$pageNavigator['next']?>">
                    <?php
                }
                ?>
            </form>
        </div>
        <?php
    }
    ?>
    <?=HtmlHelper::showFooter()?>
</div>
</body>
</html>
