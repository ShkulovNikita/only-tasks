<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

/** @global CIntranetToolbar $INTRANET_TOOLBAR */
global $INTRANET_TOOLBAR;

use Bitrix\Main\Context,
    Bitrix\Main\Type\DateTime,
    Bitrix\Main\Loader,
    Bitrix\Iblock;
/*
 * Запрет сохранения в сессии номера последней страницы 
 * при постраничной навигации. 
 */
CPageOption::SetOptionString("main", "nav_page_in_session", "N");
/*
 * Если не задано время кеширования, то установить значение по умолчанию. 
 */
if (!isset($arParams["CACHE_TIME"])) {
    $arParams["CACHE_TIME"] = 36000000;
}
/*
 * Если не задан тип инфоблока, то установить тип по умолчанию (news). 
 */
$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"] ?? '');
if (empty($arParams["IBLOCK_TYPE"])) {
    $arParams["IBLOCK_TYPE"] = "news";
}
/*
 * Также задание значений по умолчанию для идентификатора инфоблока,
 * ID и кода раздела, включения элементов подраздела и установки в заголовках
 * ответа времени модификации страницы. 
 */
$arParams["IBLOCK_ID"] = trim($arParams["IBLOCK_ID"] ?? '');
$arParams["PARENT_SECTION"] = (int)($arParams["PARENT_SECTION"] ?? 0);
$arParams["PARENT_SECTION_CODE"] ??= '';
$arParams["INCLUDE_SUBSECTIONS"] = ($arParams["INCLUDE_SUBSECTIONS"] ?? '') !== "N";
$arParams["SET_LAST_MODIFIED"] = ($arParams["SET_LAST_MODIFIED"] ?? '') === "Y";
/*
 * По умолчанию первое поле для сортировки - дата начала активности. 
 */
$arParams["SORT_BY1"] = trim($arParams["SORT_BY1"] ?? '');
if (empty($arParams["SORT_BY1"])) {
    $arParams["SORT_BY1"] = "ACTIVE_FROM";
}
/*
 * Направление сортировки по умолчанию - по убыванию. 
 */
if (
    !isset($arParams["SORT_ORDER1"])
    || !preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams["SORT_ORDER1"])
) {
    $arParams["SORT_ORDER1"]="DESC";
}
/*
 * Если поле второй сортировки не задано, и первая сортировка - по полю "сортировка",
 * то второе поле сортировки - идентификатор, иначе "сортировка". 
 */
$arParams["SORT_BY2"] = trim($arParams["SORT_BY2"] ?? '');
if (empty($arParams["SORT_BY2"])) {
    if (mb_strtoupper($arParams["SORT_BY1"]) === 'SORT') {
        $arParams["SORT_BY2"] = "ID";
        $arParams["SORT_ORDER2"] = "DESC";
    } else {
        $arParams["SORT_BY2"] = "SORT";
    }
}
/*
 * Направление второй сортировки по умолчанию - по возрастанию. 
 */
if (
    !isset($arParams["SORT_ORDER2"])
    || !preg_match('/^(asc|desc|nulls)(,asc|,desc|,nulls){0,1}$/i', $arParams["SORT_ORDER2"])
) {
    $arParams["SORT_ORDER2"]="ASC";
}

$arrFilter = [];
if (!empty($arParams["FILTER_NAME"]) && preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["FILTER_NAME"])) {
    $arrFilter = $GLOBALS[$arParams["FILTER_NAME"]] ?? [];
    if (!is_array($arrFilter)) {
        $arrFilter = [];
    }
}

$arParams["CHECK_DATES"] = ($arParams["CHECK_DATES"] ?? '') !== "N";

if (empty($arParams["FIELD_CODE"]) || !is_array($arParams["FIELD_CODE"])) {
    $arParams["FIELD_CODE"] = [];
}

