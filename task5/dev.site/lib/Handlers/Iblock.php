<?php

namespace Only\Site\Handlers;


class Iblock
{
    private static $LOG_BLOCK_ID = 5;

    public static function addLog($arFields)
    {
        \CModule::IncludeModule('iblock');
        /*
         * Изменения в инфоблоках с кодом, содержащим "LOG",
         * должны игнорироваться. 
         */
        $LOG_CODE = 'LOG';
        if (stripos($arFields['CODE'], $LOG_CODE) !== false) 
            return;
            
        /*
         * Идентификатор раздела для лога
         */
        $sectionID = self::getLogSection($arFields['NAME'], $arFields['CODE'], $LOG_CODE);
        /*
         * Если раздела не существует, то создать его 
         */
        if ($sectionID === false) {
            $newSection = new \CIBlockSection;
            $sectionID = $newSection->Add(
                Array(
                    'NAME' => $arFields['NAME'],
                    'CODE' => $arFields['CODE'],
                    'IBLOCK_ID' => self::$LOG_BLOCK_ID
                )
            );
        }
        /*
         * Попытаться получить в разделе инфоблок лога 
         */
        $logIblockID = self::getLogElement($arFields['ID'], $LOG_CODE, $sectionID);
        /*
         * Если такого инфоблока нет, то создать
         */
        if ($logIblockID === false) {
            $newLog = new \CIBlockElement;
            $logIblockID = $newLog->Add(
                Array(
                    'LID' => 's1',
                    'IBLOCK_ID' => self::$LOG_BLOCK_ID,
                    'IBLOCK_SECTION_ID' => $sectionID,
                    'CODE' => $LOG_CODE,
                    'NAME' => $arFields['ID'],
                    'ACTIVE_FROM' => date('d.m.Y'),
                    'ACTIVE' => 'Y'
                )
            );
        /*
         * Если такой инфоблок уже есть, то обновить 
         */
        } else {
            $log = new \CIBlockElement;
            $result = $log->Update(
                $logIblockID,
                Array(
                    'ACTIVE_FROM' => date('d.m.Y')
                )
            );
        }
    }

    /**
     * Получение раздела для лога.
     * @param string $elName Имя логируемого элемента.
     * @param string $elCode Код логируемого элемента.
     * @return int|bool Идентификатор раздела, если раздел существует, либо false
     */
    private static function getLogSection($elName, $elCode)
    {
        /*
         * Найти раздел 
         */
        $arSections = \CIBlockSection::GetList(
            Array('SORT' => 'ASC'),
            Array(
                'NAME' => $elName,
                'CODE' => $elCode
            ),
            false,
            Array('ID')
        );

        /*
         * Если был получен некоторый раздел, то вернуть его идентификатор 
         */
        while ($arSect = $arSections->GetNext()) {
            return $arSect['ID'];
        } 

        return false;
    }

    /**
     * Получение инфоблока лога для заданного элемента.
     * @param int $elID Идентификатор логируемого элемента.
     * @param string $elCode Символьный код лога.
     * @param int $sectionID Идентификатор раздела инфоблока-лога.
     * @return int|bool Идентификатор инфоблока-лога либо false, если такого инфоблока нет.
     */
    private static function getLogElement($elID, $elCode, $sectionID)
    {
        $arElements = \CIBlockElement::GetList(
            Array('SORT' => 'ASC'),
            Array(
                'NAME' => $elID,
                'CODE' => $elCode,
                'SECTION_ID' => $sectionID
            ),
            false,
            false,
            Array('ID')
        );

        while ($arEl = $arElements->GetNext()) {
            return $arEl['ID'];
        }

        return false;
    }

