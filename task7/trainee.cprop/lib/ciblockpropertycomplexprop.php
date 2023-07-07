<?php

use \Bitrix\Main\Localization\Loc;

class CIBlockPropertyComplexProp 
{
    private static $showedCss = false;
    private static $showedJs = false;

    /**
     * Метод-обработчик, добавляющий функционал для работы с комплексным свойством.
     * @return array Массив, описывающий поведение комплексного свойства.
     */
    public static function GetUserTypeDescription()
    {
        return array(
            // Строчный тип свойства.
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => 'C',
            'DESCRIPTION' => Loc::getMessage('IEX_COMPLEX_PROP_DESC'),
            // HTML для редактирования значений свойства.
            'GetPropertyFieldHtml' => array(__CLASS__,  'GetPropertyFieldHtml'),
            // Преобразование в формат для сохранения в БД.
            'ConvertToDB' => array(__CLASS__, 'ConvertToDB'),
        );
    }

    /**
     * Создание HTML для вывода формы редактирования значения свойства.
     * @param array $arProperty Метаданные свойства.
     * @param array $value Значение свойства.
     * @param array $strHTMLControlName Имена элементов управления 
     * для заполнения значений свойств.
     * @return string HTML формы редактирования значения свойства.
     */
    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        /*
         * Свернуть/удалить. 
         */
        $hideText = Loc::getMessage('IEX_COMPLEX_PROP_HIDE_TEXT');
        $clearText = Loc::getMessage('IEX_COMPLEX_PROP_CLEAR_TEXT');
        /*
         * Подключить CSS и JS класса. 
         */
        self::showCss();
        self::showJs();
        /*
         * Подготовить массив с параметрами полей комплексного свойства. 
         */
        if (!empty($arProperty['USER_TYPE_SETTINGS'])) {
            $arFields = self::prepareSetting($arProperty['USER_TYPE_SETTINGS']);
        } else {
            return '<span>'.Loc::getMessage('IEX_COMPLEX_PROP_ERROR_INCORRECT_SETTINGS').'</span>';
        }
        /*
         * HTML-формы редактирования значений.
         */
        $result = '';
        /*
         * Кнопка "свернуть/показать" для отображения значений комплексного свойства. 
         */
        $result .= '<div class="mf-gray"><a class="cl mf-toggle">' . $hideText . '</a>';
        /*
         * Если свойство множественное, то также отображать кнопку "удалить". 
         */
        if($arProperty['MULTIPLE'] === 'Y'){
            $result .= ' | <a class="cl mf-delete">' . $clearText . '</a></div>';
        }
        $result .= '<table class="mf-fields-list active">';
        /*
         * Перебрать все поля свойства. 
         */
        foreach ($arFields as $code => $arItem) {
            if ($arItem['TYPE'] === 'string') {
                $result .= self::showString($code, $arItem['TITLE'], $value, $strHTMLControlName);
            }
            else if ($arItem['TYPE'] === 'file') {
                $result .= self::showFile($code, $arItem['TITLE'], $value, $strHTMLControlName);
            }
            else if ($arItem['TYPE'] === 'text') {
                $result .= self::showTextarea($code, $arItem['TITLE'], $value, $strHTMLControlName);
            }
            else if ($arItem['TYPE'] === 'date') {
                $result .= self::showDate($code, $arItem['TITLE'], $value, $strHTMLControlName);
            }
            else if ($arItem['TYPE'] === 'element') {
                $result .= self::showBindElement($code, $arItem['TITLE'], $value, $strHTMLControlName);
            }
        }

        $result .= '</table>';

