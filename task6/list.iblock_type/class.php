<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) {
    die();
} 

use Bitrix\Main\Context,
    Bitrix\Main\Type\DateTime,
    Bitrix\Main\Loader,
    Bitrix\Iblock;

class CTraineeList extends CBitrixComponent
{
    /**
     * Фильтр.
     * @var array
     */
    public $arrFilter = [];
    /**
     * Параметры постраничной навигации.
     * @var array
     */
    public $arNavParams;
    public $arNavigation;
    /**
     * Массив с переменными для построения ссылок в постраничной навигации.
     * @var array
     */
    public $pagerParameters = [];
    /**
     * Право доступа пользователя к компоненту.
     * @var bool
     */
    public $bUSER_HAVE_ACCESS;
    /**
     * Массив результата работы компонента, передаваемый в шаблон.
     * @var array
     */
    public $arResult;
    /**
     * Принимает значение "type", если элементы инфоблоков вытаскиваются по типу,
     * либо "iblock", если по конкретному инфоблоку.
     * @var string
     */
    public $iblockSource;

    /**
     * Выполнить предварительные настройки перед началом работы с компонентом.
     * @param array $arParams Массив параметров, передаваемых при вызове компонента.
     */
    public function prepareComponent(&$arParams)
    {
        /*
         * Запрет сохранения в сессии номера последней страницы 
         * при постраничной навигации. 
         */
        CPageOption::SetOptionString('main', 'nav_page_in_session', 'N');
        /*
         * Установить значения параметров по умолчанию, если требуется. 
         */
        $this->setDefaultValues($arParams);
    }

    /**
     * Выполнить заполнение результирующего массива arResult.
     * @param array $arParams Массив параметров, передаваемых при вызове компонента.
     * @return bool false - произошла ошибка, требуется остановить обработку компонента.
     */
    public function makeArResult(&$arParams)
    {
        /*
         * Проверить наличие модуля инфоблоков. 
         */
        $checkIblockModuleResult = $this->checkIblockModule();
        if (!$checkIblockModuleResult) {
            return false;
        }
        /*
         * Если не задан тип инфоблока, то остановить выполнение компонента. 
         */
        $iblockTypeIsSet = $this->checkIfIblockTypeIsSet($arParams);
        if (!$iblockTypeIsSet) {
            return false;
        }
        /*
         * Если не задан ID инфоблока, то получить заданный тип инфоблока.
         */
        $getIblockResult;
        if ($arParams['IBLOCK_ID'] == '') {
            $getIblockResult = $this->getIblockType($arParams);
            $this->iblockSource = 'type';
        /*
         * Получить инфоблок, ID или код которого был передан в arParams.
         */
        } else {
            $getIblockResult = $this->getIblock($arParams);
            $this->iblockSource = 'iblock';
        }
        /*
         * Если не удалось получить инфоблок или тип, то остановить выполнение компонента.
         */
        if ($getIblockResult === false) {
            return false;
        }
        /*
         * Имеет ли пользователь право доступа для работы с компонентом. 
         */
        $this->arResult['USER_HAVE_ACCESS'] = $this->bUSER_HAVE_ACCESS;
        /*
         * Массив полей, которые необходимо получить при выборке элементов инфоблока. 
         */
        $arSelect = $this->setSelectFields($arParams);
        /*
         * $bGetProperty - флаг существования свойств. 
         */
        $bGetProperty = !empty($arParams['PROPERTY_CODE']);
        /*
         * Параметры фильтра для выборки элементов инфоблока.
         */
        $arFilter = $this->setFilter($arParams);
        if ($arFilter === false) {
            return false;
        }
        /*
         * Параметры сортировки. 
         */
        $arSort = $this->setOrdering($arParams);
        /*
         * Подготовить короткий список полей для пробного запроса. 
         */
        $shortSelect = $this->makeShortSelect($arSort);
        /*
         * Ссылка на страницу со списком элементов. 
         */
        $listPageUrl = '';
        /*
         * Список выводимых элементов инфоблоков. 
         */
        $this->arResult['ITEMS'] = array();
        /*
         * Идентификаторы элементов из $arResult['ITEMS']. 
         */
        $this->arResult['ELEMENTS'] = array();
        /*
         * Выполнить запрос на выборку элементов с коротким списком полей.
         */
        $rsElement = $this->getShortElements($arSort, $arFilter, $shortSelect);
        /*
         * Если в результате запроса были получены элементы инфоблока. 
         */
        if (!empty($this->arResult['ITEMS'])) {
            /*
             * $elementFilter содержит идентификаторы сайта и полученных элементов. 
             */
            $elementFilter = array(
                'IBLOCK_LID' => SITE_ID,
                'ID' => $this->arResult['ELEMENTS']
            );
            /*
             * Также фильтровать по типу или инфоблоку. 
             */
            if ($this->iblockSource == 'type') {
                $elementFilter['IBLOCK_TYPE'] = $this->arResult['ID'];
            } else {
                $elementFilter['IBLOCK_ID'] = $this->arResult['ID'];
            }
            /*
             * Получить элементы со всеми требуемыми полями. 
             */
            $iblockElements = $this->getIblockElements($arSelect, $arParams, $elementFilter);
            /*
             * Выполнить обработку полученных элементов. 
             */
            $this->processIblockElements($iblockElements, $arParams, $bGetProperty, $listPageUrl);
            unset($iblockElements);
            /*
             * Если есть массив свойств, то записать свойства в $arResult[ITEMS]. 
             */
            $this->setElementProperties($bGetProperty, $elementFilter);
        /*
         * Если ничего не найдено, то вывести соответствующее сообщение. 
         */
        } else {
            $this->abortResultCache();
            ShowError(GetMessage('T_IBLOCK_TYPE_LIST_IBLOCK_ELEMENTS_NA'));
            return false;
        }
        /*
         * Заполнить значения свойств. 
         */
        $this->fillElementProperties($bGetProperty, $arParams);
        /*
         * Рассортировать элементы инфоблоков по их инфоблокам в $arResult['ITEMS']. 
         */
        $this->sortIblockElements();
        /*
         * Обработка ссылок для постраничной навигации. 
         */
        $navComponentParameters = array();
        $this->processPageLinks($navComponentParameters, $listPageUrl, $arParams);
        /*
         * Получение HTML панели постраничной навигации. 
         */
        $this->setPagerPanel($navComponentParameters, $rsElement, $arParams);
        /*
         * Указать, какие ключи массива $arResult должны кешироваться. 
         */
        $this->setResultCachedKeys();
    }

