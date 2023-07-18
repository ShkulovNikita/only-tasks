<?php

/**
 * Класс с методами для работы с сессией.
 */
class Session
{
    /**
     * Сохранить значение в сессию под указанным ключом.
     * @param string $key Ключ для сохранения значения.
     * @param string $value Сохраняемое значение.
     */
    public static function setValue($key, $value)
    {
        self::startSession();
        $_SESSION[$key] = $value;
    }

    /**
     * Получить значение из сессии.
     * @param string $key Ключ для получения значения.
     * @return mixed Значение из сессии.
     */
    public static function getValue($key)
    {
        self::startSession();
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        } else {
            return '';
        }
    }

    /**
     * Удалить значение из сессии.
     * @param string $key Ключ значения в сессии.
     */
    public static function deleteValue($key)
    {
        self::startSession();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Начать сессию, если она ещё не была создана.
     */
    private static function startSession()
    {
        try {
            if (!isset($_SESSION)) {
                session_start();
            }
        } catch (Exception $ex) {
            echo "Ошибка сессии: " . $ex->getMessage();
        }
    }
}