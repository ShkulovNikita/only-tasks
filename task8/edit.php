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
$file = FileController::edit();
?>
<div class="container-fluid">
    <?=HtmlHelper::showHeader();?>
    <div class="row">
        <div class="col-6">
            <?=HtmlHelper::showMessage('error');?>
            <?php
            if ($file != '') {
                ?>
                    <h3><?=$file['name']?></h3>
                    <button id="edit-properties" class="btn btn-primary">Редактировать метаинформацию</button>
                    <?php
                        /*
                         * Если файл текстовый, то его можно отредактировать.
                         */
                        if (str_contains($file->mime_type, 'text/plain')) {
                            ?>
                            <button id="edit-text-file" class="btn btn-primary">Редактировать файл</button>
                            <?php
                        }
                    ?>
                    <form method="POST" id="properties-form">
                        <?php
                        /*
                         * Получить как массив всю метаинформацию о файле.
                         */
                        $properties = $file->getProperties();
                        if ($properties) {
                            foreach ($properties as $propertyKey => $propertyValue) {
                                ?>
                                <label for="<?=$propertyKey?>"><?=$propertyKey?></label>
                                <input type="text" name="<?=$propertyKey?>" value="<?=$propertyValue?>">
                                <br>
                                <?php
                            }
                        }
                        ?>
                        <label for="newPropertyKey[0]">Ключ</label>
                        <input type="text" name="newPropertyKey[0]" value="">
                        <label for="newPropertyValue[0]">Значение</label>
                        <input type="text" name="newPropertyValue[0]" value="">
                        <br>
                        <label for="newPropertyKey[1]">Ключ</label>
                        <input type="text" name="newPropertyKey[1]" value="">
                        <label for="newPropertyValue[1]">Значение</label>
                        <input type="text" name="newPropertyValue[1]" value="">
                        <br>
                        <input type="submit" name="edit" class="btn btn-primary" value="Сохранить">
                    </form>
                <?php
            }
            ?>
        </div>
        <div class="col-6" id="editor-field">
            <?php
                if ($file != '') {
                    ?>
                    <form method="POST">
                        <input type="hidden" id="current-file-name" value="<?=$file->name?>">
                        <?php
                        if (str_contains($file->mime_type, 'text/plain')) {
                            ?>
                            <textarea class="form-control" name="edit_file_text" rows="5" id="editor-field__file-text"></textarea>
                            <?php
                        }
                        ?>
                        <input type="submit" id="edit-content-button" name="edit_content" class="btn btn-primary" value="Сохранить">
                    </form>
                    <?php
                }
            ?>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    /*
     * Скрыть поля для редактирования файла. 
     */
    hideEditorField();
    hideFormProperties();

    /**
     * Нажатие на кнопку "Редактирование метаинформации".
     */
    $('#edit-properties').button().click(function() {
        let propsForm = $('#properties-form');
        changeVisibility(propsForm);
    });

    /**
     * Нажатие на кнопку "Редактировать файл".
     */
    $('#edit-text-file').button().click(function() {
        let fileForm = $('#editor-field');
        /*
         * На время загрузки содержимого файла в textarea выводится
         * соответствующее сообщение. 
         */
        let editorField = $('#editor-field__file-text');
        editorField.prop('disabled', true);
        editorField.val('Загрузка...');
        /*
         * Заблокировать кнопку сохранения текста файла. 
         */
        let editorButton = $('#edit-content-button');
        editorButton.prop('disabled', true);
        /*
         * Отобразить поле ввода. 
         */
        changeVisibility(fileForm);
        /*
         * Получить содержимое файла. 
         */
        let fileContentResult = getFileContent(editorField, editorButton);
    });

    /**
     * Получить содержимое текущего файла и вывести в поле ввода.
     */
    function getFileContent(editorField, editorButton) {
        /*
         * Получить имя текущего файла. 
         */
        let filename = $('#current-file-name').val();
        /*
         * Выполнить ajax-запрос на получение содержимого файла. 
         */
        $.ajax({
            url: 'getfilecontent.php',
            type: 'POST',
            data: { "filename": filename },
            success: function(response) { 
                processReadingFileResult(editorField, editorButton, response);
            },
            error: function() {
                editorField.val('Произошла ошибка.');
            }
        });
    }

    /**
     * Отобразить для редактирования текст из файла в указанном поле.
     */
    function setFileText(editorField, editorButton, text) {
        editorField.val(text);
        editorField.prop('disabled', false);
        editorButton.prop('disabled', false);
    }

    /**
     * Вывести результат чтения текстового файла.
     */
    function processReadingFileResult(editorField, editorButton, response) {
        /*
         * Проверить, возникли ли ошибки чтения файла. 
         */
        const errorMessageLength = 8;
        /*
         * Если полученный текст короче, чем "Ошибка: ", 
         * то ошибок не было.
         */
        if (response.length < errorMessageLength) {
            setFileText(editorField, editorButton, response);
        } else {
            /*
             * Получить первые 8 символов. 
             */
            let possibleErrorMess = response.slice(0, errorMessageLength);
            /*
             * Если есть ошибка, то оставить поле заблокированным. 
             */
            if (possibleErrorMess == 'Ошибка: ') {
                editorField.val(response);
            } else {
                setFileText(editorField, editorButton, response);
            }
        }
    }

    /**
     * Изменить видимость формы, переданной в качестве параметра.
     */
    function changeVisibility(elem) {
        elem.toggle();
    }

    /**
     * Скрыть форму редактирования содержимого файла.
     */
    function hideEditorField() {
        $('#editor-field').hide();
    }

    /**
     * Скрыть форму редактирования метаинформации.
     */
    function hideFormProperties() {
        $('#properties-form').hide();
    }
});
</script>
</body>