        return $result;
    }

    /**
     * Метод для преобразования значения свойства в формат, 
     * пригодный для сохранения в базе данных.
     * @param array $arProperty Метаданные свойства.
     * @param array $arValue Значение свойства.
     * @return array Массив с данными в формате ['VALUE' => 'Значение', 'DESCRIPTION' => 'Описание']
     * для записи в базу данных.
     */
    public function ConvertToDB($arProperty, $arValue)
    {
        /*
         * Получить массив со значениями и типами полей свойства.
         */
        $arFields = self::prepareSetting($arProperty['USER_TYPE_SETTINGS']);
        /*
         * Для полей типа "файл" получить идентификаторы файлов
         * в таблице файлов системы. 
         */
        foreach ($arValue['VALUE'] as $code => $value) {
            if ($arFields[$code]['TYPE'] === 'file') {
                $arValue['VALUE'][$code] = self::prepareFileToDB($value);
            }
        }
        /*
         * Если хотя бы одно поле не пусто, то отметить это в флаге. 
         */
        $isEmpty = true;
        foreach ($arValue['VALUE'] as $v) {
            if (!empty($v)) {
                $isEmpty = false;
                break;
            }
        }
        /*
         * Если есть непустое поле, то сериализовать значения свойства,
         * иначе передать массив с пустыми значениями.
         */
        if ($isEmpty === false) {
            $arResult['VALUE'] = json_encode($arValue['VALUE']);
        } else {
            $arResult = ['VALUE' => '', 'DESCRIPTION' => ''];
        }

        return $arResult;
    }

    /**
     * Сформировать HTML-код для текстового поля свойства.
     * @param string $code Символьный код поля.
     * @param string $title Название поля.
     * @param array $arValue Значения полей свойства.
     * @param string array $strHTMLControlName Имена элементов управления.
     * @return string HTML текстового поля свойства.
     */
    private static function showString($code, $title, $arValue, $strHTMLControlName)
    {
        $result = '';
        /*
         * Получить значение свойства для данного поля по его символьному коду,
         * либо установить пустое значение. 
         */
        $v = !empty($arValue['VALUE'][$code]) ? $arValue['VALUE'][$code] : '';
        $result .= '<tr>
                    <td align="right">'.$title.': </td>
                    <td><input type="text" value="'.$v.'" name="'.$strHTMLControlName['VALUE'].'['.$code.']"/></td>
                </tr>';

        return $result;
    }

    /**
     * Сформировать HTML-код для многострочного текстового поля свойства.
     * @param string $code Символьный код поля.
     * @param string $title Название поля.
     * @param array $arValue Значения полей свойства.
     * @param string array $strHTMLControlName Имена элементов управления.
     * @return string HTML многострочного текстового поля свойства.
     */
    public static function showTextarea($code, $title, $arValue, $strHTMLControlName)
    {
        $result = '';

        $v = !empty($arValue['VALUE'][$code]) ? $arValue['VALUE'][$code] : '';
        $result .= '<tr>
                    <td align="right" valign="top">'.$title.': </td>
                    <td><textarea rows="8" name="'.$strHTMLControlName['VALUE'].'['.$code.']">'.$v.'</textarea></td>
                </tr>';

        return $result;
    }

    /**
     * Сформировать HTML-код для файлового поля свойства.
     * @param string $code Символьный код поля.
     * @param string $title Название поля.
     * @param array $arValue Значения полей свойства.
     * @param string array $strHTMLControlName Имена элементов управления.
     * @return string HTML файлового поля свойства.
     */
    private static function showFile($code, $title, $arValue, $strHTMLControlName)
    {
        $result = '';

        if (!empty($arValue['VALUE'][$code]) && !is_array($arValue['VALUE'][$code])) {
            $fileId = $arValue['VALUE'][$code];
        }
        else if (!empty($arValue['VALUE'][$code]['OLD'])) {
            $fileId = $arValue['VALUE'][$code]['OLD'];
        }
        else {
            $fileId = '';
        }

        if(!empty($fileId))
        {
            /*
             * Получить информацию о файле. 
             */
            $arPicture = CFile::GetByID($fileId)->Fetch();
            /*
             * Если информация о файле была успешно получена. 
             */
            if($arPicture)
            {
                /*
                 * Получить путь для загрузки файлов. 
                 */
                $strImageStorePath = COption::GetOptionString('main', 'upload_dir', 'upload');
                /*
                 * Получить путь файла-значения поля.
                 */
                $sImagePath = '/' . $strImageStorePath . '/' . $arPicture['SUBDIR'] . '/' . $arPicture['FILE_NAME'];
                /*
                 * Расширение файла. 
                 */
                $fileType = self::getExtension($sImagePath);
                /*
                 * Выбрать способ отображения в зависимости от того, является файл
                 * изображением или нет. 
                 */
                if (in_array($fileType, ['png', 'jpg', 'jpeg', 'gif'])) {
                    $content = '<img src="' . $sImagePath . '">';
                } else {
                    $content = '<div class="mf-file-name">' . $arPicture['FILE_NAME'] . '</div>';
                }
                /*
                 * Итоговый HTML для отображения файла-значения поля. 
                 */
                $result = '<tr>
                        <td align="right" valign="top">' . $title . ': </td>
                        <td>
                            <table class="mf-img-table">
                                <tr>
                                    <td>' . $content . '<br>
                                        <div>
                                            <label><input name="' . $strHTMLControlName['VALUE'] . '[' . $code . '][DEL]" value="Y" type="checkbox"> ' . Loc::getMessage("IEX_COMPLEX_PROP_FILE_DELETE") . '</label>
                                            <input name="' . $strHTMLControlName['VALUE'] . '[' . $code . '][OLD]" value="' . $fileId . '" type="hidden">
                                        </div>
                                    </td>
                                </tr>
                            </table>                      
                        </td>
                    </tr>';
            }
        } else {
            /*
             * Файл не указан, поэтому значение value пустое. 
             */
            $result .= '<tr>
                    <td align="right">'.$title.': </td>
                    <td><input type="file" value="" name="'.$strHTMLControlName['VALUE'].'['.$code.']"/></td>
                </tr>';
        }

        return $result;
    }

    /**
     * Сформировать HTML-код для поля-даты свойства.
     * @param string $code Символьный код поля.
     * @param string $title Название поля.
     * @param array $arValue Значения полей свойства.
     * @param string array $strHTMLControlName Имена элементов управления.
     * @return string HTML поля-даты свойства.
     */
    public static function showDate($code, $title, $arValue, $strHTMLControlName)
    {
        $result = '';

        $v = !empty($arValue['VALUE'][$code]) ? $arValue['VALUE'][$code] : '';
        $result .= '<tr>
                        <td align="right" valign="top">'.$title.': </td>
                        <td>
                            <table>
                                <tr>
                                    <td style="padding: 0;">
                                        <div class="adm-input-wrap adm-input-wrap-calendar">
                                            <input class="adm-input adm-input-calendar" type="text" name="' . $strHTMLControlName['VALUE'] . '[' . $code . ']" size="23" value="' . $v . '">
                                            <span class="adm-calendar-icon"
                                                  onclick="BX.calendar({node: this, field:\'' . $strHTMLControlName['VALUE'] . '[' . $code . ']\', form: \'\', bTime: true, bHideTime: false});"></span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>';

        return $result;
    }

    /**
     * Сформировать HTML-код для поля-привязки к элементу.
     * @param string $code Символьный код поля.
     * @param string $title Название поля.
     * @param array $arValue Значения полей свойства.
     * @param string array $strHTMLControlName Имена элементов управления.
     * @return string HTML поля-привязки к элементу..
     */
    public static function showBindElement($code, $title, $arValue, $strHTMLControlName)
    {
        $result = '';
        /*
         * Идентификатор элемента. 
         */
        $v = !empty($arValue['VALUE'][$code]) ? $arValue['VALUE'][$code] : '';

        $elUrl = '';
        if (!empty($v)) {
            /*
             * Получить инфоблок, к которому была выполнена привязка. 
             */
            $arElem = \CIBlockElement::GetList(
                [], 
                ['ID' => $v],
                false, 
                ['nPageSize' => 1], 
                ['ID', 'IBLOCK_ID', 'IBLOCK_TYPE_ID', 'NAME']
            )->Fetch();
            /*
             * Если элемент был получен, то сформировать ссылку на него. 
             */
            if (!empty($arElem)) {
                $elUrl .= '<a target="_blank" href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $arElem['IBLOCK_ID'] . '&ID=' . $arElem['ID'] . '&type=' . $arElem['IBLOCK_TYPE_ID'] . '">' . $arElem['NAME'] . '</a>';
            }
        }

        $result .= '<tr>
                    <td align="right">' . $title . ': </td>
                    <td>
                        <input name="' . $strHTMLControlName['VALUE'] . '[' . $code . ']" id="' . $strHTMLControlName['VALUE'] . '[' . $code . ']" value="' . $v . '" size="8" type="text" class="mf-inp-bind-elem">
                        <input type="button" value="..." onClick="jsUtils.OpenWindow(\'/bitrix/admin/iblock_element_search.php?lang=ru&IBLOCK_ID=0&n=' . $strHTMLControlName['VALUE'] . '&k=' . $code . '\', 900, 700);">&nbsp;
                        <span>' . $elUrl . '</span>
                    </td>
                </tr>';

        return $result;
    }

    /**
     * Метод для получения идентификатора файла в таблице файлов системы.
     * @param array $arValue Значение файлового поля свойства.
     * @return int $result Числовой идентификатор сохраненного и 
     * зарегистрированного в системе файла.
     */
    private static function prepareFileToDB($arValue)
    {
        $result = false;
        /*
         * Удалить файл, если требуется. 
         */
        if (!empty($arValue['DEL']) && $arValue['DEL'] === 'Y' && !empty($arValue['OLD'])) {
            CFile::Delete($arValue['OLD']);
        /*
         * Если удалять не нужно, то вернуть файл под ключом OLD.
         */
        } else if (!empty($arValue['OLD'])) {
            $result = $arValue['OLD'];
        /*
         * Если нет файла, но задано его имя, то сохранить и 
         * зарегистрировать его в таблице файлов.
         */
        } else if (!empty($arValue['name'])) {
            $result = CFile::SaveFile($arValue, 'vote');
        }

        return $result;
    }

    /**
     * Отображение стилей CSS.
     */
    private static function showCss()
    {
        /*
         * Отобразить стили, если они ещё не были отображены ранее.
         */
        if (!self::$showedCss) {
            self::$showedCss = true;
            ?>
            <style>
                .cl {cursor: pointer;}
                .mf-gray {color: #797777;}
                .mf-fields-list {display: none; padding-top: 10px; margin-bottom: 10px!important; margin-left: -300px!important; border-bottom: 1px #e0e8ea solid!important;}
                .mf-fields-list.active {display: block;}
                .mf-fields-list td {padding-bottom: 5px;}
                .mf-fields-list td:first-child {width: 300px; color: #616060;}
                .mf-fields-list td:last-child {padding-left: 5px;}
                .mf-fields-list input[type="text"] {width: 350px!important;}
                .mf-fields-list textarea {min-width: 350px; max-width: 650px; color: #000;}
                .mf-fields-list img {max-height: 150px; margin: 5px 0;}
                .mf-img-table {background-color: #e0e8e9; color: #616060; width: 100%;}
                .mf-fields-list input[type="text"].adm-input-calendar {width: 170px!important;}
                .mf-file-name {word-break: break-word; padding: 5px 5px 0 0; color: #101010;}
                .mf-fields-list input[type="text"].mf-inp-bind-elem {width: unset!important;}
            </style>
            <?
        }
    }

    /**
     * Подключение скриптов.
     */
    private static function showJs()
    {
        /*
         * Показать/свернуть.
         */
        $showText = Loc::getMessage('IEX_COMPLEX_PROP_SHOW_TEXT');
        $hideText = Loc::getMessage('IEX_COMPLEX_PROP_HIDE_TEXT');
        /*
         * Подключить jQuery. 
         */
        CJSCore::Init(array("jquery"));
        /*
         * Подключить скрипты класса, если они не были подключены ранее. 
         */
        if (!self::$showedJs) {
            self::$showedJs = true;
            ?>
            <script>
                /*
                 * Переключение отображения "показать/свернуть". 
                 */
                $(document).on('click', 'a.mf-toggle', function (e) {
                    e.preventDefault();

                    var table = $(this).closest('tr').find('table.mf-fields-list');
                    $(table).toggleClass('active');
                    if ($(table).hasClass('active')){
                        $(this).text('<?=$hideText?>');
                    } else {
                        $(this).text('<?=$showText?>');
                    }
                });
                /*
                 * При нажатии на "удалить" установить пустые значения или
                 * значения по умолчанию для инпутов. 
                 */
                $(document).on('click', 'a.mf-delete', function (e) {
                    e.preventDefault();

                    var textInputs = $(this).closest('tr').find('input[type="text"]');
                    $(textInputs).each(function (i, item) {
                        $(item).val('');
                    });

                    var textarea = $(this).closest('tr').find('textarea');
                    $(textarea).each(function (i, item) {
                        $(item).text('');
                    });

                    var checkBoxInputs = $(this).closest('tr').find('input[type="checkbox"]');
                    $(checkBoxInputs).each(function (i, item) {
                        $(item).attr('checked', 'checked');
                    });

                    $(this).closest('tr').hide('slow');
                });
            </script>
            <?
        }
    }

    /**
     * Создание массива полей комплексного свойства с их параметрами:
     * символьный код как ключ, значения - заголовок, сортировка и тип поля.
     * @param array $arSetting Параметры комплексного свойства.
     * @return array Массив полей и их параметров комплексного свойства.
     */
    private static function prepareSetting($arSetting)
    {
        $arResult = [];
        /*
         * Преобразовать параметры в двумерный массив, где ключи
         * первого измерения - символьные коды полей, 
         * а второе измерение - значения для TITLE, SORT и TYPE.
         */
        foreach ($arSetting as $key => $value) {
            if (strstr($key, '_TITLE') !== false) {
                $code = str_replace('_TITLE', '', $key);
                $arResult[$code]['TITLE'] = $value;
            }
            else if (strstr($key, '_SORT') !== false) {
                $code = str_replace('_SORT', '', $key);
                $arResult[$code]['SORT'] = $value;
            }
            else if (strstr($key, '_TYPE') !== false) {
                $code = str_replace('_TYPE', '', $key);
                $arResult[$code]['TYPE'] = $value;
            }
        }
        /*
         * Задать функцию сортировки. 
         */
        if (!function_exists('cmp')) {
            function cmp($a, $b)
            {
                if ($a['SORT'] == $b['SORT']) {
                    return 0;
                }
                return ($a['SORT'] < $b['SORT']) ? -1 : 1;
            }
        }
        /*
         * Отсортировать поля по их полю сортировки. 
         */
        uasort($arResult, 'cmp');

        return $arResult;
    }

    /**
     * Получить расширение указанного файла.
     * @param string $filePath Путь до файла.
     * @return string Расширение файла.
     */
    private static function getExtension($filePath)
    {
        return array_pop(explode('.', $filePath));
    }
}