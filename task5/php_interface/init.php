<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/vendor/autoload.php');

$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->AddEventHandler(
	"iblock",
	"OnAfterIBlockElementUpdate",
	[
		"Only\\Site\\Handlers\\Iblock",
		'addLog'
	]
);
