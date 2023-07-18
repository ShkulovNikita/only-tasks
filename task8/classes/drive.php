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
            /*
             * Получить объект для работы с диском.
             */
            $disk = new Arhitector\Yandex\Disk(User::getToken());
            /*
             * Получить указанную папку как ресурс.
             */
            $appFolderResource = $disk->getResource('app:/' . $subdir);
            /*
             * Коллекция файлов в папке. 
             */
            $files = $appFolderResource->items;

            return $files;
        } catch (Arhitector\Yandex\Client\Exception\UnauthorizedException $ex) {
            Session::setValue('error', $ex);
        } catch (Exception $ex) {
            Session::setValue('error', $ex);            
        }
    }
}