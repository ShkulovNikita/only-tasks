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
                    <title>$title</title>
                    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
                    <meta charset=\"utf-8\" />
                    <link rel=\"shortcut icon\" href=\"images/favicon.ico\" type=\"image/x-icon\">
                    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\" integrity=\"sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM\" crossorigin=\"anonymous\">
                    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js\" integrity=\"sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz\" crossorigin=\"anonymous\"></script>
                    <script src=\"https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js\"></script>
                    <link rel=\"stylesheet\" type=\"text/css\" href=\"styles/styles.css\"/>
                ";

        return $html;
    }

    /**
     * Сформировать HTML хэдера страницы.
     * @return string HTML-разметка хэдера.
     */
    public static function showHeader()
    {   
        $toolbar = '';
        if (User::isAuthorized() === true) {
            $toolbar .= "
                            <div class=\"row justify-content-between toolbar\">
                                <div class=\"col-2 toolbar__button-container\">
                                    <a class=\"btn btn-secondary button_yellow\" href=\"upload.php\">Загрузить файл</a>
                                </div>
                                <div class=\"col-2 toolbar__button-container\">
                                    <a class=\"btn btn-danger toolbar__error-button\" href=\"logout.php\">Выйти</a>
                                </div>
                            </div>
                        ";
        }
        $html = "
                    <div class=\"row\">
                        <div class=\"col-12\">
                            <a href=\"index.php\" class=\"header header__title\">
                                <div class=\"header__text\">
                                    <h1>Задание 8</h1>
                                </div>
                            </a>
                        </div>
                    </div>
                    $toolbar
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
                    <div class=\"col-12\">
                        <hr>
                    </div>
                ";
        return $html;
    }

    /**
     * Вывод сообщений и ошибок.
     * @return string HTML-разметка панели с сообщением.
     */
    public static function showMessage()
    {
        $errorMessage = Session::getValue('error');
        $resultHtml = self::getMessageHtml('error', $errorMessage);
        Session::deleteValue('error');

        $infoMessage = Session::getValue('message');
        $resultHtml .= self::getMessageHtml('info', $infoMessage);
        Session::deleteValue('message');
        
        return $resultHtml;
    }

    /**
     * Сформировать HTML-разметку для сообщения.
     * @param string $type Тип сообщения (error либо info).
     * @param string $text Текст сообщения.
     * @return string HTML для вывода сообщения.
     */
    private static function getMessageHtml($type, $text)
    {
        if (!empty($text)) {
            $html = '<div class="message message_' . $type . '">' . $text . '</div>' . "\n";
            return $html;
        } else {
            return '';
        }
    }
}