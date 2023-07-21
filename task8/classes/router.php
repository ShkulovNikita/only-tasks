<?php

namespace AppClasses;

/**
 * Класс, отвечающий за перенаправления пользователя.
 */
class Router
{
    /**
     * Страницы, для доступа к которым не требуется авторизация.
     * @var array
     */
    private static $noLogin = ['index.php', 'signin.php', 'logout.php'];

    /**
     * Проверить, следует ли перенаправить пользователя.
     * @param string $page Страница, к которой пользователь пытается
     * получить доступ.
     */
    public static function routeUser($page)
    {
        /*
         * Если неавторизованный пользователь пытается получить доступ к
         * странице, требующей авторизации, то перенаправить его на
         * страницу авторизации. 
         */
        if (User::isAuthorized() === false && !in_array($page, self::$noLogin)) {
            Session::setValue('message', 'Выполните вход в систему.');
            self::routeToPage('signin.php');
        }
    }

    /**
     * Перенаправить пользователя на указанную страницу.
     * @param string $page Целевая страница.
     */
    public static function routeToPage($page)
    {
        header("Location: " . $page);
        die();
    }
}