foreach ($arParams["FIELD_CODE"] as $key=>$val) {
    if (!$val) {
        unset($arParams["FIELD_CODE"][$key]);
    }
}

if (empty($arParams["PROPERTY_CODE"]) || !is_array($arParams["PROPERTY_CODE"])) {
    $arParams["PROPERTY_CODE"] = array();
}
foreach ($arParams["PROPERTY_CODE"] as $key=>$val) {
    if ($val==="") {
        unset($arParams["PROPERTY_CODE"][$key]);
    }
}

$arParams["DETAIL_URL"] = trim($arParams["DETAIL_URL"] ?? '');
$arParams["SECTION_URL"] = trim($arParams["SECTION_URL"] ?? '');
$arParams["IBLOCK_URL"] = trim($arParams["IBLOCK_URL"] ?? '');

$arParams["NEWS_COUNT"] = (int)($arParams["NEWS_COUNT"] ?? 0);
if ($arParams["NEWS_COUNT"] <= 0) {
    $arParams["NEWS_COUNT"] = 20;
}

$arParams["CACHE_FILTER"] = ($arParams["CACHE_FILTER"] ?? '') === "Y";
if (!$arParams["CACHE_FILTER"] && !empty($arrFilter)) {
    $arParams["CACHE_TIME"] = 0;
}

$arParams["SET_TITLE"] = ($arParams["SET_TITLE"] ?? '') !== "N";
$arParams["SET_BROWSER_TITLE"] = ($arParams["SET_BROWSER_TITLE"] ?? '') === 'N' ? 'N' : 'Y';
$arParams["SET_META_KEYWORDS"] = ($arParams["SET_META_KEYWORDS"] ?? '') === 'N' ? 'N' : 'Y';
$arParams["SET_META_DESCRIPTION"] = ($arParams["SET_META_DESCRIPTION"] ?? '') === 'N' ? 'N' : 'Y';
$arParams["ADD_SECTIONS_CHAIN"] = ($arParams["ADD_SECTIONS_CHAIN"] ?? '') !== "N"; //Turn on by default
$arParams["INCLUDE_IBLOCK_INTO_CHAIN"] = ($arParams["INCLUDE_IBLOCK_INTO_CHAIN"] ?? '') !== "N";
$arParams["STRICT_SECTION_CHECK"] = ($arParams["STRICT_SECTION_CHECK"] ?? '') === "Y";
$arParams["ACTIVE_DATE_FORMAT"] = trim($arParams["ACTIVE_DATE_FORMAT"] ?? '');
if (empty($arParams["ACTIVE_DATE_FORMAT"])) {
    $arParams["ACTIVE_DATE_FORMAT"] = $DB->DateFormatToPHP(\CSite::GetDateFormat("SHORT"));
}
$arParams["PREVIEW_TRUNCATE_LEN"] = (int)($arParams["PREVIEW_TRUNCATE_LEN"] ?? 0);
$arParams["HIDE_LINK_WHEN_NO_DETAIL"] = ($arParams["HIDE_LINK_WHEN_NO_DETAIL"] ?? '') === "Y";

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
/*
 * Отображение постраничной навигации. 
 */
if ($arParams["DISPLAY_TOP_PAGER"] || $arParams["DISPLAY_BOTTOM_PAGER"]) {
    $arNavParams = array(
        "nPageSize" => $arParams["NEWS_COUNT"],
        "bDescPageNumbering" => $arParams["PAGER_DESC_NUMBERING"],
        "bShowAll" => $arParams["PAGER_SHOW_ALL"],
    );
    $arNavigation = CDBResult::GetNavParams($arNavParams);
    if ((int)$arNavigation["PAGEN"] === 0 && $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"] > 0) {
        $arParams["CACHE_TIME"] = $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"];
    }
} else {
    $arNavParams = array(
        "nTopCount" => $arParams["NEWS_COUNT"],
        "bDescPageNumbering" => $arParams["PAGER_DESC_NUMBERING"],
    );
    $arNavigation = false;
}