    function OnBeforeIBlockElementAddHandler(&$arFields)
    {
        $iQuality = 95;
        $iWidth = 1000;
        $iHeight = 1000;
        /*
         * Получаем пользовательские свойства
         */
        $dbIblockProps = \Bitrix\Iblock\PropertyTable::getList(array(
            'select' => array('*'),
            'filter' => array('IBLOCK_ID' => $arFields['IBLOCK_ID'])
        ));
        /*
         * Выбираем только свойства типа ФАЙЛ (F)
         */
        $arUserFields = [];
        while ($arIblockProps = $dbIblockProps->Fetch()) {
            if ($arIblockProps['PROPERTY_TYPE'] == 'F') {
                $arUserFields[] = $arIblockProps['ID'];
            }
        }
        /*
         * Перебираем и масштабируем изображения
         */
        foreach ($arUserFields as $iFieldId) {
            foreach ($arFields['PROPERTY_VALUES'][$iFieldId] as &$file) {
                if (!empty($file['VALUE']['tmp_name'])) {
                    $sTempName = $file['VALUE']['tmp_name'] . '_temp';
                    $res = \CAllFile::ResizeImageFile(
                        $file['VALUE']['tmp_name'],
                        $sTempName,
                        array("width" => $iWidth, "height" => $iHeight),
                        BX_RESIZE_IMAGE_PROPORTIONAL_ALT,
                        false,
                        $iQuality);
                    if ($res) {
                        rename($sTempName, $file['VALUE']['tmp_name']);
                    }
                }
            }
        }

        if ($arFields['CODE'] == 'brochures') {
            $RU_IBLOCK_ID = \Only\Site\Helpers\IBlock::getIblockID('DOCUMENTS', 'CONTENT_RU');
            $EN_IBLOCK_ID = \Only\Site\Helpers\IBlock::getIblockID('DOCUMENTS', 'CONTENT_EN');
            if ($arFields['IBLOCK_ID'] == $RU_IBLOCK_ID || $arFields['IBLOCK_ID'] == $EN_IBLOCK_ID) {
                \CModule::IncludeModule('iblock');
                $arFiles = [];
                foreach ($arFields['PROPERTY_VALUES'] as $id => &$arValues) {
                    $arProp = \CIBlockProperty::GetByID($id, $arFields['IBLOCK_ID'])->Fetch();
                    if ($arProp['PROPERTY_TYPE'] == 'F' && $arProp['CODE'] == 'FILE') {
                        $key_index = 0;
                        while (isset($arValues['n' . $key_index])) {
                            $arFiles[] = $arValues['n' . $key_index++];
                        }
                    } elseif ($arProp['PROPERTY_TYPE'] == 'L' && $arProp['CODE'] == 'OTHER_LANG' && $arValues[0]['VALUE']) {
                        $arValues[0]['VALUE'] = null;
                        if (!empty($arFiles)) {
                            $OTHER_IBLOCK_ID = $RU_IBLOCK_ID == $arFields['IBLOCK_ID'] ? $EN_IBLOCK_ID : $RU_IBLOCK_ID;
                            $arOtherElement = \CIBlockElement::GetList([],
                                [
                                    'IBLOCK_ID' => $OTHER_IBLOCK_ID,
                                    'CODE' => $arFields['CODE']
                                ], false, false, ['ID'])
                                ->Fetch();
                            if ($arOtherElement) {
                                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                                \CIBlockElement::SetPropertyValues($arOtherElement['ID'], $OTHER_IBLOCK_ID, $arFiles, 'FILE');
                            }
                        }
                    } elseif ($arProp['PROPERTY_TYPE'] == 'E') {
                        $elementIds = [];
                        foreach ($arValues as &$arValue) {
                            if ($arValue['VALUE']) {
                                $elementIds[] = $arValue['VALUE'];
                                $arValue['VALUE'] = null;
                            }
                        }
                        if (!empty($arFiles && !empty($elementIds))) {
                            $rsElement = \CIBlockElement::GetList([],
                                [
                                    'IBLOCK_ID' => \Only\Site\Helpers\IBlock::getIblockID('PRODUCTS', 'CATALOG_' . $RU_IBLOCK_ID == $arFields['IBLOCK_ID'] ? '_RU' : '_EN'),
                                    'ID' => $elementIds
                                ], false, false, ['ID', 'IBLOCK_ID', 'NAME']);
                            while ($arElement = $rsElement->Fetch()) {
                                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                                \CIBlockElement::SetPropertyValues($arElement['ID'], $arElement['IBLOCK_ID'], $arFiles, 'FILE');
                            }
                        }
                    }
                }
            }
        }
    }

}
