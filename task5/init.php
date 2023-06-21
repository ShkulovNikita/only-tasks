<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/dev.site/lib/Handlers/Iblock.php");

if (CModule::IncludeModule("dev.site")) {
    $eventManager = \Bitrix\Main\EventManager::getInstance();
    /*
     * Подписаться на события добавления и редактирования инфоблока
     */
    $eventManager->registerEventHandlerCompatible(
        "iblock",
        "OnAfterIBlockElementAdd",
        "dev.site", 
        "Only\\Site\\Handlers\\Iblock", 
        "addLog"
    );
    $eventManager->registerEventHandlerCompatible(
        "iblock", 
        "OnAfterIBlockElementUpdate", 
        "dev.site", 
        "Only\\Site\\Handlers\\Iblock", 
        "addLog"
    );
}

function addLog(&$arFields)
{
    echo ''; print_r($arFields); echo ''; die();
}