$pagerParameters = [];
if (!empty($arParams["PAGER_PARAMS_NAME"]) && preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["PAGER_PARAMS_NAME"])) {
    $pagerParameters = $GLOBALS[$arParams["PAGER_PARAMS_NAME"]] ?? [];
    if (!is_array($pagerParameters)) {
        $pagerParameters = array();
    }
}

$arParams["USE_PERMISSIONS"] = ($arParams["USE_PERMISSIONS"] ?? '') === "Y";
if (!is_array($arParams["GROUP_PERMISSIONS"] ?? null)) {
    $adminGroupCode = 1;
    $arParams["GROUP_PERMISSIONS"] = [$adminGroupCode];
}
/*
 * Проверка прав доступа текущего пользователя к компоненту. 
 */
$bUSER_HAVE_ACCESS = !$arParams["USE_PERMISSIONS"];
if ($arParams["USE_PERMISSIONS"] && isset($GLOBALS["USER"]) && is_object($GLOBALS["USER"])) {
    $arUserGroupArray = $USER->GetUserGroupArray();
    foreach ($arParams["GROUP_PERMISSIONS"] as $PERM) {
        if (in_array($PERM, $arUserGroupArray)) {
            $bUSER_HAVE_ACCESS = true;
            break;
        }
    }
}

$arParams["CACHE_GROUPS"] ??= '';

/*
 * Если нет валидного кеша, то создать, либо использовать существующий кеш для заполнения $arParams. 
 * @param bool Время кеширования.
 * @param array От чего дополнительно зависит кеш.
 */
