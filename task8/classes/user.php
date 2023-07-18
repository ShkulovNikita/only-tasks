<?php

require_once 'session.php';

/**
 * User - класс, отвечаюший за действия, связанные с пользователем приложения.
 */
class User 
{ 
    /**
     * Сохранить полученный пользователем токен.
     * @param string $token Введенный пользователем токен.
     * @return bool|string true - авторизация произведена успешно, либо текст ошибки.
     */
    public static function authorizeUser($token)
    {
        $token = self::getTokenFromForm($token);

        if (!empty($token)) {
            Session::setValue('token', $token);
            Session::setValue('auth', true);

            return true;
        } else {
            return "Неверный токен";
        }
    }

    /**
     * Проверить, авторизован ли пользователь.
     * @return bool true - пользователь авторизован, false - не авторизован.
     */
    public static function isAuthorized()
    {
        if (Session::getValue('auth') === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Получить сохраненный токен текущего пользователя.
     * @return string|bool Токен пользователя либо false.
     */
    public static function getToken()
    {
        if (!empty(Session::getValue('token'))) {
            return Session::getValue('token');
        } else {
            return false;
        }
    }

    /**
     * Удалить введенный пользователем токен из приложения.
     */
    public static function logout()
    {
        Session::deleteValue('token');
        Session::setValue('auth', false);
    }

    /**
     * Получить токен из формы.
     * @param string $token Введенный пользователем токен.
     * @return string Введенный пользователем токен.
     */
    private static function getTokenFromForm($token) {
        $token = htmlspecialchars($token);
        return $token;
    }
}