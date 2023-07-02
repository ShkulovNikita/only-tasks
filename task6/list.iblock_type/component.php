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

/*
 * Если нет валидного кеша, то создать, либо использовать существующий кеш для заполнения $arParams. 
 */
if (
    $this->startResultCache(
        false, 
        array(
            ($arParams["CACHE_GROUPS"]==="N"? false: $USER->GetGroups()), 
            $this->bUSER_HAVE_ACCESS, 
            $this->arNavigation, 
            $this->arrFilter, 
            $this->pagerParameters
        )
    )
) {
    $resultOfMakingArray = $this->makeArResult($arParams);
    /*
     * Если было возвращено false, то необходимо остановить обработку компонента.
     */
    if ($resultOfMakingArray === false) {
        return;
    }
    /*
     * Подключение шаблона компонента. 
     */
    $this->includeComponentTemplate();
}

/*
 * Если задан идентификатор типа или инфоблока. 
 */
if (isset($this->arResult["ID"])) {
    $isAuthorized = $USER->IsAuthorized();

    return $this->processChosenIblockID($isAuthorized, $arParams);
}
