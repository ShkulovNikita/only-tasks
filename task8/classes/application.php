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
}