    /**
     * Выполнить действия, требуемые при введенном пользователем идентификаторе
     * инфоблока или типа.
     * @param bool $isAuthorized Авторизован ли текущий пользователь.
     * @param array $arParams Параметры компонента.
     * @return array Массив $arResult['ELEMENTS'].
     */
    public function processChosenIblockID($isAuthorized, $arParams)
    {
        global $APPLICATION;

        $arTitleOptions = null;
        if ($isAuthorized) {
            if (
                $APPLICATION->GetShowIncludeAreas()
                || (is_object($GLOBALS['INTRANET_TOOLBAR']) && $arParams['INTRANET_TOOLBAR'] !== 'N')
                || $arParams['SET_TITLE']
            ) {
                if (Loader::includeModule('iblock')) {
                    /*
                     * Получить набор кнопок для управления выбранным инфоблоком. 
                     */
                    $arButtons = $this->getIblockButtons($arParams);
                    /*
                     * Отобразить кнопки для включаемых областей.
                     */
                    if ($APPLICATION->GetShowIncludeAreas()) {
                        $this->addIncludeAreaIcons(CIBlock::GetComponentMenu($APPLICATION->GetPublicShowMode(), $arButtons));
                    }
                    /*
                     * Задание параметров тулбара, если требуется. 
                     */
                    $this->setToolbar($arButtons, $arParams);
                    /*
                     * Задание параметров заголовка страницы, если требуется. 
                     */
                    $arTitleOptions = $this->setPageTitleParams($arButtons, $arParams);
                }
            }
        }

        /*
         * Возвращение шаблону кешированных данных. 
         */
        $this->setTemplateCachedData($this->arResult['NAV_CACHED_DATA']);

        $ipropertyExists = (!empty($this->arResult['IPROPERTY_VALUES']) && is_array($this->arResult['IPROPERTY_VALUES']));
        $iproperty = ($ipropertyExists ? $this->arResult['IPROPERTY_VALUES'] : array());
        /*
         * Задание заголовка страницы компонентом. 
         */
        $this->setPageTitle($arParams, $ipropertyExists, $iproperty, $arTitleOptions);
        /*
         * Задание CEO-параметров страницы. 
         */
        if ($ipropertyExists) {
            $this->setIproperties($iproperty, $arParams, $arTitleOptions);
        }
        /*
         * Добавить имя инфоблока в цепочку навигации, если требуется.
         */
        $this->addIblockToNavChain($arParams);
        /*
         * Добавление имен разделов в цепочку навигации. 
         */
        $this->addSectionsToNavChain($arParams);
        
        if ($arParams['SET_LAST_MODIFIED'] && $this->arResult['ITEMS_TIMESTAMP_X']) {
            Context::getCurrent()->getResponse()->setLastModified($this->arResult['ITEMS_TIMESTAMP_X']);
        }

        unset($iproperty);
        unset($ipropertyExists);

        return $this->arResult['ELEMENTS'];
    }

    /**
     * Установка значений по умолчанию для незаполненных параметров компонента.
     * @param array $arParams Массив параметров компонента.
     */
    private function setDefaultValues(&$arParams)
    {
        if (!isset($arParams['CACHE_TIME'])) {
            $arParams['CACHE_TIME'] = 36000000;
        } else {
            $this->setDefaultInt($arParams['CACHE_TIME'], 36000000, 'CACHE_TIME');
        }

        $this->setDefaultSort1($arParams);
        $this->setDefaultSort2($arParams);
        $this->setDefaultString($arParams['IBLOCK_TYPE'], 'news', 50, 'IBLOCK_TYPE');
        $this->setDefaultInt($arParams['IBLOCK_ID'], '', 'IBLOCK_ID');
        $this->setDefaultInt($arParams['PARENT_SECTION'], 0, 'PARENT_SECTION');
        $this->setDefaultString($arParams['PARENT_SECTION_CODE'], '', 100, 'PARENT_SECTION_CODE');

        $arParams['INCLUDE_SUBSECTIONS'] = ($arParams['INCLUDE_SUBSECTIONS'] ?? '') !== 'N';
        $arParams['SET_LAST_MODIFIED'] = ($arParams['SET_LAST_MODIFIED'] ?? '') === 'Y';
        $arParams['SET_TITLE'] = ($arParams['SET_TITLE'] ?? '') !== 'N';
        $arParams['SET_BROWSER_TITLE'] = ($arParams['SET_BROWSER_TITLE'] ?? '') === 'N' ? 'N' : 'Y';
        $arParams['SET_META_KEYWORDS'] = ($arParams['SET_META_KEYWORDS'] ?? '') === 'N' ? 'N' : 'Y';
        $arParams['SET_META_DESCRIPTION'] = ($arParams['SET_META_DESCRIPTION'] ?? '') === 'N' ? 'N' : 'Y';
        $arParams['ADD_SECTIONS_CHAIN'] = ($arParams['ADD_SECTIONS_CHAIN'] ?? '') !== 'N';
        $arParams['INCLUDE_IBLOCK_INTO_CHAIN'] = ($arParams['INCLUDE_IBLOCK_INTO_CHAIN'] ?? '') !== 'N';
        $arParams['STRICT_SECTION_CHECK'] = ($arParams['STRICT_SECTION_CHECK'] ?? '') === 'Y';
        $arParams['ACTIVE_DATE_FORMAT'] = trim($arParams['ACTIVE_DATE_FORMAT'] ?? '');
        if (empty($arParams['ACTIVE_DATE_FORMAT'])) {
            $arParams['ACTIVE_DATE_FORMAT'] = $DB->DateFormatToPHP(\CSite::GetDateFormat('SHORT'));
        }

        $this->setDefaultInt($arParams['PREVIEW_TRUNCATE_LEN'], 0, 'PREVIEW_TRUNCATE_LEN');

        $arParams['HIDE_LINK_WHEN_NO_DETAIL'] = ($arParams['HIDE_LINK_WHEN_NO_DETAIL'] ?? '') === 'Y';
        $arParams['CHECK_DATES'] = ($arParams['CHECK_DATES'] ?? '') !== 'N';
        $arParams['DISPLAY_TOP_PAGER'] = ($arParams['DISPLAY_TOP_PAGER'] ?? '') === 'Y';
        $arParams['DISPLAY_BOTTOM_PAGER'] = ($arParams['DISPLAY_BOTTOM_PAGER'] ?? '') !== 'N';
        $arParams['PAGER_SHOW_ALWAYS'] = ($arParams['PAGER_SHOW_ALWAYS'] ?? '') === 'Y';
        $arParams['PAGER_DESC_NUMBERING'] = ($arParams['PAGER_DESC_NUMBERING'] ?? '') === 'Y';
        $arParams['PAGER_SHOW_ALL'] = ($arParams['PAGER_SHOW_ALL'] ?? '') === 'Y';
        $arParams['PAGER_BASE_LINK_ENABLE'] ??= 'N';
        $this->setDefaultString($arParams['PAGER_TITLE'], '', 255, 'PAGER_TITLE');
        $this->setDefaultString($arParams['PAGER_TEMPLATE'], '', 255, 'PAGER_TEMPLATE');
        $this->setDefaultInt($arParams['PAGER_DESC_NUMBERING_CACHE_TIME'], 0, 'PAGER_DESC_NUMBERING_CACHE_TIME');
        $this->setDefaultString($arParams['PAGER_BASE_LINK'], '', 255, 'PAGER_BASE_LINK');
        $this->setDefaultString($arParams['INTRANET_TOOLBAR'], '', 255, 'INTRANET_TOOLBAR');

        $arParams['CHECK_PERMISSIONS'] = ($arParams['CHECK_PERMISSIONS'] ?? '') !== 'N';
        
        $this->setDefaultString($arParams['MESSAGE_404'], '', 255, 'MESSAGE_404');
        $this->setDefaultString($arParams['FILE_404'], '', 255, 'FILE_404');
        $arParams['SET_STATUS_404'] ??= 'N';
        $arParams['SHOW_404'] ??= 'N';

        $this->setDefaultFieldCode($arParams);
        $this->setDefaultPropertyCode($arParams);
        
        $this->setDefaultString($arParams['DETAIL_URL'], '', 255, 'DETAIL_URL');
        $this->setDefaultString($arParams['SECTION_URL'], '', 255, 'SECTION_URL');
        $this->setDefaultString($arParams['IBLOCK_URL'], '', 255, 'IBLOCK_URL');

        $this->setDefaultInt($arParams['NEWS_COUNT'], 0, 'NEWS_COUNT');
        if ($arParams['NEWS_COUNT'] <= 0) {
            $arParams['NEWS_COUNT'] = 20;
        }

        $this->setDefaultFilter($arParams);
        $this->setDefaultNavigationParams($arParams);
        $this->setDefaultUserPermission($arParams);

        $arParams['CACHE_FILTER'] = ($arParams['CACHE_FILTER'] ?? '') === 'Y';
        if (!$arParams['CACHE_FILTER'] && !empty($this->arrFilter)) {
            $arParams['CACHE_TIME'] = 0;
        }

        $this->setDefaultString($arParams['CACHE_GROUPS'], '', 1, 'CACHE_GROUPS');
    }

