<?php

require_once 'session.php';

/**
 * Класс, предназначенный для вывода некоторых HTML-частей страниц.
 */
class HtmlHelper
{
    /**
     * Сформировать HTML верхней части страницы.
     * @return string HTML-разметка хэдера.
     */
    public static function showHeader()
    {
        $html = "
                    <div class=\"col-md-12\">
                        <h1>Задание 8</h1>
                        <hr>
                    </div>
                ";

        return $html;
    }

    /**
     * Сформировать HTML нижней части страницы.
     * @return string HTML-разметка футера.
     */
    public static function showFooter()
    {
        $html = "
                    <div class=\"col-md-12\">
                        <hr>
                    </div>
                ";
        return $html;
    }

    /**
     * Сформировать HTML для вывода сообщений и ошибок.
     * @return string HTML-разметка панели с сообщением.
     */
    public static function showMessage()
    {
        $errorMessage = Session::getValue('error');
        if (!empty($errorMessage)) {
            $html = '';
            $html .= '<div class="card bg-danger text-white">' . "\n";
            $html .= '<div class="card-header">' . $errorMessage . '</div>' . "\n";
            $html .= '</div>' . "\n";
            Session::deleteValue('error');
            return $html;
        } else {
            return '';
        }
    }
}