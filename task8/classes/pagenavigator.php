<?php

namespace AppClasses;

/**
 * Класс для реализации постраничной навигации.
 */
class PageNavigator {
    /**
     * Количество файлов, получаемых при запросе для страницы.
     */
    private const PAGE_LIMIT = 12;

    /**
     * Получить смещение для запроса на получение файлов.
     * @return int Смещение.
     */
    public static function getOffset()
    {
        $currentPage = self::getCurrentPage();

        return ($currentPage - 1) * self::PAGE_LIMIT;
    }

    /**
     * Получить количество отображаемых файлов в списке.
     * @return int Количество файлов, отображемых в списке на одной странице.
     */
    public static function getPublicLimit()
    {
        return self::PAGE_LIMIT;
    }

    /**
     * Получить количество файлов, загружаемых для одной страницы.
     * @return int Количество файлов для одной страницы.
     */
    public static function getPageLimit()
    {
        return self::PAGE_LIMIT + 1;
    }

    /**
     * Получить массив, описывающий постраничку.
     * @param array $files Массив файлов, полученных для данной страницы.
     * @return array Массив для построения постранички.
     */
    public static function getPageNavigator($files)
    {
        $navigator = [];
        /*
         * Получить текущую страницу.
         */
        $currentPage = self::getCurrentPage();
        /*
         * Если страница не полностью заполнена, то эта страница - последняя.
         * Иначе добавить следующую страницу.
         */
        if (count($files) > self::PAGE_LIMIT) {
            $navigator['next'] = $currentPage + 1;
        }
        /*
         * Если текущая страница не первая, то добавить предыдущую страницу.
         */
        if ($currentPage > 1) {
            $navigator['previous'] = $currentPage - 1;
        }
        /**
         * Указать текущую страницу, если указаны предыдущая или следующая страница.
         */
        if (isset($navigator['previous']) || isset($navigator['next'])) {
            $navigator['current'] = $currentPage;
        }

        return $navigator;
    }

    /**
     * Получить номер текущей страницы.
     * @return int Номер текущей страницы.
     */
    private static function getCurrentPage()
    {
        $currentPage = 1;
        if (isset($_GET['current_page'])) {
            $currentPage = intval($_GET['current_page']);
        }
        /*
         * Валидация.
         */
        if ($currentPage < 1) {
            $currentPage = 1;
        }

        return $currentPage;
    }
}
