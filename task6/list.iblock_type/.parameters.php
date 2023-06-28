<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) {
    die();
}
/** @var array $arCurrentValues */

/*
 * Компонент работает только при включенном модуле инфоблоков. 
 */
use Bitrix\Main\Loader;
if (!Loader::includeModule('iblock')) {
    return;
}

/*
 * Проверка задания и корректности идентификатора инфоблока для списка. 
 */
$iblockExists = (!empty($arCurrentValues['IBLOCK_ID']) && (int)$arCurrentValues['IBLOCK_ID'] > 0);
/*
 * Получить все типы инфоблоков. 
 */
$arTypesEx = CIBlockParameters::GetIBlockTypes();

/*
 * Список информационных блоков. 
 */
$arIBlocks = [];
$iblockFilter = [
    'ACTIVE' => 'Y',
];
/*
 * Если задан тип информационного блока, то добавить его в фильтр инфоблоков.
 */
if (!empty($arCurrentValues['IBLOCK_TYPE'])) {
    $iblockFilter['TYPE'] = $arCurrentValues['IBLOCK_TYPE'];
}
/*
 * Задать в фильтре инфоблоков текущий сайт.
 */
if (isset($_REQUEST['site'])) {
    $iblockFilter['SITE_ID'] = $_REQUEST['site'];
}
/*
 * Получить инфоблоки, удовлетворяющие сформированному фильтру по типу и сайту. 
 */
$db_iblock = CIBlock::GetList(["SORT"=>"ASC"], $iblockFilter);
while($arRes = $db_iblock->Fetch()) {
    $arIBlocks[$arRes["ID"]] = "[" . $arRes["ID"] . "] " . $arRes["NAME"];
}
/*
 * Направление сортировки. 
 */
$arSorts = [
    "ASC"=>GetMessage("T_IBLOCK_TYPE_DESC_SORT_ASC"),
    "DESC"=>GetMessage("T_IBLOCK_TYPE_DESC_SORT_DESC"),
];
/*
 * Поля сортировки.
 */
$arSortFields = [
    "ID"=>GetMessage("T_IBLOCK_TYPE_DESC_FID"),
    "NAME"=>GetMessage("T_IBLOCK_TYPE_DESC_FNAME"),
    "ACTIVE_FROM"=>GetMessage("T_IBLOCK_TYPE_DESC_FACT"),
    "SORT"=>GetMessage("T_IBLOCK_TYPE_DESC_FSORT"),
    "TIMESTAMP_X"=>GetMessage("T_IBLOCK_TYPE_DESC_FTSAMP"),
];
/*
 * Массив свойств в формате $arProperty[Код свойства] => "[Код свойства] Имя свойства". 
 */
$arProperty = [];
/*
 * Массив свойств с типом L - список, N - число или S - строка.
 */
$arProperty_LNS = [];
/*
 * Если задан корректный идентификатор инфоблока, то получить список его свойств.
 */
if ($iblockExists) {
    $rsProp = CIBlockProperty::GetList(
        [
            "SORT" => "ASC",
            "NAME" => "ASC",
        ],
        [
            "ACTIVE" => "Y",
            "IBLOCK_ID" => $arCurrentValues["IBLOCK_ID"],
        ]
    );
    while ($arr = $rsProp->Fetch()) {
        $arProperty[$arr["CODE"]] = "[" . $arr["CODE"] . "] " . $arr["NAME"];
        if (in_array($arr["PROPERTY_TYPE"], ["L", "N", "S"])) {
            $arProperty_LNS[$arr["CODE"]] = "[" . $arr["CODE"] . "] " . $arr["NAME"];
        }
    }
}
/*
 * Массив параметров компонента. 
 */
