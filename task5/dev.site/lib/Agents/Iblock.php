<?php

namespace Only\Site\Agents;


class Iblock
{
    public static function clearOldLogs()
    {
        $logIblockID = 4;
        $logIblockCode = 'LOG';
        
        \CModule::IncludeModule('iblock');
        
        /*
         * Получить все логи, отсортированные по дате изменения.
         */
        $logsRes = \CIBlockElement::GetList(
            ['TIMESTAMP_X' => 'DESC'],
            ['CODE' => $logIblockCode, 'IBLOCK_ID' => $logIblockID],
            false,
            false,
            ['ID', 'TIMESTAMP_X']
        );
        /*
         * Если логов меньше либо равно 10, то удалять ничего не нужно.
         */
        $arLogs = [];
        while ($arLog = $logsRes->Fetch()) {
            $arLogs[] = $arLog;
        }
        if (count($arLogs) <= 10) {
            return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
        }
        /*
         * Убрать из массива первые 10 элементов.
         */
        array_splice($arLogs, 0, 10);
        /*
         * Удалить оставшиеся в массиве логи.
         */
        foreach ($arLogs as $log) {
            \CIBlockElement::Delete($log['ID']);
        }
    
        return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
    }

    private static function logToFile($text)
    {
        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/bitrix/local/logs/log.txt";
        file_put_contents(
            $filePath,
            $text,
            FILE_APPEND
        );
    }

    public static function example()
    {
        global $DB;
        if (\Bitrix\Main\Loader::includeModule('iblock')) {
            $iblockId = \Only\Site\Helpers\IBlock::getIblockID('QUARRIES_SEARCH', 'SYSTEM');
            $format = $DB->DateFormatToPHP(\CLang::GetDateFormat('SHORT'));
            $rsLogs = \CIBlockElement::GetList(['TIMESTAMP_X' => 'ASC'], [
                'IBLOCK_ID' => $iblockId,
                '<TIMESTAMP_X' => date($format, strtotime('-1 months')),
            ], false, false, ['ID', 'IBLOCK_ID']);
            while ($arLog = $rsLogs->Fetch()) {
                \CIBlockElement::Delete($arLog['ID']);
            }
        }
        return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
    }
}