    /**
     * Выполнить валидацию и установить значение по умолчанию (при необходимости)
     * для параметра строкового типа.
     * @param string $param Значение параметра.
     * @param int $charLimit Максимальная длина строки.
     * @param string $name Имя для поиска сообщения об ошибке для данного параметра.
     */
    private function setDefaultString(&$param, $defaultValue, $charLimit, $name)
    {
        /*
         * Удалить пробелы в начале и конце параметра. 
         */
        $param = trim($param ?? $defaultValue);
        /*
         * Проверить соответствие по длине. 
         */
        if (mb_strlen($param) > $charLimit) {
            ShowError(GetMessage('T_IBLOCK_TYPE_LIST_' . $name . '_INCORRECT_LENGTH'));
            $param = $defaultValue;
        }
    }

    /**
     * Валидация и установка значения по умолчанию для параметра числового типа.
     * @param int $param Значение параметра.
     * @param int $defaultValue Значение по умолчанию.
     * @param string $name Имя для поиска сообщения об ошибке для данного параметра.
     */
    private function setDefaultInt(&$param, $defaultValue, $name)
    {
        $param = trim($param ?? '');
        if ($param == '') {
            $param = $defaultValue;
        } else {
            $param = filter_var($param, FILTER_VALIDATE_INT);
            if ($param === false) {
                ShowError(GetMessage('T_IBLOCK_TYPE_LIST_' . $name . '_IS_NOT_INT'));
                $param = $defaultValue;
            }
        }
    }

