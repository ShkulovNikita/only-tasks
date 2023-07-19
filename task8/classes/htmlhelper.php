<?php

namespace AppClasses;

require "$_SERVER[DOCUMENT_ROOT]/vendor/autoload.php";

/**
 * Класс, предназначенный для вывода некоторых HTML-частей страниц.
 */
class HtmlHelper
{
    /**
     * Сформировать HTML верхней части страницы.
     * @param string $title Заголовок вкладки браузера.
     * @return string HTML верхней части страницы.
     */
    public static function showProlog($title)
    {
        $html = "
                    <!DOCTYPE html>
                    <head>
                        <title>$title</title>
                        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
                        <meta charset=\"utf-8\" />
                        <link rel=\"shortcut icon\" href=\"images/favicon.ico\" type=\"image/x-icon\">
                        <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\" integrity=\"sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM\" crossorigin=\"anonymous\">
                        <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js\" integrity=\"sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz\" crossorigin=\"anonymous\"></script>
                        <script src=\"https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js\"></script>
                        <link rel=\"stylesheet\" type=\"text/css\" href=\"styles/styles.css\"/>
                    </head>
                ";

        return $html;
    }

    /**
     * Сформировать HTML хэдера страницы.
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