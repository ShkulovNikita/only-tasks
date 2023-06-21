<?php

namespace Only\Site\Handlers;


class Iblock
{
    static $LOG_BLOCK_ID = 5;

    public static function addLog($arFields)
    {
        \CModule::IncludeModule('iblock');
        /*
         * Изменения в инфоблоках с кодом, содержащим "LOG",
         * должны игнорироваться. 
         */
        $LOG_CODE = 'LOG';
        if (stripos($arFields['CODE'], $LOG_CODE) === false) {
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

            echo '';
            
            //echo $result; echo "\n\n"; print_r($arFields); echo ''; die();
        } else {
            echo 'LOG!'; print_r($arFields); echo ''; die();
        }

        // определить, добавление или редактирование


        // создать либо получить существующий инфоблок LOG
        
        
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
         * Поиск раздела будет осуществляться по его имени и коду 
         */
        $arFilter = Array(
            'NAME' => $elName,
            'CODE' => $elCode
        );

        /*
         * Найти раздел 
         */
        $arSections = \CIBlockSection::GetList(
            Array('SORT'=>'ASC'),
            $arFilter,
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