$arComponentParameters = [
    "GROUPS" => [],
    "PARAMETERS" => [
        "AJAX_MODE" => [],
        /*
         * Тип инфоблока. 
         */
        "IBLOCK_TYPE" => [
            "PARENT" => "BASE",
            "NAME" => GetMessage("T_IBLOCK_TYPE_DESC_LIST_TYPE"),
            "TYPE" => "LIST",
            "VALUES" => $arTypesEx,
            "DEFAULT" => "news",
            "REFRESH" => "Y",
        ],
        /*
         * Идентификатор инфоблока. 
         */
        "IBLOCK_ID" => [
            "PARENT" => "BASE",
            "NAME" => GetMessage("T_IBLOCK_TYPE_DESC_LIST_ID"),
            "TYPE" => "LIST",
            "VALUES" => $arIBlocks,
            "DEFAULT" => '={$_REQUEST["ID"]}',
            "ADDITIONAL_VALUES" => "Y",
            "REFRESH" => "Y",
        ],
        /*
         * Количество элементов инфоблоков на странице. 
         */
        "NEWS_COUNT" => [
            "PARENT" => "BASE",
            "NAME" => GetMessage("T_IBLOCK_TYPE_DESC_LIST_CONT"),
            "TYPE" => "STRING",
            "DEFAULT" => "20",
        ],
        /*
         * Поле для первой сортировки инфоблоков. 
         */
        "SORT_BY1" => [
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("T_IBLOCK_TYPE_DESC_IBORD1"),
            "TYPE" => "LIST",
            "DEFAULT" => "ACTIVE_FROM",
            "VALUES" => $arSortFields,
            "ADDITIONAL_VALUES" => "Y",
        ],
        /*
         * Направление для первой сортировки.
         */
        "SORT_ORDER1" => [
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("T_IBLOCK_TYPE_DESC_IBBY1"),
            "TYPE" => "LIST",
            "DEFAULT" => "DESC",
            "VALUES" => $arSorts,
            "ADDITIONAL_VALUES" => "Y",
        ],
        /*
         * Поле для второй сортировки.
         */
        "SORT_BY2" => [
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("T_IBLOCK_TYPE_DESC_IBORD2"),
            "TYPE" => "LIST",
            "DEFAULT" => "SORT",
            "VALUES" => $arSortFields,
            "ADDITIONAL_VALUES" => "Y",
        ],
        /*
         * Направление для второй сортировки.
         */
        "SORT_ORDER2" => [
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("T_IBLOCK_TYPE_DESC_IBBY2"),
            "TYPE" => "LIST",
            "DEFAULT" => "ASC",
            "VALUES" => $arSorts,
            "ADDITIONAL_VALUES" => "Y",
        ],
        /*
         * Фильтр.
         */
        "FILTER_NAME" => [
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("T_IBLOCK_TYPE_FILTER"),
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ],
        /*
         * Поля. 
         */
        "FIELD_CODE" => CIBlockParameters::GetFieldCode(GetMessage("IBLOCK_TYPE_FIELD"), "DATA_SOURCE"),
        /*
         * Свойства.
         */
        "PROPERTY_CODE" => [
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("T_IBLOCK_TYPE_PROPERTY"),
            "TYPE" => "LIST",
            "MULTIPLE" => "Y",
            "VALUES" => $arProperty_LNS,
            "ADDITIONAL_VALUES" => "Y",
        ],
        /*
         * Показывать только активные на данный момент элементы.
         */
        "CHECK_DATES" => [
            "PARENT" => "DATA_SOURCE",
            "NAME" => GetMessage("T_IBLOCK_TYPE_DESC_CHECK_DATES"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ],
        /*
         * URL страницы детального просмотра (по умолчанию - из настроек инфоблока). 
         */
        "DETAIL_URL" => CIBlockParameters::GetPathTemplateParam(
            "DETAIL",
            "DETAIL_URL",
            GetMessage("T_IBLOCK_TYPE_DESC_DETAIL_PAGE_URL"),
            "",
            "URL_TEMPLATES"
        ),
        /*
         * Максимальная длина анонса для вывода (только для типа текст).
         */
        "PREVIEW_TRUNCATE_LEN" => [
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("T_IBLOCK_TYPE_DESC_PREVIEW_TRUNCATE_LEN"),
            "TYPE" => "STRING",
            "DEFAULT" => "",
        ],
        /*
         * Формат показа даты.
         */
        "ACTIVE_DATE_FORMAT" => CIBlockParameters::GetDateFormat(GetMessage("T_IBLOCK_TYPE_DESC_ACTIVE_DATE_FORMAT"), "ADDITIONAL_SETTINGS"),
        "SET_TITLE" => [],
        /*
         * Устанавливать заголовок окна браузера.
         */
        "SET_BROWSER_TITLE" => [
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("CP_BNL_SET_BROWSER_TITLE"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ],
        /*
         * Устанавливать ключевые слова страницы.
         */
        "SET_META_KEYWORDS" => [
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("CP_BNL_IBLOCK_TYPE_SET_META_KEYWORDS"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ],
        /*
         * Устанавливать описание страницы.
         */
        "SET_META_DESCRIPTION" => [
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("CP_BNL_IBLOCK_TYPE_SET_META_DESCRIPTION"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ],
        /*
         * Устанавливать в заголовках ответа время модификации страницы. 
         */
        "SET_LAST_MODIFIED" => [
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("CP_BNL_IBLOCK_TYPE_SET_LAST_MODIFIED"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ],
        /*
         * Включать инфоблок в цепочку навигации. 
         */
        "INCLUDE_IBLOCK_INTO_CHAIN" => [
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("T_IBLOCK_TYPE_DESC_INCLUDE_IBLOCK_INTO_CHAIN"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ],
        /*
         * Включать раздел в цепочку навигации. 
         */
        "ADD_SECTIONS_CHAIN" => [
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("T_IBLOCK_TYPE_DESC_ADD_SECTIONS_CHAIN"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ],
        /*
         * Скрывать ссылку, если нет детального описания. 
         */
        "HIDE_LINK_WHEN_NO_DETAIL" => [
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("T_IBLOCK_TYPE_DESC_HIDE_LINK_WHEN_NO_DETAIL"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ],
        /*
         * ID раздела.
         */
        "PARENT_SECTION" => [
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("IBLOCK_TYPE_SECTION_ID"),
            "TYPE" => "STRING",
            "DEFAULT" => '',
        ],
        /*
         * Код раздела.
         */
        "PARENT_SECTION_CODE" => [
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("IBLOCK_TYPE_SECTION_CODE"),
            "TYPE" => "STRING",
            "DEFAULT" => '',
        ],
        /*
         * Показывать элементы подразделов раздела. 
         */
        "INCLUDE_SUBSECTIONS" => [
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("CP_BNL_IBLOCK_TYPE_INCLUDE_SUBSECTIONS"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ],
        /*
         * Строгая проверка раздела для показа списка.
         */
        "STRICT_SECTION_CHECK" => [
            "PARENT" => "ADDITIONAL_SETTINGS",
            "NAME" => GetMessage("CP_BNL_IBLOCK_TYPE_STRICT_SECTION_CHECK"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ],
        /*
         * Время кеширования. 
         */
        "CACHE_TIME"  =>  ["DEFAULT"=>36000000],
        /*
         * Кешировать при установленном фильтре 
         */
        "CACHE_FILTER" => [
            "PARENT" => "CACHE_SETTINGS",
            "NAME" => GetMessage("IBLOCK_TYPE_CACHE_FILTER"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "N",
        ],
        /*
         * Учитывать права доступа. 
         */
        "CACHE_GROUPS" => [
            "PARENT" => "CACHE_SETTINGS",
            "NAME" => GetMessage("CP_BNL_IBLOCK_TYPE_CACHE_GROUPS"),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
        ],
    ],
];

CIBlockParameters::AddPagerSettings(
    $arComponentParameters,
    GetMessage("T_IBLOCK_TYPE_DESC_PAGER_NEWS"), //$pager_title
    true, //$bDescNumbering
    true, //$bShowAllParam
    true, //$bBaseLink
    /*
     * Включение обработки ссылок для постраничной навигации 
     */
    ($arCurrentValues["PAGER_BASE_LINK_ENABLE"] ?? '') ==="Y" //$bBaseLinkEnabled
);

CIBlockParameters::Add404Settings($arComponentParameters, $arCurrentValues);
