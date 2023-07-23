<?php

namespace AppClasses;

/**
 * Класс с общими свойствами приложения.
 */
class Application
{
    /**
     * Идентификатор приложения в Яндексе.
     */
    private const APPLICATION_ID = 'e374ff39aff04b51a88fa1c34c174e49';
    /**
     * Ссылка для получения отладочного токена.
     */
    private const TOKEN_LINK = 'https://oauth.yandex.ru/authorize?response_type=token&client_id=';
    /**
     * Папка для временного хранения редактируемых файлов.
     */
    private const EDIT_PATH = "temp/edit/";
    /**
     * Папка для временного хранения скачиваемых файлов.
     */
    private const DOWNLOAD_PATH = "temp/download/";
    /**
     * Папка для временного хранения загружаемых на Диск файлов.
     */
    private const UPLOAD_PATH = "temp/upload/";

    /**
     * Получить ссылку для отладочного токена данного приложения.
     * @return string Ссылка для получения отладочного токена.
     */
    public static function getAppTokenLink()
    {
        return self::TOKEN_LINK . self::APPLICATION_ID;
    }

    /**
     * Получить путь для сохранения файлов для редактирования.
     * @return string Путь для временного хранения редактируемых файлов.
     */
    public static function getEditPath()
    {
        return "$_SERVER[DOCUMENT_ROOT]/" . self::EDIT_PATH;
    }

    /**
     * Получить путь для сохранения файлов для скачивания.
     * @return string Путь для временного хранения скачиваемых файлов.
     */
    public static function getDownloadPath()
    {
        return "$_SERVER[DOCUMENT_ROOT]/" . self::DOWNLOAD_PATH;
    }

    /**
     * Получить путь для сохранения файлов для загрузки на Диск.
     * @return string Путь для временного хранения загружаемых файлов.
     */
    public static function getUploadPath()
    {
        return "$_SERVER[DOCUMENT_ROOT]/" . self::UPLOAD_PATH;
    }

    /**
     * Получить лимит размера для загружаемых файлов.
     * @return int Лимит в байтах.
     */
    public static function getFileLimit()
    {
        return self::convertToBytes(ini_get('post_max_size'));
    }

    /**
     * Конвертировать размер в строчном формате в байты.
     * @param string $from Строка вида "10М", "8К" и т.д.
     * @return int Размер в байтах.
     */
    private static function convertToBytes(string $from): ?int 
    {
        $bytes = 0;
        $units = ['B', 'K', 'M', 'G'];
        /*
         * Найти букву, содержащуюся в размере. 
         */
        $multiplier = 1;
        $lastChar = substr($from, -1);
        foreach ($units as $key => $unit) {
            if ($lastChar == $unit) {
                return intval(substr($from, 0, -1)) * $multiplier;
            }
            $multiplier = $multiplier * 1024;
        }

        return $bytes;
    }
}
