<?php

namespace AppClasses;

require "$_SERVER[DOCUMENT_ROOT]/vendor/autoload.php";

/**
 * Класс для работы с текстовыми файлами.
 */
class TextEditor 
{
    /**
     * Получить содержимое указанного текстового файла.
     * @param string $filename Имя файла.
     * @param bool $error Флаг успешности чтения файла.
     * @return string Содержимое файла.
     */
    public static function getTextFileContent($filename, &$error)
    {
        /*
         * Загрузить файл на сервер. 
         */
        $downloadFileResult = Drive::downloadEditFile($filename);
        /*
         * Если удалось сохранить файл. 
         */
        if ($downloadFileResult) {
            /*
             * Прочитать файл.
             */
            $fileContent = self::readTextFile($filename, $error);
            return $fileContent;
        } else {
            $error = false;
            return 'Не удалось получить файл.';
        }
    }

    /**
     * Записать новый текст в файл.
     * @param string $filename Имя файла.
     * @param string $text Новый текст файла.
     */
    public static function writeTextFileContent($filename, $text)
    {
        /*
         * Проверить, существует ли файл. 
         */
        $filePath = Application::getEditPath();
        if (!file_exists($filePath . $filename)) {
            Session::setValue('error', 'Ошибка: файл не найден.');
            return;
        }
        /*
         * Открыть файл и записать в него текст. 
         */
        try {
            $file = fopen($filePath . $filename, 'w');
            fwrite($file, $text);
            fclose($file);
        } catch (\Exception $ex) {
            Session::setValue('error', 'Ошибка: ' . $ex);
            return;
        }
        /*
         * Загрузить обновленный файл на Яндекс.Диск. 
         */
        Drive::uploadFileToYandex($filePath . $filename, $filename, 'file');
    }

    /**
     * Прочитать загруженный на сервер текстовый файл.
     * @param string $filename Имя файла.
     * @param bool $error Флаг успешности чтения файла.
     * @return string Содержимое текстового файла.
     */
    private static function readTextFile($filename, &$error)
    {
        $filePath = Application::getEditPath() . $filename;
        try {
            if (file_exists($filePath)) {
                /*
                 * Прочитать файл полностью. 
                 */
                $str = htmlentities(file_get_contents($filePath));
                return $str;
            } else {
                $error = false;
                return 'Файл не найден на сервере.';
            }
        } catch (\Exception $ex) {
            $error = false;
            return 'Произошла ошибка при чтении файла.';
        }
    }
}
