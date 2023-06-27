<?php
if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/autoload.php")) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/autoload.php");
}

$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->AddEventHandler(
    "iblock",
    "OnAfterIBlockElementAdd",
    [
        "Only\\Site\\Handlers\\Iblock",
		'addLog'
    ]
);
$eventManager->AddEventHandler(
    "iblock",
    "OnAfterIBlockElementUpdate",
    [
        "Only\\Site\\Handlers\\Iblock",
        'addLog'
    ]
);
