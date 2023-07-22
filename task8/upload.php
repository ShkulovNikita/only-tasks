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
    <div class="row">
        <div class="col-md-6">
            <?=HtmlHelper::showMessage();?>
        </div>
    </div>
    <button id="chooseFileButton" class="btn btn-primary" disabled="disabled">Выбрать файл</button>
    <button id="useUrlButton" class="btn btn-primary">По ссылке</button>
    <form method="POST" enctype="multipart/form-data">
        <label id="filenameLabel" for="filename">Выберите файл</label>
        <input id="fileUploadInput" type="file" name="filename" size="10">
        <label id="fileUrlLabel" for="fileurl">Введите URL</label>
        <input id="fileUrlInput" type="url" name="fileurl"><br />
        <input type="hidden" id="uploadType" name="type" value="file">
        <input type="submit" class="btn btn-primary" value="Отправить">
    </form>
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
