<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = array(
	'NAME' => GetMessage(''),
	'DESCRIPTION' => GetMessage(''),
	'ICON' => '/images/something.gif',
	'SORT' => 20,
	'CACHE_PATH' => 'Y',
	'PATH' => array(
		'ID' => 'test_task_components',
		'CHILD' => array(
			'ID' => 'cars_list',
			'NAME' => GetMessage(''),
		),
	),
);
