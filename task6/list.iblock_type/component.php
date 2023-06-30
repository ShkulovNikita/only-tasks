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

$this->prepareComponent($arParams);

$arrFilter = $this->arrFilter;
$arNavParams = $this->arNavParams;
$arNavigation = $this->arNavigation;
$pagerParameters = $this->pagerParameters;
$bUSER_HAVE_ACCESS = $this->bUSER_HAVE_ACCESS;

/*
 * Если нет валидного кеша, то создать, либо использовать существующий кеш для заполнения $arParams. 
 * @param bool Время кеширования.
 * @param array От чего дополнительно зависит кеш.
 */
if ($this->startResultCache(
    false, 
    array(($arParams["CACHE_GROUPS"]==="N"? false: $USER->GetGroups()), 
    $bUSER_HAVE_ACCESS, 
    $arNavigation, 
    $arrFilter, 
    $pagerParameters))
) {
    $resultOfMakingArray = $this->makeArResult($arParams);
    /*
     * Если было возвращено false, то необходимо остановить обработку компонента. 
     */
    if ($resultOfMakingArray === false) {
        return;
    }

    $arResult = $this->arResult;

    /**
     * -----------
     * --УДАЛИТЬ--
     * -----------
     */
    $arSelect = $this->arSelect;
    $bGetProperty = $this->bGetProperty;
    $arFilter = $this->arFilter;
    $arSort = $this->arSort;
    $shortSelect = $this->shortSelect;
    $listPageUrl = $this->listPageUrl;

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