    /**
     * Установка первой сортировки по умолчанию.
     * @param array $arParams Параметры компонента.
     */
    private function setDefaultSort1(&$arParams)
    {
        $arParams['SORT_BY1'] = trim($arParams['SORT_BY1'] ?? '');
        if (empty($arParams['SORT_BY1'])) {
            $arParams['SORT_BY1'] = 'ACTIVE_FROM';
        }
        if (
            !isset($arParams['SORT_ORDER1'])
            || !preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams['SORT_ORDER1'])
        ) {
            $arParams['SORT_ORDER1'] = 'DESC';
        }
    }

    /**
     * Установка второй сортировки по умолчанию.
     * @param array $arParams Параметры компонента.
     */
    private function setDefaultSort2(&$arParams)
    {
        $arParams['SORT_BY2'] = trim($arParams['SORT_BY2'] ?? '');
        if (empty($arParams['SORT_BY2'])) {
            if (mb_strtoupper($arParams['SORT_BY1']) === 'SORT') {
                $arParams['SORT_BY2'] = 'ID';
                $arParams['SORT_ORDER2'] = 'DESC';
            } else {
                $arParams['SORT_BY2'] = 'SORT';
            }
        }
        if (
            !isset($arParams['SORT_ORDER2'])
            || !preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams['SORT_ORDER2'])
        ) {
            $arParams['SORT_ORDER2'] = 'ASC';
        }
    }

    /**
     * Проверка массива полей на пустоту, удаление пустых значений.
     * @param array $arParams Параметры компонента. 
     */
    private function setDefaultFieldCode(&$arParams)
    {
        if (empty($arParams['FIELD_CODE']) || !is_array($arParams['FIELD_CODE'])) {
            $arParams['FIELD_CODE'] = [];
        }
        foreach ($arParams['FIELD_CODE'] as $key => $val) {
            if (!$val) {
                unset($arParams['FIELD_CODE'][$key]);
            }
        }
    }

    /**
     * Проверка на пустоту массива свойств и удаление пустых значений.
     * @param array $arParams Параметры компонента.
     */
    private function setDefaultPropertyCode(&$arParams)
    {
        if (empty($arParams['PROPERTY_CODE']) || !is_array($arParams['PROPERTY_CODE'])) {
            $arParams['PROPERTY_CODE'] = array();
        }
        foreach ($arParams['PROPERTY_CODE'] as $key => $val) {
            if ($val === "") {
                unset($arParams['PROPERTY_CODE'][$key]);
            }
        }
    }

    /**
     * Предварительная подготовка фильтра.
     * @param array $arParams Параметры компонента.
     */
    private function setDefaultFilter(&$arParams)
    {
        if (!empty($arParams['FILTER_NAME']) && preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams['FILTER_NAME'])) {
            $this->arrFilter = $GLOBALS[$arParams['FILTER_NAME']] ?? [];
            if (!is_array($this->arrFilter)) {
                $this->arrFilter = [];
            }
        }
    }

    /**
     * Установка параметров постраничной навигации по умолчанию.
     * @param array $arParams Параметры компонента.
     */
    private function setDefaultNavigationParams(&$arParams)
    {
        if ($arParams['DISPLAY_TOP_PAGER'] || $arParams['DISPLAY_BOTTOM_PAGER']) {
            $this->arNavParams = array(
                'nPageSize' => $arParams['NEWS_COUNT'],
                'bDescPageNumbering' => $arParams['PAGER_DESC_NUMBERING'],
                'bShowAll' => $arParams['PAGER_SHOW_ALL'],
            );
            $this->arNavigation = CDBResult::GetNavParams($this->arNavParams);
            if ((int)$this->arNavigation['PAGEN'] === 0 && $arParams['PAGER_DESC_NUMBERING_CACHE_TIME'] > 0) {
                $arParams['CACHE_TIME'] = $arParams['PAGER_DESC_NUMBERING_CACHE_TIME'];
            }
        } else {
            $this->arNavParams = array(
                'nTopCount' => $arParams['NEWS_COUNT'],
                'bDescPageNumbering' => $arParams['PAGER_DESC_NUMBERING'],
            );
            $this->arNavigation = false;
        }
        if (!empty($arParams['PAGER_PARAMS_NAME']) && preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams['PAGER_PARAMS_NAME'])) {
            $this->pagerParameters = $GLOBALS[$arParams['PAGER_PARAMS_NAME']] ?? [];
            if (!is_array($this->pagerParameters)) {
                $this->pagerParameters = array();
            }
        }
    }

    /**
     * Определить право доступа пользователя к компоненту.
     * @param array $arParams Параметры компонента.
     */
    private function setDefaultUserPermission(&$arParams)
    {
        $arParams['USE_PERMISSIONS'] = ($arParams['USE_PERMISSIONS'] ?? '') === "Y";
        if (!is_array($arParams['GROUP_PERMISSIONS'] ?? null)) {
            $adminGroupCode = 1;
            $arParams['GROUP_PERMISSIONS'] = [$adminGroupCode];
        }

        $this->bUSER_HAVE_ACCESS = !$arParams['USE_PERMISSIONS'];
        if ($arParams['USE_PERMISSIONS'] && isset($GLOBALS['USER']) && is_object($GLOBALS['USER'])) {
            $arUserGroupArray = $USER->GetUserGroupArray();
            foreach ($arParams['GROUP_PERMISSIONS'] as $PERM) {
                if (in_array($PERM, $arUserGroupArray)) {
                    $this->bUSER_HAVE_ACCESS = true;
                    break;
                }
            }
        }
    }

    /**
     * Проверить наличие установленного модуля инфоблоков и прервать выполнение,
     * если его нет.
     * @return bool true|false true - модуль доступен, false - модуль недоступен.
     */
    private function checkIblockModule()
    {
        if (!Loader::includeModule('iblock')) {
            $this->abortResultCache();
            ShowError(GetMessage('IBLOCK_TYPE_IBLOCK_MODULE_NOT_INSTALLED'));
            return false;
        }
        return true;
    }

    /**
     * Проверить, задан ли какой-либо тип инфоблока.
     * @param array $arParams Параметры компонента.
     * @return bool true, если задан; false, если не задан.
     */
    private function checkIfIblockTypeIsSet($arParams)
    {
        if (!isset($arParams['IBLOCK_TYPE'])) {
            $this->abortResultCache();
            ShowError(GetMessage('T_IBLOCK_TYPE_LIST_TYPE_NA'));
            return false;
        }
        return true;
    }

    /**
     * Получить инфоблок, ID/код которого передан в качестве параметра.
     * @param array $arParams Параметры компонента.
     * @return bool true - был успешно получен инфоблок, false - ошибка.
     */
    private function getIblock($arParams)
    {
        /*
         * Если код инфоблока задан числом, то получить инфоблок по идентификатору,
         * иначе получить инфоблок по символьному коду. 
         */
        if (is_numeric($arParams['IBLOCK_ID'])) {
            $rsIBlock = CIBlock::GetList(array(), array(
                'ACTIVE' => 'Y',
                'ID' => $arParams['IBLOCK_ID'],
            ));
        } else {
            $rsIBlock = CIBlock::GetList(array(), array(
                'ACTIVE' => 'Y',
                'CODE' => $arParams['IBLOCK_ID'],
                'SITE_ID' => SITE_ID,
            ));
        }
        /*
         * Попробовать передать в $arResult найденный инфоблок. 
         */
        $this->arResult = $rsIBlock->GetNext();
        /*
         * Если инфоблок не был найден, то вывести соответствующую ошибку.
         */
        if (!$this->arResult) {
            $this->abortResultCache();
            Iblock\Component\Tools::process404(
                trim($arParams['MESSAGE_404']) ?: GetMessage('T_IBLOCK_TYPE_LIST_NA')
                ,true
                ,$arParams['SET_STATUS_404'] === "Y"
                ,$arParams['SHOW_404'] === "Y"
                ,$arParams['FILE_404']
            );
            return false;
        }

        return true;
    }

    /**
     * Получить тип инфоблока.
     * @param array $arParams Параметры компонента.
     * @return bool true - тип был успешно получен, false - ошибка.
     */
    private function getIblockType($arParams)
    {
        $rsType = CIBlockType::GetList(
            [],
            ['=ID' => $arParams['IBLOCK_TYPE']]
        );
        $this->arResult = $rsType->GetNext();

        if (!$this->arResult) {
            $this->abortResultCache();
            Iblock\Component\Tools::process404(
                trim($arParams['MESSAGE_404']) ?: GetMessage('T_IBLOCK_TYPE_LIST_TYPE_NA')
                ,true
                ,$arParams['SET_STATUS_404'] === 'Y'
                ,$arParams['SHOW_404'] === 'Y'
                ,$arParams['FILE_404']
            );
            return false;
        }

        return true;
    }

    /**
     * Установить массив полей, которые нужно получить при выборке
     * элементов инфоблока.
     * @param array $arParams Параметры компонента.
     * @return array Массив полей.
     */
    private function setSelectFields($arParams)
    {
        return array_merge($arParams['FIELD_CODE'], array(
            'ID',
            'IBLOCK_ID',
            'IBLOCK_SECTION_ID',
            'NAME',
            'ACTIVE_FROM',
            'TIMESTAMP_X',
            'DETAIL_PAGE_URL',
            'LIST_PAGE_URL',
            'DETAIL_TEXT',
            'DETAIL_TEXT_TYPE',
            'PREVIEW_TEXT',
            'PREVIEW_TEXT_TYPE',
            'PREVIEW_PICTURE',
        ));
    }

    /**
     * Заполнить фильтр для получения элементов инфоблока.
     * @param array $arParams Параметры компонента.
     * @return array|bool Массив с параметрами фильтра либо false, если произошла ошибка. 
     */
    private function setFilter(&$arParams)
    {
        /*
         * Включить в фильтр ID сайта, активность и права доступа. 
         */
        $arFilter = array(
            'IBLOCK_LID' => SITE_ID,
            'ACTIVE' => 'Y',
            'CHECK_PERMISSIONS' => $arParams['CHECK_PERMISSIONS'] ? 'Y' : 'N',
        );
        /*
         * Фильтрация по типу или инфоблоку. 
         */
        if ($this->iblockSource == 'type') {
            $arFilter['IBLOCK_TYPE'] = $this->arResult['ID'];
        } else {
            $arFilter['IBLOCK_ID'] = $this->arResult['ID'];
        }
        /*
         * Если выбрано "Показывать только активные на данный момент элементы",
         * то дополнить фильтр. 
         */
        if ($arParams['CHECK_DATES']) {
            $arFilter['ACTIVE_DATE'] = 'Y';
        }
        /*
         * Добавить в фильтр параметры раздела, если требуется. 
         */
        $checkParentSectionResult = $this->checkParentSection($arParams, $arFilter);
        if ($checkParentSectionResult === false) {
            return false;
        }

        return $arFilter;
    }

    /**
     * Получить параметры раздела (если требуется) и внести их в фильтр и $arResult.
     * @param array $arParams Параметры компонента.
     * @param array $arFilter Фильтр для получения элементов инфоблока.
     * @return bool true, если не возникло ошибок, иначе false.
     */
    private function checkParentSection(&$arParams, &$arFilter)
    {
        /*
         * Получить ID раздела по его идентификатору, коду и идентификатору инфоблока. 
         */
        $PARENT_SECTION = $this->getParentSectionID(
            $arParams['PARENT_SECTION'], 
            $arParams['PARENT_SECTION_CODE'], 
            $this->arResult['ID']
        );

        /*
         * Проверить при строгой проверке раздела, что ID родительского раздела был успешно получен. 
         */
        $getParentSectionResult = $this->checkStrictParentSection(
            $arParams,
            $PARENT_SECTION
        );
        if ($getParentSectionResult === false) {
            return false;
        }
        /*
         * Проверить, что указанный раздел принадлежит заданному инфоблоку/типу.
         */
        if ($PARENT_SECTION > 0) {
            $checkBelongingResult = $this->checkIfSectionBelongsToIblock($PARENT_SECTION);
            if ($checkBelongingResult === false) {
                if ($this->iblockSource == 'type') {
                    ShowError(GetMessage('T_IBLOCK_TYPE_LIST_SECTION_DOESNT_BELONG_TO_TYPE'));
                } else {
                    ShowError(GetMessage('T_IBLOCK_TYPE_LIST_SECTION_DOESNT_BELONG_TO_IBLOCK'));
                }
                return false;
            }
        }

        $arParams['PARENT_SECTION'] = $PARENT_SECTION;
        /*
         * Добавить параметры раздела в фильтр. 
         */
        $this->addSectionToFilter($arParams, $arFilter);

        return true;
    }

    /**
     * Получить идентификатор родительского раздела.
     * @param int $parentSectionID ID раздела.
     * @param string $parentSectionCode Символьный код раздела.
     * @param int $iblockID ID инфоблока.
     * @return int ID раздела.
     */
    private function getParentSectionID($parentSectionID, $parentSectionCode, $iblockID)
    {
        $arFilter = [];
        if ($this->iblockSource == 'iblock') {
            $arFilter = ['IBLOCK_ID' => $iblockID];
        } else {
            $arFilter = ['IBLOCK_TYPE' => $iblockID];
        }
        
        return CIBlockFindTools::GetSectionID(
            $parentSectionID,
            $parentSectionCode,
            $arFilter
        );
    }

    /**
     * Если задана строгая проверка раздела, и задан ID раздела или его код,
     * то проверить, был ли получен ID родительского раздела (если нет - ошибка).
     * @param array $arParams Параметры компонента.
     * @param int $parentSection Полученный ранее ID раздела.
     * @return bool true - если ID успешно получен, иначе false.
     */
    private function checkStrictParentSection(&$arParams, $parentSection)
    {
        if (
            $arParams['STRICT_SECTION_CHECK']
            && (
                $arParams['PARENT_SECTION'] > 0
                || $arParams['PARENT_SECTION_CODE'] <> ''
            )
        ) {
            if ($parentSection <= 0) {
                $this->abortResultCache();
                Iblock\Component\Tools::process404(
                    trim($arParams['MESSAGE_404']) ?: GetMessage('T_IBLOCK_TYPE_LIST_NA'),
                    true,
                    $arParams['SET_STATUS_404'] === 'Y',
                    $arParams['SHOW_404'] === 'Y',
                    $arParams['FILE_404']
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Проверить, что указанный раздел принадлежит указанному типу/инфоблоку.
     * @param int $sectionID Идентификатор раздела.
     * @return bool true - принадлежит, false - не принадлежит.
     */
    private function checkIfSectionBelongsToIblock($sectionID) {
        $arSectFilter = [];
        /*
         * Задание фильтрации в зависимости от указанного типа или инфоблока. 
         */
        if ($this->iblockSource == 'type') {
            $arSectFilter = [
                'IBLOCK_TYPE' => $this->arResult['ID'],
                'ACTIVE' => 'Y'
            ];
        } else {
            $arSectFilter = [
                'IBLOCK_ID' => $this->arResult['ID'],
                'ACTIVE' => 'Y'
            ];
        }
        /*
         * Получить список идентификаторов разделов данного инфоблока/типа. 
         */
        $rsSections = CIBlockSection::GetList(
            [],
            $arSectFilter,
            false,
            ['ID']
        );
        $arSectionsIds = [];
        while ($arSection = $rsSections->GetNext()) {
            $arSectionsIds[] = $arSection['ID'];
        }

        return in_array($sectionID, $arSectionsIds);
    }

    /**
     * При заданном разделе внесение соответствующих параметров в фильтр.
     * @param array $arParams Параметры компонента.
     * @param array $arFilter Фильтр для получения элементов инфоблока.
     */
    private function addSectionToFilter(&$arParams, &$arFilter)
    {
        if ($arParams['PARENT_SECTION'] > 0) {
            /*
            * Сохранить раздел в фильтре. 
            */
            $arFilter['SECTION_ID'] = $arParams['PARENT_SECTION'];
            /*
            * Указать в фильтре, показывать ли элементы подразделов указанного раздела. 
            */
            if ($arParams['INCLUDE_SUBSECTIONS']) {
                $arFilter['INCLUDE_SUBSECTIONS'] = 'Y';
            }
            $this->addSectionPath($arParams);

            $ipropValues = new Iblock\InheritedProperty\SectionValues($this->arResult['ID'], $arParams['PARENT_SECTION']);
            $this->arResult['IPROPERTY_VALUES'] = $ipropValues->getValues();
        } else {
            $this->arResult['SECTION'] = false;
        }
    }

    /**
     * Добавить значения для arResult[SECTION][PATH] из полученного раздела.
     * @param array $arParams Параметры компонента.
     */
    private function addSectionPath($arParams)
    {
        $this->arResult['SECTION'] = array('PATH' => array());
        $rsPath = CIBlockSection::GetNavChain(
            $this->arResult['ID'],
            $arParams['PARENT_SECTION'],
            [
                'ID',
                'IBLOCK_ID',
                'NAME',
                'SECTION_PAGE_URL',
            ]
        );
        /*
         * Применение шаблона полученного пути. 
         */
        $rsPath->SetUrlTemplates("", $arParams['SECTION_URL'], $arParams['IBLOCK_URL']);
        /*
         * Запись пути в $arResult. 
         */
        while ($arPath = $rsPath->GetNext()) {
            $ipropValues = new Iblock\InheritedProperty\SectionValues($arParams['IBLOCK_ID'], $arPath['ID']);
            $arPath['IPROPERTY_VALUES'] = $ipropValues->getValues();
            $this->arResult['SECTION']['PATH'][] = $arPath;
        }
        unset($arPath, $rsPath);
    }

    /**
     * Задать сортировку.
     * @param array $arParams Параметры компонента.
     * @return array Массив с параметрами сортировки (поля и направление).
     */
    private function setOrdering($arParams)
    {
        $arSort = array(
            $arParams['SORT_BY1'] => $arParams['SORT_ORDER1'],
            $arParams['SORT_BY2'] => $arParams['SORT_ORDER2'],
        );
        if (!array_key_exists('ID', $arSort)) {
            $arSort['ID'] = 'DESC';
        }

        return $arSort;
    }

    /**
     * Подготовить массив с минимальным числом полей для попытки получения элементов.
     * @param array $arSort Массив с параметрами сортировки.
     * @return array Массив с "коротким" списком полей для запроса на получение элементов.
     */
    private function makeShortSelect($arSort)
    {
        $shortSelect = array('ID', 'IBLOCK_ID');
        foreach (array_keys($arSort) as $index) {
            if (!in_array($index, $shortSelect)) {
                $shortSelect[] = $index;
            }
        }
        return $shortSelect;
    }

    /**
     * Выполнить запрос на получение элементов инфоблока с коротким списком полей.
     * @param array $arSort Массив с параметрами сортировки.
     * @param array $arFilter Массив с параметрами фильтрации.
     * @param array $shortSelect Поля для выборки.
     * @return $rsElement Полученный список элементов инфоблока.
     */
    private function getShortElements($arSort, $arFilter, $shortSelect)
    {
        $rsElement = CIBlockElement::GetList(
            $arSort, 
            array_merge(
                $arFilter,
                $this->arrFilter
            ), 
            false, 
            $this->arNavParams, 
            $shortSelect
        );
        while ($row = $rsElement->Fetch()) {
            $id = (int)$row['ID'];
            $this->arResult['ITEMS'][$id] = $row;
            $this->arResult['ELEMENTS'][] = $id;
        }
        unset($row);

        return $rsElement;
    }

    /**
     * Выполнить запрос на получение элементов инфоблоков.
     * @param array $arSelect Поля для элементов, которые необходимо получить.
     * @param array $arParams Параметры компонента.
     * @param array $elementFilter Массив с идентификаторами инфоблока, сайта и 
     * полученных в "коротком" запросе элементов.
     * @return array Полученные элементы инфоблока.
     */
    private function getIblockElements($arSelect, &$arParams, &$elementFilter)
    {
        /*
         * Отображение ещё не опубликованных инфоблоков. 
         */
        if (isset($this->arrFilter['SHOW_NEW'])) {
            $elementFilter['SHOW_NEW'] = $this->arrFilter['SHOW_NEW'];
        }
        /*
         * Получить список элементов в соответствии с фильтром выше. 
         */
        $iterator = CIBlockElement::GetList(array(), $elementFilter, false, false, $arSelect);
        /*
         * Применить шаблоны путей.
         */
        $iterator->SetUrlTemplates($arParams['DETAIL_URL'], '', ($arParams['IBLOCK_URL'] ?? ''));
        
        return $iterator;
    }

    /**
     * Выполнить обработку элементов инфоблока для их вывода в компоненте.
     * @param array $iterator Массив-результат запроса на получение элементов инфоблока.
     * @param array $arParams Параметры компонента.
     * @param bool $bGetProperty Факт наличия у инфоблока свойств.
     * @param string $listPageUrl Ссылка на страницу со списком элементов.
     */
    private function processIblockElements($iterator, $arParams, $bGetProperty, &$listPageUrl)
    {
        $obParser = new CTextParser;
        /*
         * Перебрать все полученные элементы 
         */
        while ($arItem = $iterator->GetNext()) {
            /*
             * Добавить ссылки на удаление и редактирование элемента. 
             */
            $this->addElementButtons($arItem);
            /*
             * Обрезать текст анонса, если нужно. 
             */
            $this->cutPreviewText($obParser, $arItem, $arParams);
            /*
             * Отображение даты начала активности. 
             */
            $this->displayActiveDate($arItem, $arParams);

            Iblock\InheritedProperty\ElementValues::queue($arItem['IBLOCK_ID'], $arItem['ID']);
            $arItem['FIELDS'] = array();
            /*
             * Если есть массив свойств, то добавить такой массив для $arItem.
             */
            if ($bGetProperty) {
                $arItem['PROPERTIES'] = array();
            }
            $arItem['DISPLAY_PROPERTIES'] = array();
            /*
             * Учитывать время последней модификации. 
             */
            $this->setLastModified($arItem, $arParams);
            /*
             * Ссылка на страницу со списком элементов. 
             */
            if ($listPageUrl === '' && isset($arItem['~LIST_PAGE_URL'])) {
                $listPageUrl = $arItem['~LIST_PAGE_URL'];
            }
            /*
             * Внести полученный элемент $arItem в подмассив ITEMS. 
             */
            $id = (int)$arItem['ID'];
            $this->arResult['ITEMS'][$id] = $arItem;
        }
    }

    /**
     * Добавить к элементу кнопки редактирования и удаления.
     * @param array $arItem Элемент инфоблока.
     */
    private function addElementButtons(&$arItem)
    {
        /*
         * Набор кнопок для управления текущим элементом инфоблока. 
         */
        $arButtons = CIBlock::GetPanelButtons(
            $arItem['IBLOCK_ID'], // Идентификатор инфоблока.
            $arItem['ID'], // Идентификатор элемента.
            0, // Идентификатор раздела.
            array('SECTION_BUTTONS' => false, 'SESSID' => false)
        );
        /*
         * Задать ссылки на редактирование и удаление элемента. 
         */
        $arItem['EDIT_LINK'] = $arButtons['edit']['edit_element']['ACTION_URL'] ?? '';
        $arItem['DELETE_LINK'] = $arButtons['edit']['delete_element']['ACTION_URL'] ?? '';
    }

    /**
     * Обрезка текста анонса, если требуется. 
     * @param CTextParser Парсер для работы с текстом.
     * @param array $arItem Элемент инфоблока.
     * @param array $arParams Параметры компонента.
     */
    private function cutPreviewText($obParser, &$arItem, $arParams)
    {
        if ($arParams['PREVIEW_TRUNCATE_LEN'] > 0) {
            $arItem['PREVIEW_TEXT'] = $obParser->html_cut($arItem['PREVIEW_TEXT'], $arParams['PREVIEW_TRUNCATE_LEN']);
        }
    }

    /**
     * Отображение даты начала активности.
     * @param array $arItem Элемент инфоблока.
     * @param array $arParams Параметры компонента.
     */
    private function displayActiveDate(&$arItem, $arParams)
    {
        if ($arItem['ACTIVE_FROM'] <> '') {
            $arItem['DISPLAY_ACTIVE_FROM'] = CIBlockFormatProperties::DateFormat(
                $arParams['ACTIVE_DATE_FORMAT'], 
                MakeTimeStamp($arItem['ACTIVE_FROM'], 
                CSite::GetDateFormat())
            );
        } else {
            $arItem['DISPLAY_ACTIVE_FROM'] = '';
        }
    }

    /**
     * Учитывать время последней модификации.
     * @param array $arItem Элемент инфоблока.
     * @param array $arParams Параметры компонента.
     */
    private function setLastModified($arItem, $arParams)
    {
        if ($arParams['SET_LAST_MODIFIED']) {
            $time = DateTime::createFromUserTime($arItem['TIMESTAMP_X']);
            if (
                !isset($this->arResult['ITEMS_TIMESTAMP_X'])
                || $time->getTimestamp() > $this->arResult['ITEMS_TIMESTAMP_X']->getTimestamp()
            ) {
                $this->arResult['ITEMS_TIMESTAMP_X'] = $time;
            }
        }
    }

    /**
     * Если есть массив свойств, то записать свойства в $arResult[ITEMS].
     * @param bool $bGetProperty Флаг наличия свойств.
     * @param array $elementFilter Массив с идентификаторами инфоблока, сайта и элементов.
     */
    private function setElementProperties($bGetProperty, &$elementFilter)
    {
        if ($bGetProperty) {
            unset($elementFilter['IBLOCK_LID']);
            CIBlockElement::GetPropertyValuesArray(
                $this->arResult['ITEMS'],
                $this->arResult['ID'],
                $elementFilter
            );
        }
    }

    /**
     * Заполнить значения свойств элементов.
     * @param bool $bGetProperty Флаг наличия свойств.
     * @param array $arParams Параметры компонента.
     */
    private function fillElementProperties($bGetProperty, $arParams)
    {
        $this->arResult['ITEMS'] = array_values($this->arResult['ITEMS']);
        foreach ($this->arResult['ITEMS'] as &$arItem) {
            /*
             * Заполнить значения свойств для отображения. 
             */
            if ($bGetProperty) {
                foreach ($arParams['PROPERTY_CODE'] as $pid) {
                    $prop = &$arItem['PROPERTIES'][$pid];
                    if (
                        (is_array($prop['VALUE']) && count($prop['VALUE']) > 0)
                        || (!is_array($prop['VALUE']) && $prop['VALUE'] <> '')
                    ) {
                        $arItem['DISPLAY_PROPERTIES'][$pid] = CIBlockFormatProperties::GetDisplayValue($arItem, $prop);
                    }
                }
            }
    
            $ipropValues = new Iblock\InheritedProperty\ElementValues($arItem['IBLOCK_ID'], $arItem['ID']);
            $arItem['IPROPERTY_VALUES'] = $ipropValues->getValues();
            Iblock\Component\Tools::getFieldImageData(
                $arItem,
                array('PREVIEW_PICTURE', 'DETAIL_PICTURE'),
                Iblock\Component\Tools::IPROPERTY_ENTITY_ELEMENT,
                'IPROPERTY_VALUES'
            );
    
            foreach($arParams['FIELD_CODE'] as $code) {
                if (array_key_exists($code, $arItem)) {
                    $arItem['FIELDS'][$code] = $arItem[$code];
                }
            }
        }
        unset($arItem);
        if ($bGetProperty) {
            \CIBlockFormatProperties::clearCache();
        }
    }

    /**
     * Рассортировать полученные элементы инфоблоков в подмассивы $arResult['ITEMS']
     * с ID инфоблоков в качестве ключей.
     */
    private function sortIblockElements()
    {
        /*
         * Вспомогательный массив для сортировки. 
         */
        $arTemp = [];
        /*
         * Перебрать все полученные элементы инфоблоков. 
         */
        foreach ($this->arResult['ITEMS'] as $arElement) {
            /*
             * Если ID инфоблока для массива сортировки новый, то добавить элемент-подмассив.
             */
            if (!array_key_exists($arElement['IBLOCK_ID'], $arTemp)) {
                $arTemp[$arElement['IBLOCK_ID']] = [];
                $arTemp[$arElement['IBLOCK_ID']][] = $arElement;
            /*
             * Иначе добавить элемент в существующий подмассив.
             */
            } else {
                $arTemp[$arElement['IBLOCK_ID']][] = $arElement;
            }
        }
        /*
         * Записать в ITEMS результат сортировки. 
         */
        $this->arResult['ITEMS'] = $arTemp;
    }

    /**
     * Обработка ссылок для постраничной навигации.
     * @param array $navComponentParameters Параметры постраничной навигации.
     * @param string $listPageUrl Ссылка на страницу со списком элементов.
     * @param array $arParams Параметры компонента.
     */
    private function processPageLinks(&$navComponentParameters, $listPageUrl, $arParams)
    {
        if ($arParams['PAGER_BASE_LINK_ENABLE'] === 'Y') {
            /*
             * Адрес для построения ссылок. 
             */
            $pagerBaseLink = trim($arParams['PAGER_BASE_LINK']);
            /*
             * Если адрес не задан, то построить его. 
             */
            if ($pagerBaseLink === '') {
                if (
                    $this->arResult['SECTION']
                    && $this->arResult['SECTION']['PATH']
                    && $this->arResult['SECTION']['PATH'][0]
                    && $this->arResult['SECTION']['PATH'][0]['~SECTION_PAGE_URL']
                ) {
                    $pagerBaseLink = $this->arResult['SECTION']['PATH'][0]['~SECTION_PAGE_URL'];
                } elseif (
                    $listPageUrl !== ''
                ) {
                    $pagerBaseLink = $listPageUrl;
                }
            }
    
            if ($this->pagerParameters && isset($this->pagerParameters['BASE_LINK'])) {
                $pagerBaseLink = $this->pagerParameters['BASE_LINK'];
                unset($this->pagerParameters['BASE_LINK']);
            }
    
            /*
             * Задать параметры для постраничной навигации. 
             */
            $navComponentParameters['BASE_LINK'] = CHTTP::urlAddParams($pagerBaseLink, $this->pagerParameters, array('encode' => true));
        }
    }

    /**
     * Задание панели постраничной навигации.
     * @param array $navComponentParameters Параметры постраничной навигации.
     * @param $rsElement Результат запроса на получение элементов инфоблока в короткой форме.
     * @param array $arParams Параметры компонента.
     */
    private function setPagerPanel($navComponentParameters, $rsElement, $arParams)
    {
        $this->arResult['NAV_STRING'] = $rsElement->GetPageNavStringEx(
            $navComponentObject,
            $arParams['PAGER_TITLE'],
            $arParams['PAGER_TEMPLATE'],
            $arParams['PAGER_SHOW_ALWAYS'],
            $this,
            $navComponentParameters
        );
        $this->arResult['NAV_CACHED_DATA'] = null;
        $this->arResult['NAV_RESULT'] = $rsElement;
        $this->arResult['NAV_PARAM'] = $navComponentParameters;
    }

    /**
     * Указать, какие ключи массива $arResult должны кешироваться.
     */
    private function setResultCachedKeys()
    {
        $this->setResultCacheKeys(array(
            'ID',
            'IBLOCK_TYPE_ID',
            'LIST_PAGE_URL',
            'NAV_CACHED_DATA',
            'NAME',
            'SECTION',
            'ELEMENTS',
            'IPROPERTY_VALUES',
            'ITEMS_TIMESTAMP_X',
        ));
    }

    /**
     * Получить набор кнопок для управления выбранным инфоблоком.
     * @param array $arParams Параметры компонента.
     * @return array Массив, описывающий набор кнопок для управления элементами инфоблока.
     */
    private function getIblockButtons($arParams)
    {
        return CIBlock::GetPanelButtons(
            $this->arResult['ID'], // ID выбранного инфоблока.
            0, // ID элемента
            $arParams['PARENT_SECTION'], // ID раздела
            array('SECTION_BUTTONS' => false)
        );
    }

    /**
     * Задание параметров тулбара.
     * @param array $arButtons Массив, описывающий набор кнопок для управления элементами инфоблока.
     * @param array $arParams Параметры компонента.
     */
    private function setToolbar($arButtons, $arParams)
    {
        if (
            is_array($arButtons['intranet'])
            && is_object($INTRANET_TOOLBAR)
            && $arParams['INTRANET_TOOLBAR'] !== "N"
        ) {
            $APPLICATION->AddHeadScript('/bitrix/js/main/utils.js');
            foreach ($arButtons['intranet'] as $arButton) {
                $INTRANET_TOOLBAR->AddButton($arButton);
            }
        }
    }

    /**
     * Задание параметров установки заголовка страницы компонентом.
     * @param array $arButtons Массив, описывающий набор кнопок для управления элементами инфоблока.
     * @param array $arParams Параметры компонента.
     * @return array Параметры задания заголовка страницы.
     */
    private function setPageTitleParams($arButtons, $arParams)
    {
        if ($arParams['SET_TITLE']) {
            if (isset($arButtons['submenu']['edit_iblock'])) {
                /* 
                 * Сохранить в $arTitleOptions ссылку на редактирование инфоблока в админке.
                 */
                return [
                    'ADMIN_EDIT_LINK' => $arButtons['submenu']['edit_iblock']['ACTION'],
                    'PUBLIC_EDIT_LINK' => '',
                    'COMPONENT_NAME' => $this->getName(),
                ];
            }
        }
    }

    /**
     * Задание заголовка страницы компонентом.
     * @param array $arParams Параметры компонента.
     * @param bool $ipropertyExists Наличие свойств SEO.
     * @param array $iproperty Массив, содержащий SEO информацию.
     * @param array $arTitleOptions Параметры задания заголовка страницы.
     */
    private function setPageTitle($arParams, $ipropertyExists, $iproperty, $arTitleOptions)
    {
        global $APPLICATION;
        if ($arParams['SET_TITLE']) {
            if ($ipropertyExists && $iproperty['SECTION_PAGE_TITLE'] != "") {
                $APPLICATION->SetTitle($iproperty['SECTION_PAGE_TITLE'], $arTitleOptions);
            } elseif(isset($this->arResult['NAME'])) {
                $APPLICATION->SetTitle($this->arResult['NAME'], $arTitleOptions);
            }
        }
    }

    /**
     * Задание SEO информации страницы.
     * @param array $iproperty Массив, содержащий SEO информацию.
     * @param array $arParams Параметры компонента.
     * @param array $arTitleOptions Параметры задания заголовков.
     */
    private function setIproperties($iproperty, $arParams, $arTitleOptions)
    {
        global $APPLICATION;
        if ($arParams['SET_BROWSER_TITLE'] === 'Y' && $iproperty['SECTION_META_TITLE'] != '') {
            $APPLICATION->SetPageProperty('title', $iproperty['SECTION_META_TITLE'], $arTitleOptions);
        }
        if ($arParams['SET_META_KEYWORDS'] === 'Y' && $iproperty['SECTION_META_KEYWORDS'] != '') {
            $APPLICATION->SetPageProperty('keywords', $iproperty['SECTION_META_KEYWORDS'], $arTitleOptions);
        }
        if ($arParams['SET_META_DESCRIPTION'] === 'Y' && $iproperty['SECTION_META_DESCRIPTION'] != '') {
            $APPLICATION->SetPageProperty('description', $iproperty['SECTION_META_DESCRIPTION'], $arTitleOptions);
        }
    }

    /**
     * Добавление имени инфоблока в цепочку навигации, если требуется.
     * @param array $arParams Параметры компонента.
     */
    private function addIblockToNavChain($arParams)
    {
        global $APPLICATION;
        if ($arParams['INCLUDE_IBLOCK_INTO_CHAIN'] && isset($this->arResult['NAME'])) {
            if ($arParams['ADD_SECTIONS_CHAIN'] && is_array($this->arResult['SECTION'])) {
                $APPLICATION->AddChainItem(
                    $this->arResult['NAME'],
                    $arParams['IBLOCK_URL'] <> ''? $arParams['IBLOCK_URL'] : $this->arResult['LIST_PAGE_URL']
                );
            } else {
                $APPLICATION->AddChainItem($this->arResult['NAME']);
            }
        }
    }

    /**
     * Добавление имен разделов в цепочку навигации.
     * @param array $arParams Параметры компонента.
     */
    private function addSectionsToNavChain($arParams)
    {
        global $APPLICATION;
        if ($arParams['ADD_SECTIONS_CHAIN'] && is_array($this->arResult['SECTION'])) {
            foreach ($this->arResult['SECTION']['PATH'] as $arPath) {
                if ($arPath['IPROPERTY_VALUES']['SECTION_PAGE_TITLE'] != '') {
                    $APPLICATION->AddChainItem($arPath['IPROPERTY_VALUES']['SECTION_PAGE_TITLE'], $arPath['~SECTION_PAGE_URL']);
                }
                else {
                    $APPLICATION->AddChainItem($arPath['NAME'], $arPath['~SECTION_PAGE_URL']);
                }
            }
        }
    }
}
