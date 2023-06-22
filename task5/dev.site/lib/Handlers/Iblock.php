<?php

namespace Only\Site\Handlers;


class Iblock
{
    private static $LOG_BLOCK_ID = 5;

    public static function addLog($arFields)
    {
        \CModule::IncludeModule('iblock');
        /*
         * Срабатывания на лог-элементы (код "LOG" либо пустые код и имя) 
         * должны игнорироваться.
         */
        $LOG_CODE = 'LOG';
        if (
            (($arFields['CODE'] === null) 
            && ($arFields['NAME'] === null)) 
            || (stripos($arFields['CODE'], $LOG_CODE) !== false)
        ) {
            return;
        }
        /*
         * Получить разделы логируемого элемента инфоблока. 
         */
        $elPath = self::getPath(
            $arFields['NAME'], 
            $arFields['IBLOCK_ID'], 
            $arFields['IBLOCK_SECTION']
        );
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
                    'ACTIVE' => 'Y',
                    'PREVIEW_TEXT' => $elPath
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
                    'ACTIVE_FROM' => date('d.m.Y'),
                    'PREVIEW_TEXT' => $elPath
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
            [],
            [
                'NAME' => $elName,
                'CODE' => $elCode
            ],
            false,
            ['ID']
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
            [],
            [
                'NAME' => $elID,
                'CODE' => $elCode,
                'SECTION_ID' => $sectionID
            ],
            false,
            false,
            ['ID']
        );

        while ($arEl = $arElements->GetNext()) {
            return $arEl['ID'];
        }

        return false;
    }

    /**
     * Получение пути до элемента инфоблока в формате
     * "Имя инфоблока -> Имя раздела(от родителя к ребенку)... -> Имя элемента".
     * @param string $elName Имя элемента инфоблока.
     * @param int $iblockID Идентификатор инфоблока элемента.
     * @param int $lowerSectionID Идентификатор непосредственного раздела элемента.
     * @return string Путь до элемента инфоблока в указанном формате.
     */
    private static function getPath($elName, $iblockID, $lowerSectionID)
    {
        /*
         * Добавить в конец пути имя элемента инфоблока. 
         */
        $path = $elName;
        /*
         * Если у элемента есть родительский раздел, то найти его родительские разделы. 
         */
        if (isset($lowerSectionID)) {
            self::getSections($path, $lowerSectionID[0]);
        }
        /*
         * Получить имя инфоблока.
         */
        $iblockName = self::getIblockName($iblockID);
        /*
         * Добавить в начало название инфоблока. 
         */
        if ($iblockName !== false) {
            $path = $iblockName . ' -> ' . $path;
        }

        return $path;
    }

    /**
     * Рекурсивная функция поиска родительских разделов элемента инфоблока.
     * @param string $path Путь с родительскими разделами инфоблока.
     * @param int $sectionID Идентификатор очередного родительского раздела.
     */
    private static function getSections(&$path, $sectionID)
    {
        /*
         * Получить данные о текущем разделе: имя и старший раздел.
         */
        $arSection = \CIBlockSection::GetList(
            [],
            ['ID' => $sectionID],
            false,
            ['ID', 'IBLOCK_SECTION_ID', 'NAME']
        );
        $sectionInfo = $arSection->getNext();
        /*
         * Добавить имя раздела к пути /
         */
        $path = $sectionInfo['NAME'] . ' -> ' . $path;
        /*
         * Если есть родительский раздел, то вызвать функцию ещё раз,
         * иначе вернуть полученный путь. 
         */
        if (isset($sectionInfo['IBLOCK_SECTION_ID'])) {
            self::getSections($path, $sectionInfo['IBLOCK_SECTION_ID']);
        } else {
            return;
        }
    }

    /**
     * Получение имени инфоблока, которому принадлежит указанный элемент.
     * @param int $elID Идентификатор элемента инфоблока.
     * @return string|bool Имя информационного блока либо false.
     */
    private static function getIblockName($elID)
    {
        $iblockRes = \CIBlock::GetByID($elID);
        if ($iblock = $iblockRes->GetNext()) {
            return $iblock['NAME'];
        } else {
            return false;
        }
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
