<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) {
    die();
}
/** @var array $arCurrentValues */

/*
 * Компонент работает только при включенных модулях инфоблоков и хайлоад-блоков. 
 */
use Bitrix\Main\Loader;
if (!Loader::includeModule('iblock') || !Loader::includeModule('highloadblock')) {
    return;
}
