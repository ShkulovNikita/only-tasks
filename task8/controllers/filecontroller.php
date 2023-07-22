<?php

namespace Controllers;

require "$_SERVER[DOCUMENT_ROOT]/vendor/autoload.php";

use AppClasses\{User, Drive, Router, Session, TextEditor};

/**
 * Класс, отвечающий за логику работы с данными файлов.
 */
class FileController
{
    /**
     * Получить список всех файлов в папке приложения на Диске.
     * @return array Список файлов.
     */
    public static function index()
    {
        $files = [];
        if (User::isAuthorized() === true) {
            $files = Drive::getFiles();
        }
        return $files;
    }

    /**
     * Просмотр информации о файле.
     * @return Arhitector\Yandex\Disk\Resource\Closed|string Файл как ресурс либо пустая строка.
     */
    public static function view()
    {
        /*
         * Проверить авторизацию пользователя. 
         */
        Router::routeUser('view.php');
        /*
         * Получение информации об указанном файле. 
         */
        if (isset($_GET['name'])) {
            return Drive::viewFile(htmlspecialchars($_GET['name']));
        } else {
            return Drive::viewFile(htmlspecialchars(''));
        }
    }

    /**
     * Редактирование файла.
     * @param array $incorrectProps Массив свойств, которые не удалось добавить.
     * @return Arhitector\Yandex\Disk\Resource\Closed|string Файл как ресурс либо пустая строка.
     */
    public static function edit(&$incorrectProps)
    {
        /*
         * Проверить авторизацию пользователя.
         */
        Router::routeUser('edit.php');
        /*
         * Получить информацию о файле.
         */
        $file = FileController::getFileInfo();
        /*
         * Сохранить изменения метаинформации. 
         */
        $incorrectProps = FileController::editProperties($file);
        /*
         * Сохранить изменения содержимого файла. 
         */
        FileController::editFileContent($file);

        return $file;
    }

    /**
     * Выполнить скачивания файла, указанного пользователем.
     */
    public static function download()
    {
        /*
         * Проверить авторизацию пользователя. 
         */
        Router::routeUser('download.php');
        /*
         * Если пользователь указал файл, то скачать его. 
         */
        if (isset($_POST['download'])) {
            Drive::downloadFile(htmlspecialchars($_POST['download']));
        }
    }

    /**
     * Выполнить удаление указанного файла.
     */
    public static function delete()
    {
        /**
         * Проверить, выполнен ли вход пользователя в систему.
         */
        Router::routeUser('delete.php');
        /*
         * Проверить, что пользователем был выбран файл для удаления. 
         */
        if (isset($_POST['fileForDelete'])) {
            Drive::deleteFile(htmlspecialchars($_POST['fileForDelete']));
        }
        /*
         * После удаления перенаправить пользователя на главную страницу.
         */
        Router::routeToPage('index.php');
    }

    /**
     * Загрузка файла на Яндекс.Диск.
     */
    public static function upload()
    {
        /*
         * Проверить авторизацию пользователя. 
         */
        Router::routeUser('upload.php');
        /*
         * Загрузить файл, указанный пользователем, на Я.Диск. 
         */
        $filename;
        if (
            $_FILES && $_FILES["filename"]["error"] == UPLOAD_ERR_OK 
            || isset($_POST['fileurl']) && !empty($_POST['fileurl'])
        ) {
            $filename = Drive::uploadFile();
        }
        /*
         * Проверить, что файл существует на Диске после загрузки. 
         */
        if (
            !empty($filename) 
            && Drive::getFile($filename)
            && Session::getValue('error') === ''
        ) {
            Session::setValue('message', '<a href="view.php?name=' . $filename . '">Ссылка на файл.</a>');
        }
    }

    /**
     * Получить содержимое указанного файла.
     * @return string Содержимое текстового файла либо текст ошибки.
     */
    public static function getFileContent()
    {
        /*
         * Проверить авторизацию пользователя. 
         */
        Router::routeUser('getfilecontent.php');
        /*
         * Получить текст файла. 
         */
        if (isset($_POST['filename'])) {
            $noErrors = true;
            $fileContent = TextEditor::getTextFileContent(htmlspecialchars($_POST['filename']), $noErrors);
            if ($noErrors === false) {
                return 'Ошибка: ' . $fileContent;
            } else {
                return $fileContent;
            }
        }
    }

    /**
     * Получить информацию о файле для редактирования.
     * @return Arhitector\Yandex\Disk\Resource\Closed|string Файл как ресурс либо пустая строка.
     */
    private static function getFileInfo()
    {
        /*
         * Проверить, был ли задан пользователем файл,
         * информацию о котором следует получить. 
         */
        if (isset($_GET['name'])) {
            return Drive::viewFile(htmlspecialchars($_GET['name']));
        } else {
            return Drive::viewFile(htmlspecialchars(''));
        }
    }

    /**
     * Редактирование метаинформации файла.
     * @param Arhitector\Yandex\Disk\Resource\Closed|string $file Файл как ресурс либо пустая строка.
     * @return array Массив свойств, которые не удалось добавить.
     */
    private static function editProperties($file)
    {
        /*
         * Выполняется, если была нажата кнопка редактирования метаинформации.
         */
        if (isset($_POST['edit']) && ($file != '')) {
            $incorrectProps = Drive::editProperties($file);
            return $incorrectProps;
        } else {
            return [];
        }
    }

    /**
     * Редактирование содержимого текстового файла.
     * @param Arhitector\Yandex\Disk\Resource\Closed|string $file Файл как ресурс либо пустая строка.
     */
    private static function editFileContent($file)
    {
        /*
         * Должна быть нажата кнопка редактирования и задан текст файла. 
         */
        if (
            isset($_POST['edit_content']) 
            && isset($_POST['edit_file_text']) 
            && ($file != '')
        ) {
            TextEditor::writeTextFileContent($file->name, $_POST['edit_file_text']);
        }
    }
}
