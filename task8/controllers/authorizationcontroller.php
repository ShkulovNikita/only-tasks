<?php

namespace Controllers;

require "$_SERVER[DOCUMENT_ROOT]/vendor/autoload.php";

use AppClasses\{User, Router, Session};

/**
 * Класс, отвечающий за логику авторизации пользователя.
 */
class AuthorizationController
{
    /**
     * Вход пользователя в систему.
     */
    public static function signin()
    {
        /*
         * Если в форму было введено значение токена, то сохранить его. 
         */
        if (isset($_POST['token']) && !empty($_POST['token'])) {
            $signinResult = User::authorizeUser($_POST['token']);
            if ($signinResult === true) {
                Session::setValue('message', 'Выполнен вход в систему.');
                Router::routeToPage('index.php');
            } else {
                Session::setValue('error', $signinResult);
            }
        }
    }

    /**
     * Выход пользователя из системы.
     */
    public static function logout()
    {
        User::logout();
        Session::setValue('message', 'Выполнен выход из системы.');
        Router::routeToPage('index.php');
    }
}