if($this->startResultCache(false, array(($arParams["CACHE_GROUPS"]==="N"? false: $USER->GetGroups()), $bUSER_HAVE_ACCESS, $arNavigation, $arrFilter, $pagerParameters))) {
    /*
     * Модуль инфоблоков должен быть установлен для работы с компонентом. 
     */
    if(!Loader::includeModule("iblock")) {
        $this->abortResultCache();
        ShowError(GetMessage("IBLOCK_TYPE_IBLOCK_MODULE_NOT_INSTALLED"));
        return;
    }
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
    $arResult = $rsIBlock->GetNext();
    /*
     * Если инфоблок не был найден, то вывести соответствующую ошибку.
     */
    if (!$arResult) {
        $this->abortResultCache();
        Iblock\Component\Tools::process404(
            trim($arParams["MESSAGE_404"]) ?: GetMessage("T_IBLOCK_TYPE_LIST_NA")
            ,true
            ,$arParams["SET_STATUS_404"] === "Y"
            ,$arParams["SHOW_404"] === "Y"
            ,$arParams["FILE_404"]
        );
        return;
    }

    /*
     * Имеет ли пользователь право доступа для работы с компонентом. 
     */
    $arResult["USER_HAVE_ACCESS"] = $bUSER_HAVE_ACCESS;

    /*
     * Сохранить в $arSelect все поля инфоблока. 
     */
    //SELECT
    $arSelect = array_merge($arParams["FIELD_CODE"], array(
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
    /*
     * $bGetProperty - массив свойств. 
     */
    $bGetProperty = !empty($arParams["PROPERTY_CODE"]);
    /*
     * Фильтр - это массив, включающий в себя идентификатор инфоблока, идентификатор сайта,
     * активность и права доступа. 
     */
    //WHERE
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
    /*
     * Получить ID родительского раздела по его идентификатору, коду и идентификатору инфоблока. 
     */
    $PARENT_SECTION = CIBlockFindTools::GetSectionID(
        $arParams["PARENT_SECTION"],
        $arParams["PARENT_SECTION_CODE"],
        array(
            "GLOBAL_ACTIVE" => "Y",
            "IBLOCK_ID" => $arResult["ID"],
        )
    );
    /*
     * Если задана строгая проверка раздела, и задан ID раздела или его код,
     * то проверить, был ли получен ID родительского раздела (если нет - вывести ошибку).
     */
    if (
        $arParams["STRICT_SECTION_CHECK"]
        && (
            $arParams["PARENT_SECTION"] > 0
            || $arParams["PARENT_SECTION_CODE"] <> ''
        )
    ) {
        if ($PARENT_SECTION <= 0) {
            $this->abortResultCache();
            Iblock\Component\Tools::process404(
                trim($arParams["MESSAGE_404"]) ?: GetMessage("T_IBLOCK_TYPE_LIST_NA"),
                true,
                $arParams["SET_STATUS_404"] === "Y",
                $arParams["SHOW_404"] === "Y",
                $arParams["FILE_404"]
            );
            return;
        }
    }

    $arParams["PARENT_SECTION"] = $PARENT_SECTION;
    /*
     * Был найден родительский раздел. 
     */
    if ($arParams["PARENT_SECTION"] > 0) {
        /*
         * Сохранить раздел в фильтре. 
         */
        $arFilter["SECTION_ID"] = $arParams["PARENT_SECTION"];
        /*
         * Указать в фильтре, показывать ли элементы подразделов указанного раздела. 
         */
        if ($arParams["INCLUDE_SUBSECTIONS"]) {
            $arFilter["INCLUDE_SUBSECTIONS"] = "Y";
        }
        /*
         * Сформировать навигационную цепочку. 
         */
        $arResult["SECTION"] = array("PATH" => array());
        $rsPath = CIBlockSection::GetNavChain(
            $arResult["ID"],
            $arParams["PARENT_SECTION"],
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
        $rsPath->SetUrlTemplates("", $arParams["SECTION_URL"], $arParams["IBLOCK_URL"]);
        /*
         * Запись пути в $arResult. 
         */
        while ($arPath = $rsPath->GetNext()) {
            $ipropValues = new Iblock\InheritedProperty\SectionValues($arParams["IBLOCK_ID"], $arPath["ID"]);
            $arPath["IPROPERTY_VALUES"] = $ipropValues->getValues();
            $arResult["SECTION"]["PATH"][] = $arPath;
        }
        unset($arPath, $rsPath);
        /*
         * Запись значений вычисляемых наследуемых свойств раздела. 
         */
        $ipropValues = new Iblock\InheritedProperty\SectionValues($arResult["ID"], $arParams["PARENT_SECTION"]);
        $arResult["IPROPERTY_VALUES"] = $ipropValues->getValues();
    } else {
        $arResult["SECTION"]= false;
    }

    ShowError("Фильтр: <br>");
    ShowError(var_dump($arFilter));
    ShowError("<br> Result: <br>");
    ShowError(var_dump($arResult));
    ShowError("<br><br>");

    /*
     * Запись сортировки в виде $arParams[Поле сортировки] => Порядок сортировки. 
     */
    //ORDER BY
    $arSort = array(
        $arParams["SORT_BY1"] => $arParams["SORT_ORDER1"],
        $arParams["SORT_BY2"] => $arParams["SORT_ORDER2"],
    );
    if (!array_key_exists("ID", $arSort)) {
        $arSort["ID"] = "DESC";
    }
    /*
     * Сохранить в $shortSelect поля, используемые для сортировки.
     * Используется как список получаемых полей в CIBlockElement:GetList.
     */
    $shortSelect = array('ID', 'IBLOCK_ID');
    foreach (array_keys($arSort) as $index) {
        if (!in_array($index, $shortSelect)) {
            $shortSelect[] = $index;
        }
    }
    
    $listPageUrl = '';
    /*
     * Список выводимых элементов инфоблоков. 
     */
    $arResult["ITEMS"] = array();
    /*
     * Идентификаторы элементов из $arResult["ITEMS"]. 
     */
    $arResult["ELEMENTS"] = array();
    /*
     * Получить список элементов с заполненными ранее сортировкой, фильтрами, 
     * параметрами постраничной навигации, списком возвращаемых полей.
     */
    $rsElement = CIBlockElement::GetList($arSort, array_merge($arFilter , $arrFilter), false, $arNavParams, $shortSelect);
    while ($row = $rsElement->Fetch()) {
        $id = (int)$row['ID'];
        $arResult["ITEMS"][$id] = $row;
        $arResult["ELEMENTS"][] = $id;
    }
    unset($row);
    /*
     * Если в результате запроса были получены инфоблоки. 
     */
    if (!empty($arResult['ITEMS'])) {
        /*
         * $elementFilter содержит идентификаторы инфоблока, сайта и полученных элементов. 
         */
        $elementFilter = array(
            "IBLOCK_ID" => $arResult["ID"],
            "IBLOCK_LID" => SITE_ID,
            "ID" => $arResult["ELEMENTS"]
        );
        /*
         * Отображение ещё не опубликованных инфоблоков. 
         */
        if (isset($arrFilter['SHOW_NEW'])) {
            $elementFilter['SHOW_NEW'] = $arrFilter['SHOW_NEW'];
        }
        
        $obParser = new CTextParser;
        /*
         * Получить список элементов в соответствии с фильтром выше. 
         */
        $iterator = CIBlockElement::GetList(array(), $elementFilter, false, false, $arSelect);
        /*
         * Применить шаблоны путей.
         */
        $iterator->SetUrlTemplates($arParams["DETAIL_URL"], '', ($arParams["IBLOCK_URL"] ?? ''));
        while ($arItem = $iterator->GetNext()) {
            /*
             * Набор кнопок для управления текущим элементом инфоблока. 
             */
            $arButtons = CIBlock::GetPanelButtons(
                $arItem["IBLOCK_ID"], // Идентификатор инфоблока.
                $arItem["ID"], // Идентификатор элемента.
                0, // Идентификатор раздела.
                array("SECTION_BUTTONS" => false, "SESSID" => false)
            );
            /*
             * Задать ссылки на редактирование и удаление элемента. 
             */
            $arItem["EDIT_LINK"] = $arButtons["edit"]["edit_element"]["ACTION_URL"] ?? '';
            $arItem["DELETE_LINK"] = $arButtons["edit"]["delete_element"]["ACTION_URL"] ?? '';
            /*
             * Обрезка текста анонса, если требуется. 
             */
            if ($arParams["PREVIEW_TRUNCATE_LEN"] > 0) {
                $arItem["PREVIEW_TEXT"] = $obParser->html_cut($arItem["PREVIEW_TEXT"], $arParams["PREVIEW_TRUNCATE_LEN"]);
            }
            /*
             * Отображение даты начала активности. 
             */
            if ($arItem["ACTIVE_FROM"] <> '') {
                $arItem["DISPLAY_ACTIVE_FROM"] = CIBlockFormatProperties::DateFormat($arParams["ACTIVE_DATE_FORMAT"], MakeTimeStamp($arItem["ACTIVE_FROM"], CSite::GetDateFormat()));
            } else {
                $arItem["DISPLAY_ACTIVE_FROM"] = "";
            }

            /*
             * Метка для элемента для запроса из БД. 
             */
            Iblock\InheritedProperty\ElementValues::queue($arItem["IBLOCK_ID"], $arItem["ID"]);

            $arItem["FIELDS"] = array();
            /*
             * Если есть массив свойств, то добавить такой массив для $arItem.
             */
            if ($bGetProperty) {
                $arItem["PROPERTIES"] = array();
            }
            $arItem["DISPLAY_PROPERTIES"] = array();

            /*
             * Учитывать время последней модификации. 
             */
            if ($arParams["SET_LAST_MODIFIED"]) {
                $time = DateTime::createFromUserTime($arItem["TIMESTAMP_X"]);
                /*
                 * 
                 */
                if (
                    !isset($arResult["ITEMS_TIMESTAMP_X"])
                    || $time->getTimestamp() > $arResult["ITEMS_TIMESTAMP_X"]->getTimestamp()
                ) {
                    $arResult["ITEMS_TIMESTAMP_X"] = $time;
                }
            }

            /*
             * Ссылка на страницу со списком элементов. 
             */
            if ($listPageUrl === '' && isset($arItem['~LIST_PAGE_URL'])) {
                $listPageUrl = $arItem['~LIST_PAGE_URL'];
            }

            /*
             * Внести полученный элемент $arItem в подмассив ITEMS. 
             */
            $id = (int)$arItem["ID"];
            $arResult["ITEMS"][$id] = $arItem;
        }
        unset($obElement);
        unset($iterator);

        /*
         * Если есть массив свойств, то записать свойства в $arResult[ITEMS].
         */
        if ($bGetProperty) {
            unset($elementFilter['IBLOCK_LID']);
            CIBlockElement::GetPropertyValuesArray(
                $arResult["ITEMS"],
                $arResult["ID"],
                $elementFilter
            );
        }
    }

    /*
     * Перебрать полученный массив элементов. 
     */
    $arResult['ITEMS'] = array_values($arResult['ITEMS']);
    foreach ($arResult["ITEMS"] as &$arItem) {
        /*
         * Заполнить значения свойств для отображения. 
         */
        if ($bGetProperty) {
            foreach ($arParams["PROPERTY_CODE"] as $pid) {
                $prop = &$arItem["PROPERTIES"][$pid];
                if (
                    (is_array($prop["VALUE"]) && count($prop["VALUE"]) > 0)
                    || (!is_array($prop["VALUE"]) && $prop["VALUE"] <> '')
                ) {
                    $arItem["DISPLAY_PROPERTIES"][$pid] = CIBlockFormatProperties::GetDisplayValue($arItem, $prop);
                }
            }
        }

        $ipropValues = new Iblock\InheritedProperty\ElementValues($arItem["IBLOCK_ID"], $arItem["ID"]);
        $arItem["IPROPERTY_VALUES"] = $ipropValues->getValues();
        Iblock\Component\Tools::getFieldImageData(
            $arItem,
            array('PREVIEW_PICTURE', 'DETAIL_PICTURE'),
            Iblock\Component\Tools::IPROPERTY_ENTITY_ELEMENT,
            'IPROPERTY_VALUES'
        );

        foreach($arParams["FIELD_CODE"] as $code) {
            if (array_key_exists($code, $arItem)) {
                $arItem["FIELDS"][$code] = $arItem[$code];
            }
        }
    }
    unset($arItem);
    if ($bGetProperty) {
        \CIBlockFormatProperties::clearCache();
    }

    /*
     * Включена обработка ссылок для постраничной навигации. 
     */
    $navComponentParameters = array();
    if ($arParams["PAGER_BASE_LINK_ENABLE"] === "Y") {
        /*
         * Адрес для построения ссылок. 
         */
        $pagerBaseLink = trim($arParams["PAGER_BASE_LINK"]);
        /*
         * Если адрес не задан, то построить его. 
         */
        if ($pagerBaseLink === "") {
            if (
                $arResult["SECTION"]
                && $arResult["SECTION"]["PATH"]
                && $arResult["SECTION"]["PATH"][0]
                && $arResult["SECTION"]["PATH"][0]["~SECTION_PAGE_URL"]
            ) {
                $pagerBaseLink = $arResult["SECTION"]["PATH"][0]["~SECTION_PAGE_URL"];
            } elseif (
                $listPageUrl !== ''
            ) {
                $pagerBaseLink = $listPageUrl;
            }
        }

        if ($pagerParameters && isset($pagerParameters["BASE_LINK"])) {
            $pagerBaseLink = $pagerParameters["BASE_LINK"];
            unset($pagerParameters["BASE_LINK"]);
        }

        /*
         * Задать параметры для постраничной навигации. 
         */
        $navComponentParameters["BASE_LINK"] = CHTTP::urlAddParams($pagerBaseLink, $pagerParameters, array("encode"=>true));
    }

    /*
     * Получить панель постраничной навигации. 
     */
    $arResult["NAV_STRING"] = $rsElement->GetPageNavStringEx(
        $navComponentObject,
        $arParams["PAGER_TITLE"],
        $arParams["PAGER_TEMPLATE"],
        $arParams["PAGER_SHOW_ALWAYS"],
        $this,
        $navComponentParameters
    );
    $arResult["NAV_CACHED_DATA"] = null;
    $arResult["NAV_RESULT"] = $rsElement;
    $arResult["NAV_PARAM"] = $navComponentParameters;

    /*
     * Указание, какие ключи массива $arResult должны кешироваться. 
     */
    $this->setResultCacheKeys(array(
        "ID",
        "IBLOCK_TYPE_ID",
        "LIST_PAGE_URL",
        "NAV_CACHED_DATA",
        "NAME",
        "SECTION",
        "ELEMENTS",
        "IPROPERTY_VALUES",
        "ITEMS_TIMESTAMP_X",
    ));

    ShowError("arSelect: <br>");
    ShowError(var_dump($arSelect));
    ShowError("<br> bGetProperty: <br>");
    ShowError(var_dump($bGetProperty));
    ShowError("<br> arSort: <br>");
    ShowError(var_dump($arSort));
    ShowError("<br> Фильтр: <br>");
    ShowError(var_dump($arFilter));
    ShowError("<br> Result: <br>");
    ShowError(var_dump($arResult));
    ShowError("<br> shortSelect: <br>");
    ShowError(var_dump($shortSelect));
    ShowError("<br> listPageUrl: <br>");
    ShowError(var_dump($listPageUrl));
    ShowError("<br><br>");

    /*
     * Подключение шаблона компонента. 
     */
    $this->includeComponentTemplate();
}

/*
 * Если задан идентификатор инфоблока. 
 */
if (isset($arResult["ID"])) {
    $arTitleOptions = null;
    /*
     * Пользователь авторизован. 
     */
    if ($USER->IsAuthorized()) {
        /*
         * Если включен режим отображения включаемых областей, 
         * либо нужен тулбар, либо требуется установить заголовок страницы.
         */
        if (
            $APPLICATION->GetShowIncludeAreas()
            || (is_object($GLOBALS["INTRANET_TOOLBAR"]) && $arParams["INTRANET_TOOLBAR"]!=="N")
            || $arParams["SET_TITLE"]
        ) {
            if (Loader::includeModule("iblock")) {
                /*
                 * Получить набор кнопок для управления выбранным инфоблоком. 
                 */
                $arButtons = CIBlock::GetPanelButtons(
                    $arResult["ID"], // ID выбранного инфоблока.
                    0, // ID элемента
                    $arParams["PARENT_SECTION"], // ID раздела
                    array("SECTION_BUTTONS"=>false)
                );
                /*
                 * Отобразить кнопки для включаемых областей.
                 */
                if ($APPLICATION->GetShowIncludeAreas()) {
                    $this->addIncludeAreaIcons(CIBlock::GetComponentMenu($APPLICATION->GetPublicShowMode(), $arButtons));
                }
                /*
                 * Отображение тулбара. 
                 */
                if (
                    is_array($arButtons["intranet"])
                    && is_object($INTRANET_TOOLBAR)
                    && $arParams["INTRANET_TOOLBAR"] !== "N"
                ) {
                    $APPLICATION->AddHeadScript('/bitrix/js/main/utils.js');
                    foreach ($arButtons["intranet"] as $arButton) {
                        $INTRANET_TOOLBAR->AddButton($arButton);
                    }
                }
                /*
                 * Если требуется задать заголовок страницы. 
                 */
                if ($arParams["SET_TITLE"]) {
                    if (isset($arButtons["submenu"]["edit_iblock"])) {
                        /* 
                         * Сохранить в $arTitleOptions ссылку на редактирование инфоблока в админке.
                         */
                        $arTitleOptions = [
                            'ADMIN_EDIT_LINK' => $arButtons["submenu"]["edit_iblock"]["ACTION"],
                            'PUBLIC_EDIT_LINK' => "",
                            'COMPONENT_NAME' => $this->getName(),
                        ];
                    }
                }
            }
        }
    }

    /*
     * Возвращение шаблону кешированных данных. 
     */
    $this->setTemplateCachedData($arResult["NAV_CACHED_DATA"]);

    $ipropertyExists = (!empty($arResult["IPROPERTY_VALUES"]) && is_array($arResult["IPROPERTY_VALUES"]));
    $iproperty = ($ipropertyExists ? $arResult["IPROPERTY_VALUES"] : array());

    /*
     * Если нужно задать заголовок страницы. 
     */
    if ($arParams["SET_TITLE"]) {
        if ($ipropertyExists && $iproperty["SECTION_PAGE_TITLE"] != "") {
            $APPLICATION->SetTitle($iproperty["SECTION_PAGE_TITLE"], $arTitleOptions);
        } elseif(isset($arResult["NAME"])) {
            $APPLICATION->SetTitle($arResult["NAME"], $arTitleOptions);
        }
    }

    if ($ipropertyExists) {
        if ($arParams["SET_BROWSER_TITLE"] === 'Y' && $iproperty["SECTION_META_TITLE"] != "") {
            $APPLICATION->SetPageProperty("title", $iproperty["SECTION_META_TITLE"], $arTitleOptions);
        }
        if ($arParams["SET_META_KEYWORDS"] === 'Y' && $iproperty["SECTION_META_KEYWORDS"] != "") {
            $APPLICATION->SetPageProperty("keywords", $iproperty["SECTION_META_KEYWORDS"], $arTitleOptions);
        }
        if ($arParams["SET_META_DESCRIPTION"] === 'Y' && $iproperty["SECTION_META_DESCRIPTION"] != "") {
            $APPLICATION->SetPageProperty("description", $iproperty["SECTION_META_DESCRIPTION"], $arTitleOptions);
        }
    }

    /*
     * Если нужно добавить в цепочку навигации имя инфоблока. 
     */
    if ($arParams["INCLUDE_IBLOCK_INTO_CHAIN"] && isset($arResult["NAME"])) {
        if ($arParams["ADD_SECTIONS_CHAIN"] && is_array($arResult["SECTION"])) {
            $APPLICATION->AddChainItem(
                $arResult["NAME"]
                ,$arParams["IBLOCK_URL"] <> ''? $arParams["IBLOCK_URL"]: $arResult["LIST_PAGE_URL"]
            );
        } else {
            $APPLICATION->AddChainItem($arResult["NAME"]);
        }
    }
    /*
     * Добавление имен разделов в цепочку навигации.
     */
    if ($arParams["ADD_SECTIONS_CHAIN"] && is_array($arResult["SECTION"])) {
        foreach($arResult["SECTION"]["PATH"] as $arPath) {
            if ($arPath["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"] != "") {
                $APPLICATION->AddChainItem($arPath["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"], $arPath["~SECTION_PAGE_URL"]);
            }
            else {
                $APPLICATION->AddChainItem($arPath["NAME"], $arPath["~SECTION_PAGE_URL"]);
            }
        }
    }

    if ($arParams["SET_LAST_MODIFIED"] && $arResult["ITEMS_TIMESTAMP_X"]) {
        Context::getCurrent()->getResponse()->setLastModified($arResult["ITEMS_TIMESTAMP_X"]);
    }

    unset($iproperty);
    unset($ipropertyExists);

    return $arResult["ELEMENTS"];
}
