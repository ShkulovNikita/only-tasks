<?php

namespace AppClasses;

require "$_SERVER[DOCUMENT_ROOT]/vendor/autoload.php";

if (isset($_POST['filename'])) {
    $noErrors = true;
    $fileContent = TextEditor::getTextFileContent(htmlspecialchars($_POST['filename']), $noErrors);
    if ($noErrors === false) {
        echo 'Ошибка: ' . $fileContent;
    } else {
        echo $fileContent;
    }
}
