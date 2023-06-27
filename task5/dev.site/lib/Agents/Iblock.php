<?php

namespace Only\Site\Agents;


class Iblock
{
    public static function clearOldLogs()
    {
        $logIblockCode = 'LOG';
        
        \CModule::IncludeModule('iblock');
        /*
         * Получить все логи, отсортированные по дате изменения.
         */
        $logsRes = \CIBlockElement::GetList(
            ['TIMESTAMP_X' => 'DESC'],
            ['CODE' => $logIblockCode],
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
