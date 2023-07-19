<?php

require_once 'user.php';
require_once 'session.php';
require_once "$_SERVER[DOCUMENT_ROOT]/vendor/autoload.php";

/**
 * Класс, реализующий работу с библиотекой для Яндекс.Диска.
 */
class Drive
{
    /**
     * Получить список файлов в папке приложения на Яндекс.Диске.
     * @param string $subdir Подпапка внутри папки приложения.
     * @return array Коллекция файлов на Диске.
     */
    public static function getFiles($subdir = '')
    {
        try {
            $appFolderResource = self::getResource($subdir);
            if ($appFolderResource !== false) {
                /*
                 * Коллекция файлов в папке. 
                 */
                $files = $appFolderResource->items;
                return $files;
            } else {
                return [];
            }
        } catch (Exception $ex) {
            Session::setValue('error', 'Ошибка: ' . $ex);          
            return [];  
        }
    }
    
    /**
     * Получить ресурс по указанному пути.
     * @param string $subResource Подпапка внутри папки приложения либо файл.
     * @return object|bool Ресурс на Яндекс.Диске либо false.
     */
    private static function getResource($subResource = '')
    {
        try {
            /*
             * Получить объект для работы с диском.
             */
            $disk = new Arhitector\Yandex\Disk(User::getToken());
            /*
             * Получить указанную папку как ресурс.
             */
            $appResource = $disk->getResource('app:/' . $subResource);
            return $appResource;
        } catch (Arhitector\Yandex\Client\Exception\UnauthorizedException $ex) {
            Session::setValue('error', 'Ошибка авторизации: ' . $ex);
            return false;
        } catch (Exception $ex) {
            Session::setValue('error', 'Ошибка: ' . $ex);          
            return false;  
        }
    }

    /**
     * Загрузка файла на Яндекс.Диск.
     * @param string $subdir Подпапка внутри папки приложения.
     */
    public static function uploadFile($subdir = '')
    {
        /*
         * Путь до файла.
         */
        $filePath;
        /*
         * Имя файла.
         */
        $fileName;
        /*
         * Определить, задан ли файл ссылкой или загружен пользователем с устройства. 
         */
        if ($_FILES && $_FILES["filename"]["error"] == UPLOAD_ERR_OK) {
            self::getFilePath($filePath, $fileName);
        } elseif (isset($_POST['fileurl']) && !empty($_POST['fileurl'])) {
            self::getFileUrl($filePath, $fileName);
        }
        /*
         * Загрузить файл на Диск. 
         */
        try {
            if (!empty($filePath)) {
                $resource = self::getResource($subdir . $fileName);
                $resource->upload($filePath, true, true);

                /**
                 * Удалить файл после загрузки.
                 */
                unlink($filePath);
            }
        } catch (Exception $ex) {
            Session::setValue('error', 'Ошибка: ' . $ex);
            return false;
        }

        return true;
    }

    /**
     * Получить URL файла, указанный пользователем.
     * @return string URL файла.
     */
    private static function getFileUrl(&$filePath, &$fileName)
    {
        $urlValue = htmlspecialchars($_POST['fileurl']);
        $filePath = $urlValue;
    }

    /**
     * Получить путь файла во временной папке на сервере.
     * @return string Путь до файла, загруженного на сервер в папку temp.
     */
    private static function getFilePath(&$filePath, &$fileName)
    {
        try {
            $fileName = $_FILES["filename"]["name"];
            $path = "temp/" . $fileName;
            move_uploaded_file($_FILES["filename"]["tmp_name"], $path);

            $filePath = "$_SERVER[DOCUMENT_ROOT]/" . $path;
        } catch (Exception $ex) {
            Session::setValue('error', 'Ошибка: ' . $ex);
            $filePath = '';      
        }
    }
}