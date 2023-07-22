<!DOCTYPE html>
<html>
<head>
<?php
require 'vendor/autoload.php';

use AppClasses\HtmlHelper;
use Controllers\FileController;

echo HtmlHelper::showProlog('Загрузка файла');
?>
</head>
<body>
<?php
FileController::upload();
?>
<div class="container-fluid">
    <?=HtmlHelper::showHeader()?>
    <div class="row wide-errors">
        <?=HtmlHelper::showMessage();?>
    </div>
    <div class="row main-content upload">
        <div class="col-12">
            <h3 class="text-center">Загрузка файла</h3>
        </div>
        <div class="col-12">
            <div class="upload__menu">
                <button id="chooseFileButton" class="btn btn-secondary button_yellow" disabled="disabled">Выбрать файл</button>
                <button id="useUrlButton" class="btn btn-secondary button_yellow">По ссылке</button>
            </div>
        </div>
        <div class="col-12">
            <div class="upload__form">
                <form method="POST" enctype="multipart/form-data">
                    <label id="filenameLabel" for="filename">Файл для загрузки</label>
                    <input id="fileUploadInput" type="file" name="filename" size="10" class="form-control">
                    <label id="fileUrlLabel" for="fileurl">Введите URL</label>
                    <input id="fileUrlInput" type="url" name="fileurl" class="form-control"><br />
                    <input type="hidden" id="uploadType" name="type" value="file">
                    <div class="text-center">
                        <input type="submit" class="btn btn-secondary button_yellow" value="Отправить">
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?=HtmlHelper::showFooter()?>
</div>
<script>
$(document).ready(function() {
    /**
     * При загрузке страницы по умолчанию показывается поле
     * для загрузки файла.
     */
    hideUrlInputs();
    showFileInputs();

    /**
     * При нажатии на кнопку "По ссылке" скрыть поле для загрузки файла
     * и отобразить поле для URL.
     */
    $('#chooseFileButton').button().click(function() {
        $('#useUrlButton').attr('disabled', false);
        $('#chooseFileButton').attr('disabled', true);
        $('#uploadType').val('file');
        hideUrlInputs();
        showFileInputs();
    });

    /**
     * При нажатии на кнопку "Выбрать файл" скрыть поле для URL 
     * и отобразить поле для загрузки файла.
     */
    $('#useUrlButton').button().click(function() {
        $('#chooseFileButton').attr('disabled', false);
        $('#useUrlButton').attr('disabled', true);
        $('#uploadType').val('url');
        hideFileInputs();
        showUrlInputs();
    });

    /**
     * Скрыть поле для загрузки файла.
     */
    function hideFileInputs() {
        $('#filenameLabel').hide();
        $('#fileUploadInput').hide();
    }

    /**
     * Скрыть поле ввода ссылки на файл.
     */
    function hideUrlInputs() {
        $('#fileUrlLabel').hide();
        $('#fileUrlInput').hide();
    }

    /**
     * Показать поле для загрузки файла.
     */
    function showFileInputs() {
        $('#filenameLabel').show();
        $('#fileUploadInput').show();
    }

    /**
     * Показать поле ввода ссылки на файл.
     */
    function showUrlInputs() {
        $('#fileUrlLabel').show();
        $('#fileUrlInput').show();
    }
});
</script>
</body>
</html>
