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
     * 
     */
    private static function readTextFile($filename, &$error)
    {
        $filePath = "$_SERVER[DOCUMENT_ROOT]/temp/edit/" . $filename;
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
        } catch (Exception $ex) {
            $error = false;
            return 'Произошла ошибка при чтении файла.';
        }
    }

    
}
