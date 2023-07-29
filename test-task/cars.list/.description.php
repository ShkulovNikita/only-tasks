<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = array(
	'NAME' => GetMessage('T_JOB_CARS_COMPONENT_NAME'),
	'DESCRIPTION' => GetMessage('T_JOB_CARS_COMPONENT_DESC'),
	'ICON' => '/images/something.gif',
	'SORT' => 20,
	'CACHE_PATH' => 'Y',
	'PATH' => array(
		'ID' => 'test_task_components',
        'NAME' => GetMessage('T_JOB_CARS_COMPONENT_TYPE_NAME')
	),
);
