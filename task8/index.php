<!DOCTYPE html>
<html>
<head>
<?php
require 'vendor/autoload.php';

use AppClasses\{User, HtmlHelper, FileHelper, PageNavigator, Session};
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
    <?=HtmlHelper::showHeader();?>
    <div class="row wide-errors">
        <?=HtmlHelper::showMessage();?>
    </div>
    <!-- Кнопка для авторизации. -->
    <div class="row signin-menu">
        <?php
        if (User::isAuthorized() === false) {
            ?>
            <div class="col-2 signin_menu__button-container">
                <a id="signin-button" class="btn btn-secondary button_yellow" href="signin.php">Войти в систему</a>
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
        <div class="row justify-content-start main-content files-list">
            <div class="col-12">
                <h3 class="content-title">Файлы</h3>
            </div>
            <?php
            if (count($files) == 0) {
                ?>
                <div class="col-6">
                    <p class="files-list__no-files">Нет загруженных файлов приложения на Диске.</p>
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
                    <div class="col-3 files-list__file-column text-center">
                        <div class="files-list__file_item">
                            <a href="view.php?name=<?=$file['name']?>"><span class="files-list__item-link"></span></a>
                            <?php 
                            if ($file->has('preview')) { 
                                ?>
                                <img src="<?=$file['preview']?>" class="files-list__preview-image">
                                <?php 
                            } else {
                                ?>
                                <img src="<?=FileHelper::getFilePreview($file->mime_type)?>" class="files-list__preview-image">
                                <?php
                            }
                            ?>
                            <div class="files-list__file_name"><?=$file['name']?></div>
                            <div class="files-list__buttons-menu">
                                <form action="download.php" method="POST">
                                    <input type="hidden" name="download" value="<?=$file['name']?>">
                                    <input type="submit" class="btn btn-secondary button_yellow files-list__download-button" value="Скачать">
                                </form>
                                <form action="delete.php" method="POST">
                                    <input type="hidden" name="fileForDelete" value="<?=$file['name']?>">
                                    <input type="submit" class="btn btn-danger files-list__delete-button" value="Удалить">
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
                <?php
                $counter++;
            }
            if (count($files) > 0) {
                ?>
                <!-- Вывести кнопки постраничной навигации -->
                <div class="col-12 page-navigation">
                    <form method="GET">
                        <?php
                        if (isset($pageNavigator['current'])) {
                            ?>
                            <span>Страница:</span>
                            <?php
                        }
                        ?>
                        <?php
                        if (isset($pageNavigator['previous'])) {
                            ?>
                            <input type="submit" class="btn btn-secondary button_yellow page-navigation__button" name="current_page" value="<?=$pageNavigator['previous']?>">
                            <?php
                        }
                        if (isset($pageNavigator['current'])) {
                            ?>
                            <span class="page-navigation__button-current"><?=$pageNavigator['current']?></span>
                            <?php
                        }
                        if (isset($pageNavigator['next'])) {
                            ?>
                            <input type="submit" class="btn btn-secondary button_yellow page-navigation__button" name="current_page" value="<?=$pageNavigator['next']?>">
                            <?php
                        }
                        ?>
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
