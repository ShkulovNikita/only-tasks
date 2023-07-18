<?php

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
     * Получить ссылку для отладочного токена данного приложения.
     * @return string Ссылка для получения отладочного токена.
     */
    public static function GetAppTokenLink()
    {
        return self::TOKEN_LINK . self::APPLICATION_ID;
    }
}
