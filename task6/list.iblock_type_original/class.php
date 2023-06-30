<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) {
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
     * ---------------
     * ----УДАЛИТЬ----
     * ---------------
     */
    public $arSelect;
    public $bGetProperty;
    public $arFilter;



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
        CPageOption::SetOptionString("main", "nav_page_in_session", "N");
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
         * Получить инфоблок, ID или код которого был передан в arParams.
         */
        $getIblockResult = $this->getIblock($arParams);
        if (!$getIblockResult) {
            return false;
        }
        /*
         * Имеет ли пользователь право доступа для работы с компонентом. 
         */
        $this->arResult["USER_HAVE_ACCESS"] = $this->bUSER_HAVE_ACCESS;
        /*
         * Массив полей, которые необходимо получить при выборке элементов инфоблока. 
         */
        $arSelect = $this->setSelectFields($arParams);
        /*
         * $bGetProperty - флаг существования свойств. 
         */
        $bGetProperty = !empty($arParams["PROPERTY_CODE"]);
        /*
         * Параметры фильтра для выборки элементов инфоблока.
         */
        $arFilter = $this->setFilter($arParams);



        /**
         * ----------------
         * -----УДАЛИТЬ----
         * ----------------
         */
        $this->arSelect = $arSelect;
        $this->bGetProperty = $bGetProperty;
        $this->arFilter = $arFilter;

        /**
         * 
         * 
         * ПРОДОЛЖИТЬ ЗДЕСЬ
         * 
         */
    }

    /**
     * Установка значений по умолчанию для незаполненных параметров компонента.
     * @param array $arParams Массив параметров компонента.
     */
    private function setDefaultValues(&$arParams)
    {
        if (!isset($arParams["CACHE_TIME"])) {
            $arParams["CACHE_TIME"] = 36000000;
        }

        $arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"] ?? '');
        if (empty($arParams["IBLOCK_TYPE"])) {
            $arParams["IBLOCK_TYPE"] = "news";
        }

        $this->setDefaultSort1($arParams);
        $this->setDefaultSort2($arParams);

        $arParams["IBLOCK_ID"] = trim($arParams["IBLOCK_ID"] ?? '');
        $arParams["PARENT_SECTION"] = (int)($arParams["PARENT_SECTION"] ?? 0);
        $arParams["PARENT_SECTION_CODE"] ??= '';
        $arParams["INCLUDE_SUBSECTIONS"] = ($arParams["INCLUDE_SUBSECTIONS"] ?? '') !== "N";
        $arParams["SET_LAST_MODIFIED"] = ($arParams["SET_LAST_MODIFIED"] ?? '') === "Y";
        $arParams["SET_TITLE"] = ($arParams["SET_TITLE"] ?? '') !== "N";
        $arParams["SET_BROWSER_TITLE"] = ($arParams["SET_BROWSER_TITLE"] ?? '') === 'N' ? 'N' : 'Y';
        $arParams["SET_META_KEYWORDS"] = ($arParams["SET_META_KEYWORDS"] ?? '') === 'N' ? 'N' : 'Y';
        $arParams["SET_META_DESCRIPTION"] = ($arParams["SET_META_DESCRIPTION"] ?? '') === 'N' ? 'N' : 'Y';
        $arParams["ADD_SECTIONS_CHAIN"] = ($arParams["ADD_SECTIONS_CHAIN"] ?? '') !== "N";
        $arParams["INCLUDE_IBLOCK_INTO_CHAIN"] = ($arParams["INCLUDE_IBLOCK_INTO_CHAIN"] ?? '') !== "N";
        $arParams["STRICT_SECTION_CHECK"] = ($arParams["STRICT_SECTION_CHECK"] ?? '') === "Y";
        $arParams["ACTIVE_DATE_FORMAT"] = trim($arParams["ACTIVE_DATE_FORMAT"] ?? '');
        if (empty($arParams["ACTIVE_DATE_FORMAT"])) {
            $arParams["ACTIVE_DATE_FORMAT"] = $DB->DateFormatToPHP(\CSite::GetDateFormat("SHORT"));
        }
        $arParams["PREVIEW_TRUNCATE_LEN"] = (int)($arParams["PREVIEW_TRUNCATE_LEN"] ?? 0);
        $arParams["HIDE_LINK_WHEN_NO_DETAIL"] = ($arParams["HIDE_LINK_WHEN_NO_DETAIL"] ?? '') === "Y";
        $arParams["CHECK_DATES"] = ($arParams["CHECK_DATES"] ?? '') !== "N";
        $arParams["DISPLAY_TOP_PAGER"] = ($arParams["DISPLAY_TOP_PAGER"] ?? '') === "Y";
        $arParams["DISPLAY_BOTTOM_PAGER"] = ($arParams["DISPLAY_BOTTOM_PAGER"] ?? '') !== "N";
        $arParams["PAGER_TITLE"] = trim($arParams["PAGER_TITLE"] ?? '');
        $arParams["PAGER_SHOW_ALWAYS"] = ($arParams["PAGER_SHOW_ALWAYS"] ?? '') === "Y";
        $arParams["PAGER_TEMPLATE"] = trim($arParams["PAGER_TEMPLATE"] ?? '');
        $arParams["PAGER_DESC_NUMBERING"] = ($arParams["PAGER_DESC_NUMBERING"] ?? '') === "Y";
        $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"] = (int)($arParams["PAGER_DESC_NUMBERING_CACHE_TIME"] ?? 0);
        $arParams["PAGER_SHOW_ALL"] = ($arParams["PAGER_SHOW_ALL"] ?? '') === "Y";
        $arParams["PAGER_BASE_LINK_ENABLE"] ??= 'N';
        $arParams["PAGER_BASE_LINK"] ??= '';
        $arParams["INTRANET_TOOLBAR"] ??= '';
        $arParams["CHECK_PERMISSIONS"] = ($arParams["CHECK_PERMISSIONS"] ?? '') !== "N";
        $arParams["MESSAGE_404"] ??= '';
        $arParams["SET_STATUS_404"] ??= 'N';
        $arParams["SHOW_404"] ??= 'N';
        $arParams["FILE_404"] ??= '';

        $this->setDefaultFieldCode($arParams);
        $this->setDefaultPropertyCode($arParams);

        $arParams["DETAIL_URL"] = trim($arParams["DETAIL_URL"] ?? '');
        $arParams["SECTION_URL"] = trim($arParams["SECTION_URL"] ?? '');
        $arParams["IBLOCK_URL"] = trim($arParams["IBLOCK_URL"] ?? '');

        $arParams["NEWS_COUNT"] = (int)($arParams["NEWS_COUNT"] ?? 0);
        if ($arParams["NEWS_COUNT"] <= 0) {
            $arParams["NEWS_COUNT"] = 20;
        }

        $this->setDefaultFilter($arParams);
        $this->setDefaultNavigationParams($arParams);
        $this->setDefaultUserPermission($arParams);

        $arParams["CACHE_FILTER"] = ($arParams["CACHE_FILTER"] ?? '') === "Y";
        if (!$arParams["CACHE_FILTER"] && !empty($this->arrFilter)) {
            $arParams["CACHE_TIME"] = 0;
        }

        $arParams["CACHE_GROUPS"] ??= '';
    }

    /**
     * Установка первой сортировки по умолчанию.
     * @param array $arParams Параметры компонента.
     */
    private function setDefaultSort1(&$arParams)
    {
        $arParams["SORT_BY1"] = trim($arParams["SORT_BY1"] ?? '');
        if (empty($arParams["SORT_BY1"])) {
            $arParams["SORT_BY1"] = "ACTIVE_FROM";
        }
        if (
            !isset($arParams["SORT_ORDER1"])
            || !preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams["SORT_ORDER1"])
        ) {
            $arParams["SORT_ORDER1"]="DESC";
        }
    }

    /**
     * Установка второй сортировки по умолчанию.
     * @param array $arParams Параметры компонента.
     */
    private function setDefaultSort2(&$arParams)
    {
        $arParams["SORT_BY2"] = trim($arParams["SORT_BY2"] ?? '');
        if (empty($arParams["SORT_BY2"])) {
            if (mb_strtoupper($arParams["SORT_BY1"]) === 'SORT') {
                $arParams["SORT_BY2"] = "ID";
                $arParams["SORT_ORDER2"] = "DESC";
            } else {
                $arParams["SORT_BY2"] = "SORT";
            }
        }
        if (
            !isset($arParams["SORT_ORDER2"])
            || !preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams["SORT_ORDER2"])
        ) {
            $arParams["SORT_ORDER2"]="ASC";
        }
    }

    /**
     * Проверка массива полей на пустоту, удаление пустых значений.
     * @param array $arParams Параметры компонента. 
     */
    private function setDefaultFieldCode(&$arParams)
    {
        if (empty($arParams["FIELD_CODE"]) || !is_array($arParams["FIELD_CODE"])) {
            $arParams["FIELD_CODE"] = [];
        }
        foreach ($arParams["FIELD_CODE"] as $key => $val) {
            if (!$val) {
                unset($arParams["FIELD_CODE"][$key]);
            }
        }
    }

    /**
     * Проверка на пустоту массива свойств и удаление пустых значений.
     * @param array $arParams Параметры компонента.
     */
    private function setDefaultPropertyCode(&$arParams)
    {
        if (empty($arParams["PROPERTY_CODE"]) || !is_array($arParams["PROPERTY_CODE"])) {
            $arParams["PROPERTY_CODE"] = array();
        }
        foreach ($arParams["PROPERTY_CODE"] as $key => $val) {
            if ($val === "") {
                unset($arParams["PROPERTY_CODE"][$key]);
            }
        }
    }

    /**
     * Предварительная подготовка фильтра.
     * @param array $arParams Параметры компонента.
     */
    private function setDefaultFilter(&$arParams)
    {
        if (!empty($arParams["FILTER_NAME"]) && preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["FILTER_NAME"])) {
            $this->arrFilter = $GLOBALS[$arParams["FILTER_NAME"]] ?? [];
            if (!is_array($this->arrFilter)) {
                $this->$arrFilter = [];
            }
        }
    }

    /**
     * Установка параметров постраничной навигации по умолчанию.
     * @param array $arParams Параметры компонента.
     */
    private function setDefaultNavigationParams(&$arParams)
    {
        if ($arParams["DISPLAY_TOP_PAGER"] || $arParams["DISPLAY_BOTTOM_PAGER"]) {
            $this->arNavParams = array(
                "nPageSize" => $arParams["NEWS_COUNT"],
                "bDescPageNumbering" => $arParams["PAGER_DESC_NUMBERING"],
                "bShowAll" => $arParams["PAGER_SHOW_ALL"],
            );
            $this->arNavigation = CDBResult::GetNavParams($this->arNavParams);
            if ((int)$this->arNavigation["PAGEN"] === 0 && $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"] > 0) {
                $arParams["CACHE_TIME"] = $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"];
            }
        } else {
            $this->arNavParams = array(
                "nTopCount" => $arParams["NEWS_COUNT"],
                "bDescPageNumbering" => $arParams["PAGER_DESC_NUMBERING"],
            );
            $this->arNavigation = false;
        }
        if (!empty($arParams["PAGER_PARAMS_NAME"]) && preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["PAGER_PARAMS_NAME"])) {
            $this->pagerParameters = $GLOBALS[$arParams["PAGER_PARAMS_NAME"]] ?? [];
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
        $arParams["USE_PERMISSIONS"] = ($arParams["USE_PERMISSIONS"] ?? '') === "Y";
        if (!is_array($arParams["GROUP_PERMISSIONS"] ?? null)) {
            $adminGroupCode = 1;
            $arParams["GROUP_PERMISSIONS"] = [$adminGroupCode];
        }

        $this->bUSER_HAVE_ACCESS = !$arParams["USE_PERMISSIONS"];
        if ($arParams["USE_PERMISSIONS"] && isset($GLOBALS["USER"]) && is_object($GLOBALS["USER"])) {
            $arUserGroupArray = $USER->GetUserGroupArray();
            foreach ($arParams["GROUP_PERMISSIONS"] as $PERM) {
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
        if(!Loader::includeModule("iblock")) {
            $this->abortResultCache();
            ShowError(GetMessage("IBLOCK_TYPE_IBLOCK_MODULE_NOT_INSTALLED"));
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
        if(is_numeric($arParams["IBLOCK_ID"])) {
            $rsIBlock = CIBlock::GetList(array(), array(
                "ACTIVE" => "Y",
                "ID" => $arParams["IBLOCK_ID"],
            ));
        } else {
            $rsIBlock = CIBlock::GetList(array(), array(
                "ACTIVE" => "Y",
                "CODE" => $arParams["IBLOCK_ID"],
                "SITE_ID" => SITE_ID,
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
                trim($arParams["MESSAGE_404"]) ?: GetMessage("T_IBLOCK_TYPE_LIST_NA")
                ,true
                ,$arParams["SET_STATUS_404"] === "Y"
                ,$arParams["SHOW_404"] === "Y"
                ,$arParams["FILE_404"]
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
        return array_merge($arParams["FIELD_CODE"], array(
            "ID",
            "IBLOCK_ID",
            "IBLOCK_SECTION_ID",
            "NAME",
            "ACTIVE_FROM",
            "TIMESTAMP_X",
            "DETAIL_PAGE_URL",
            "LIST_PAGE_URL",
            "DETAIL_TEXT",
            "DETAIL_TEXT_TYPE",
            "PREVIEW_TEXT",
            "PREVIEW_TEXT_TYPE",
            "PREVIEW_PICTURE",
        ));
    }

    /**
     * 
     */
    private function setFilter(&$arParams)
    {
        /*
         * Включить в фильтр идентификаторы инфоблока, сайта, активность и права доступа. 
         */
        $arFilter = array(
            "IBLOCK_ID" => $arResult["ID"],
            "IBLOCK_LID" => SITE_ID,
            "ACTIVE" => "Y",
            "CHECK_PERMISSIONS" => $arParams['CHECK_PERMISSIONS'] ? "Y" : "N",
        );
        /*
         * Если выбрано "Показывать только активные на данный момент элементы",
         * то дополнить фильтр. 
         */
        if ($arParams["CHECK_DATES"]) {
            $arFilter["ACTIVE_DATE"] = "Y";
        }



        return $arFilter;
    }

    /**
     * 
     */
    private function checkParentSection(&$arParams)
    {


    }





}
