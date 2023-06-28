<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
	"NAME" => GetMessage("T_IBLOCK_TYPE_DESC_LIST"),
	"DESCRIPTION" => GetMessage("T_IBLOCK_TYPE_DESC_LIST_DESC"),
	"ICON" => "/images/iblock_type_list.gif",
	"SORT" => 20,
	"CACHE_PATH" => "Y",
	"PATH" => array(
		"ID" => "trainee_components",
		"CHILD" => array(
			"ID" => "list_iblock_types",
			"NAME" => GetMessage("T_IBLOCK_TYPE_DESC_IBLOCKS"),
		),
	),
);

?>
