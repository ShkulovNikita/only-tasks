<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/local/parser/parser.php");

if (!$USER->IsAdmin()) {
    LocalRedirect('/');
}

$parser = new Parser(4);
$filePath = $_SERVER['DOCUMENT_ROOT'] . "/bitrix/local/parser/vacancy.csv";
$parser->parseCsv($filePath);
