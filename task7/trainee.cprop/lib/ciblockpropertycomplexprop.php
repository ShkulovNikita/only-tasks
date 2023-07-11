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
            // Конвертация из формата БД в формат для обработки.
            'ConvertFromDB' => array(__CLASS__,  'ConvertFromDB'),
            // HTML настроек свойства в форме редактирования инфоблока.
            'GetSettingsHTML' => array(__CLASS__, 'GetSettingsHTML'),
            // Настройки свойства перед сохранением метаданных свойства в БД.
            'PrepareSettings' => array(__CLASS__, 'PrepareUserSettings'),
            // Длина значения свойства.
            'GetLength' => array(__CLASS__, 'GetLength'),
            // HTML для отображения свойства в публичной части.
            'GetPublicViewHTML' => array(__CLASS__, 'GetPublicViewHTML')
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
            } else if ($arItem['TYPE'] === 'file') {
                $result .= self::showFile($code, $arItem['TITLE'], $value, $strHTMLControlName);
            } else if ($arItem['TYPE'] === 'text') {
                $result .= self::showTextarea($code, $arItem['TITLE'], $value, $strHTMLControlName);
            } else if ($arItem['TYPE'] === 'date') {
                $result .= self::showDate($code, $arItem['TITLE'], $value, $strHTMLControlName);
            } else if ($arItem['TYPE'] === 'element') {
                $result .= self::showBindElement($code, $arItem['TITLE'], $value, $strHTMLControlName);
            } else if ($arItem['TYPE'] === 'html') {
                $result .= self::showHtmlElement($code, $arItem['TITLE'], $value, $strHTMLControlName);
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
    public static function ConvertToDB($arProperty, &$arValue)
    {
        /*
         * Получить массив со значениями и типами полей свойства.
         */
        $arFields = self::prepareSetting($arProperty['USER_TYPE_SETTINGS']);
        if (!empty($arValue['VALUE'])) {
            self::addHtmlFields($arFields, $arValue);
        }
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
     * Метод для преобразования значений свойства из формата,
     * пригодного для сохранения в БД, в формат для обработки.
     * @param array $arProperty Метаданные свойства.
     * @param array $arValue Значение свойства.
     * @return array Значения свойства в формате, пригодном для обработки.
     */
    public static function ConvertFromDB($arProperty, $arValue)
    {
        $return = array();

        /*
         * Если значение свойства из БД не пусто, то десериализовать. 
         */
        if (!empty($arValue['VALUE'])) {
            $arData = json_decode($arValue['VALUE'], true);

            foreach ($arData as $code => $value){
                $return['VALUE'][$code] = $value;
            }
        }
        
        return $return;
    }

    /**
     * Метод, формирующий безопасный HTML отображения настроек свойства для
     * формы редактирования инфоблока.
     * @param array $arProperty Метаданные свойства.
     * @param array $strHTMLControlName Имя элемента управления для заполнения настроек свойства.
     * @param array $arPropertyFields Пустой массив.
     * @return string HTML для встраивания в форму редактирования инфоблока.
     */
    public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
    {
        /*
         * "Добавить", "Список полей". 
         */
        $btnAdd = Loc::getMessage('IEX_COMPLEX_PROP_SETTING_BTN_ADD');
        $settingsTitle =  Loc::getMessage('IEX_COMPLEX_PROP_SETTINGS_TITLE');
        /*
         * Дополнительные флаги управления формой. 
         */
        $arPropertyFields = array(
            /*
             * "Список полей". 
             */
            'USER_TYPE_SETTINGS_TITLE' => $settingsTitle,
            /*
             * Массив названий полей свойства, которые будут скрыты для редактирования.
             */
            'HIDE' => array(
                'ROW_COUNT', 
                'COL_COUNT', 
                'DEFAULT_VALUE', 
                'SEARCHABLE', 
                'SMART_FILTER', 
                'WITH_DESCRIPTION', 
                'FILTRABLE', 
                'MULTIPLE_CNT', 
                'IS_REQUIRED'
            ),
            /*
             * Массив полей для принудительного выставления значений в случае, 
             * если они не отображаются в форме.
             */
            'SET' => array(
                'MULTIPLE_CNT' => 1,
                'SMART_FILTER' => 'N',
                'FILTRABLE' => 'N',
            ),
        );
        /*
         * Подключить стили и скрипты для формирования формы настроек. 
         */
        self::showJsForSetting($strHTMLControlName["NAME"]);
        self::showCssForSetting();
        /*
         * Формирование заголовка таблицы-формы настроек полей свойства.
         */
        $result = '<tr><td colspan="2" align="center">
            <table id="many-fields-table" class="many-fields-table internal">        
                <tr valign="top" class="heading mf-setting-title">
                   <td>XML_ID</td>
                   <td>' . Loc::getMessage('IEX_COMPLEX_PROP_SETTING_FIELD_TITLE') . '</td>
                   <td>' . Loc::getMessage('IEX_COMPLEX_PROP_SETTING_FIELD_SORT') . '</td>
                   <td>' . Loc::getMessage('IEX_COMPLEX_PROP_SETTING_FIELD_TYPE') . '</td>
                </tr>';
        /*
         * Получить массив полей свойства с их параметрами.
         */
        $arSetting = self::prepareSetting($arProperty['USER_TYPE_SETTINGS']);
        /*
         * Вывести значения и параметры уже заданных полей свойства.
         */
        if (!empty($arSetting)) {
            foreach ($arSetting as $code => $arItem) {
                $result .= '
                       <tr valign="top">
                           <td><input type="text" class="inp-code" size="20" value="' . $code . '"></td>
                           <td><input type="text" class="inp-title" size="35" name="' . $strHTMLControlName["NAME"] . '[' . $code . '_TITLE]" value="' . $arItem['TITLE'] . '"></td>
                           <td><input type="text" class="inp-sort" size="5" name="' . $strHTMLControlName["NAME"] . '[' . $code . '_SORT]" value="' . $arItem['SORT'] . '"></td>
                           <td>
                                <select class="inp-type" name="' . $strHTMLControlName["NAME"] . '[' . $code . '_TYPE]">
                                    ' . self::getOptionList($arItem['TYPE']) . '
                                </select>                        
                           </td>
                       </tr>';
            }
        }
        /*
         * Вывести дополнительное пустое поле для возможности его добавления,
         * а также кнопку "Добавить".
         */
        $result .= '
               <tr valign="top">
                    <td><input type="text" class="inp-code" size="20"></td>
                    <td><input type="text" class="inp-title" size="35"></td>
                    <td><input type="text" class="inp-sort" size="5" value="500"></td>
                    <td>
                        <select class="inp-type"> ' . self::getOptionList() . '</select>                        
                    </td>
               </tr>
             </table>   
                
                <tr>
                    <td colspan="2" style="text-align: center;">
                        <input type="button" value="' . $btnAdd . '" onclick="addNewRows()">
                    </td>
                </tr>
                </td></tr>';

        return $result;
    }

    /**
     * Получить массив с настройками свойства для их сохранения в БД.
     * @param array $arProperty Значения полей метаданных свойства.
     * @return array Массив с настройками свойства.
     */
    public static function PrepareUserSettings($arProperty)
    {
        $result = [];
        if (!empty($arProperty['USER_TYPE_SETTINGS'])) {
            foreach ($arProperty['USER_TYPE_SETTINGS'] as $code => $value) {
                $result[$code] = $value;
            }
        }
        return $result;
    }

    /**
     * Получить длину значения свойства.
     * @param array $arProperty Метаданные свойства.
     * @param array $arValue Значение свойства.
     * @return bool Является ли значение свойства заполненным.
     */
    public static function GetLength($arProperty, $arValue)
    {
        /*
         * Получить массив с полями свойства и их значениями.
         */
        $arFields = self::prepareSetting(unserialize($arProperty['USER_TYPE_SETTINGS']));
        /*
         * Если хотя бы одно поле заполнено, то вернуть true. 
         */
        $result = false;
        foreach ($arValue['VALUE'] as $code => $value) {
            if ($arFields[$code]['TYPE'] === 'file') {
                if (!empty($value['name']) || (!empty($value['OLD']) && empty($value['DEL']))) {
                    $result = true;
                    break;
                }
            } else {
                if (!empty($value)) {
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Сформировать HTML для отображения свойства в публичной части сайта.
     * @param array $arProperty Метаданные свойства.
     * @param array $value Значение свойства.
     * @param array $strHTMLControlName Пустой массив.
     * @return string HTML для отображения свойства в публичной части.
     */
    public static function GetPublicViewHTML($arProperty, $value, $strHTMLControlName)
    {
        /*
         * Подготовить массив с параметрами полей комплексного свойства. 
         */
        if (!empty($arProperty['USER_TYPE_SETTINGS'])) {
            $arFields = self::prepareSetting($arProperty['USER_TYPE_SETTINGS']);
        } else {
            return '<span>'.Loc::getMessage('IEX_COMPLEX_PROP_ERROR_INCORRECT_SETTINGS').'</span>';
        }

        $result = '';
        /*
         * Перебрать все поля свойства. 
         */
        foreach ($arFields as $code => $arItem) {
            if ($arItem['TYPE'] === 'string') {
                $result .= self::showString($code, $arItem['TITLE'], $value, [], 'public');
            } else if ($arItem['TYPE'] === 'file') {
                $result .= self::showFile($code, $arItem['TITLE'], $value, [], 'public');
            } else if ($arItem['TYPE'] === 'text') {
                $result .= self::showTextArea($code, $arItem['TITLE'], $value, [], 'public');
            } else if ($arItem['TYPE'] === 'date') {
                $result .= self::showDate($code, $arItem['TITLE'], $value, [], 'public');
            } else if ($arItem['TYPE'] === 'element') {
                $result .= self::showBindElement($code, $arItem['TITLE'], $value, [], 'public');
            } else if ($arItem['TYPE'] === 'html') {
                $result .= self::showHtmlElement($code, $arItem['TITLE'], $value, [], 'public');
            }
        }

        return $result;
    }

    /**
     * Сформировать HTML-код для текстового поля свойства.
     * @param string $code Символьный код поля.
     * @param string $title Название поля.
     * @param array $arValue Значения полей свойства.
     * @param array $strHTMLControlName Имена элементов управления.
     * @param string $type Отображается ли поле для редактирования в админке ('admin')
     * или в публичной части ('public').
     * @return string HTML текстового поля свойства.
     */
    private static function showString($code, $title, $arValue, $strHTMLControlName, $type = 'admin')
    {
        $result = '';
        /*
         * Получить значение свойства для данного поля по его символьному коду
         * либо установить пустое значение. 
         */
        $v = !empty($arValue['VALUE'][$code]) ? $arValue['VALUE'][$code] : '';
        if ($type == 'admin') {
            $result .= '<tr>
                    <td align="right">'.$title.': </td>
                    <td><input type="text" value="'.$v.'" name="'.$strHTMLControlName['VALUE'].'['.$code.']"/></td>
                </tr>';
        } elseif ($type == 'public') {
            $result .= '<p>' . $title . ':&nbsp' . $v . '</p>';
        }

        return $result;
    }

    /**
     * Сформировать HTML-код для поля-визуального редактора.
     * @param string $code Символьный код поля.
     * @param string $title Название поля.
     * @param array $arValue Значения полей свойства.
     * @param array $strHTMLControlName Имена элементов управления.
     * @param string $type Отображается ли поле для редактирования в админке ('admin')
     * или в публичной части ('public').
     * @return string HTML поля-визуального редактора.
     */
    private static function showHtmlElement($code, $title, $arValue, $strHTMLControlName, $type = 'admin')
    {
        $result = '';
        /*
         * Получить значение свойства для данного поля
         * либо установить пустое значение. 
         */
        $v = !empty($arValue['VALUE'][$code]) ? $arValue['VALUE'][$code] : '';
        
        $name = $strHTMLControlName['VALUE'] . '[' . $code . ']';
        $name = preg_replace("/[\[\]]/i", "_", $name);

        if ($type == 'admin') {
            $htmlEditorHeight = 80;
            /*
             * Начать буферизацию, чтобы получить HTML-код редактора. 
             */
            ob_start();
            /*
             * Вывести визуальный редактор. 
             */
            CFileMan::AddHTMLEditorFrame(
                /*
                 * Имя текущего пользовательского поля. 
                 */
                $name,
                /*
                 * Уже введенное значение, если есть. 
                 */
                $v,
                /*
                 * Имя типа поля имя_TYPE.
                 */
                $name . "_TYPE",
                /*
                 * Тип введенного текста (?): html либо обычный текст. 
                 */
                "html",
                /*
                 * Высота поля ввода. 
                 */
                array(
                    'height' => $htmlEditorHeight,
                )
            );

            echo '<input type="hidden" name="'.$strHTMLControlName["VALUE"] . '[' . $code . ']' .'" >';

            /*
             * Получить значение из буфера и закрыть буфер. 
             */
            $tempResult .= ob_get_contents();
            ob_end_clean();

            $result .= '<tr>' . 
                            '<td align="right">' . $title . ': </td>' .
                            '<td>' . $tempResult . '</td>' .
                        '</tr>';
        } elseif ($type == 'public') {
            $result .= '<p>' . $title . ':&nbsp' . $v . '</p>';
        }

        return $result;
    }

    /**
     * Сформировать HTML-код для многострочного текстового поля свойства.
     * @param string $code Символьный код поля.
     * @param string $title Название поля.
     * @param array $arValue Значения полей свойства.
     * @param array $strHTMLControlName Имена элементов управления.
     * @param string $type Отображается ли поле для редактирования в админке ('admin')
     * или в публичной части ('public').
     * @return string HTML многострочного текстового поля свойства.
     */
    public static function showTextarea($code, $title, $arValue, $strHTMLControlName, $type = 'admin')
    {
        $result = '';

        $v = !empty($arValue['VALUE'][$code]) ? $arValue['VALUE'][$code] : '';
        if ($type == 'admin') {
            $result .= '<tr>
                    <td align="right" valign="top">'.$title.': </td>
                    <td><textarea rows="8" name="'.$strHTMLControlName['VALUE'].'['.$code.']">'.$v.'</textarea></td>
                </tr>';
        } elseif ($type == 'public') {
            $arParts = preg_split("/\r\n|[\r\n]/", $v);
            $result .= '<p>' . $title . ':</p>';
            foreach ($arParts as $part) {
                $result .= '<p>' . $part . '</p>';
            }
            $result .= '<p></p>';
        }

        return $result;
    }

    /**
     * Получить файл из значения указанного свойства.
     * @param string $code Символьный код файлового поля свойства.
     * @param array $arValue Значение свойства.
     * @return int|string Идентификатор файла в системе либо пустая строка.
     */
    private static function getFileIdFromPropValue($code, $arValue)
    {
        $fileId;
        if (!empty($arValue['VALUE'][$code]) && !is_array($arValue['VALUE'][$code])) {
            $fileId = $arValue['VALUE'][$code];
        }
        else if (!empty($arValue['VALUE'][$code]['OLD'])) {
            $fileId = $arValue['VALUE'][$code]['OLD'];
        }
        else {
            $fileId = '';
        }

        return $fileId;
    }

    /**
     * Получить составляющие пути до файла и его расширение.
     * @param array $arFile Массив с информацией о файле.
     * @param string $strFileStorePath Путь до папки загрузок.
     * @param string $sFilePath Полный путь до файла.
     * @param string $fileType Тип файла.
     */
    private static function getFileInfo($arFile, &$strFileStorePath, &$sFilePath, &$fileType)
    {
        /*
         * Получить путь для загрузки файлов. 
         */
        $strFileStorePath = COption::GetOptionString('main', 'upload_dir', 'upload');
        /*
         * Получить путь файла-значения поля.
         */
        $sFilePath = '/' . $strFileStorePath . '/' . $arFile['SUBDIR'] . '/' . $arFile['FILE_NAME'];
        /*
         * Расширение файла. 
         */
        $fileType = self::getExtension($sFilePath);
    }

    /**
     * Сформировать HTML-код для файлового поля свойства.
     * @param string $code Символьный код поля.
     * @param string $title Название поля.
     * @param array $arValue Значения полей свойства.
     * @param array $strHTMLControlName Имена элементов управления.
     * @param string $type Отображается ли поле для редактирования в админке ('admin')
     * или в публичной части ('public').
     * @return string HTML файлового поля свойства.
     */
    private static function showFile($code, $title, $arValue, $strHTMLControlName, $type = 'admin')
    {
        $result = '';

        $fileId = self::getFileIdFromPropValue($code, $arValue);
        if (!empty($fileId)) {
            /*
             * Получить информацию о файле. 
             */
            $arPicture = CFile::GetByID($fileId)->Fetch();
            /*
             * Если информация о файле была успешно получена. 
             */
            if ($arPicture) {
                /*
                 * Получить информацию о пути до файла и его типе. 
                 */
                self::getFileInfo($arPicture, $strImageStorePath, $sImagePath, $fileType);
                /*
                 * Выбрать способ отображения в зависимости от того, является файл
                 * изображением или нет. 
                 */
                $content = '';
                if (in_array($fileType, ['png', 'jpg', 'jpeg', 'gif'])) {
                    $content = '<p>' . $title . ':</p>' . '<img src="' . $sImagePath . '">';
                } else {
                    if ($type == 'admin') {
                        $content = '<div class="mf-file-name">' . $arPicture['FILE_NAME'] . '</div>';
                    } elseif ($type == 'public') {
                        $content = '<p>' . $title . ':&nbsp' . $arPicture['FILE_NAME'] . '</p>';
                    }
                }
                /*
                 * Итоговый HTML для отображения файла-значения поля. 
                 */
                if ($type == 'admin') {
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
                } elseif ($type == 'public') {
                    $result = $content;
                }
            }
        } else {
            if ($type == 'admin') {
                $result .= '<tr>
                    <td align="right">'.$title.': </td>
                    <td><input type="file" value="" name="'.$strHTMLControlName['VALUE'].'['.$code.']"/></td>
                    </tr>';
            }
            
        }

        return $result;
    }

    /**
     * Сформировать HTML-код для поля-даты свойства.
     * @param string $code Символьный код поля.
     * @param string $title Название поля.
     * @param array $arValue Значения полей свойства.
     * @param array $strHTMLControlName Имена элементов управления.
     * @param string $type Отображается ли поле для редактирования в админке ('admin')
     * или в публичной части ('public').
     * @return string HTML поля-даты свойства.
     */
    public static function showDate($code, $title, $arValue, $strHTMLControlName, $type = 'admin')
    {
        $result = '';

        $v = !empty($arValue['VALUE'][$code]) ? $arValue['VALUE'][$code] : '';
        if ($type == 'admin') {
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
        } elseif ($type == 'public') {
            $result .= '<p>' . $title . ':&nbsp' . $v . '</p>';
        }

        return $result;
    }

    /**
     * Сформировать HTML-код для поля-привязки к элементу.
     * @param string $code Символьный код поля.
     * @param string $title Название поля.
     * @param array $arValue Значения полей свойства.
     * @param array $strHTMLControlName Имена элементов управления.
     * @param string $type Отображается ли поле для редактирования в админке ('admin')
     * или в публичной части ('public').
     * @return string HTML поля-привязки к элементу.
     */
    public static function showBindElement($code, $title, $arValue, $strHTMLControlName, $type = 'admin')
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

        if ($type == 'admin') {
            $result .= '<tr>
                            <td align="right">' . $title . ': </td>
                            <td>
                                <input name="' . $strHTMLControlName['VALUE'] . '[' . $code . ']" id="' . $strHTMLControlName['VALUE'] . '[' . $code . ']" value="' . $v . '" size="8" type="text" class="mf-inp-bind-elem">
                                <input type="button" value="..." onClick="jsUtils.OpenWindow(\'/bitrix/admin/iblock_element_search.php?lang=ru&IBLOCK_ID=0&n=' . $strHTMLControlName['VALUE'] . '&k=' . $code . '\', 900, 700);">&nbsp;
                                <span>' . $elUrl . '</span>
                            </td>
                        </tr>';
        } elseif ($type == 'public') {
            $result .= '<p>' . $title . ':&nbsp' . $elUrl . '</p>';
        }

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
     * Добавить в массив значений полей свойства значения,
     * введенные в визуальном редакторе.
     * @param array $arFields Массив со всеми полями свойства и их параметрами.
     * @param array $arValue Массив со значениями полей свойства.
     */
    private static function addHtmlFields($arFields, &$arValue)
    {
        /*
         * Перебрать все поля свойства.
         */
        foreach ($arFields as $arFieldCode => $arFieldValue) {
            /*
             * Поля типа 'html' следует внести в массив значений полей. 
             */
            if ($arFieldValue['TYPE'] == 'html') {
                /*
                 * Перебрать значения из POST, чтобы найти подходящее. 
                 */
                foreach($_POST as $keyFromPost => $valFromPost) {
                    if (
                        preg_match("/\w+__VALUE__" . $arFieldCode . "_$/i",
                        $keyFromPost, 
                        $m) 
                    ) {
                        $arValue['VALUE'][$arFieldCode] = $valFromPost;
                        unset($_POST[$keyFromPost]);
                        break;
                    }
                }
            }
        }
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
     * Применить скрипты к настрокам свойства в форме редактирования инфоблока.
     * @param string HTML имени для настроек.
     */
    private static function showJsForSetting($inputName)
    {
        CJSCore::Init(array("jquery"));
        ?>
        <script>
            /*
             * Сформировать строку формы с инпутами для ввода кода, имени, 
             * сортировки и типа поля свойства.
             */
            function addNewRows() {
                $("#many-fields-table").append('' +
                    '<tr valign="top">' +
                    '<td><input type="text" class="inp-code" size="20"></td>' +
                    '<td><input type="text" class="inp-title" size="35"></td>' +
                    '<td><input type="text" class="inp-sort" size="5" value="500"></td>' +
                    '<td><select class="inp-type"><?=self::getOptionList()?></select></td>' +
                    '</tr>');
            }
            /*
             * При изменении символьного кода соответствующе изменять имена полей для названия, сортировки и типа.
             */
            $(document).on('change', '.inp-code', function(){
                var code = $(this).val();

                if (code.length <= 0){
                    $(this).closest('tr').find('input.inp-title').removeAttr('name');
                    $(this).closest('tr').find('input.inp-sort').removeAttr('name');
                    $(this).closest('tr').find('select.inp-type').removeAttr('name');
                } else {
                    $(this).closest('tr').find('input.inp-title').attr('name', '<?=$inputName?>[' + code + '_TITLE]');
                    $(this).closest('tr').find('input.inp-sort').attr('name', '<?=$inputName?>[' + code + '_SORT]');
                    $(this).closest('tr').find('select.inp-type').attr('name', '<?=$inputName?>[' + code + '_TYPE]');
                }
            });
            /*
             * Не допускать ввод нечисловых значений в поле сортировки. 
             */
            $(document).on('input', '.inp-sort', function(){
                var num = $(this).val();
                $(this).val(num.replace(/[^0-9]/gim,''));
            });
        </script>
        <?
    }

    /**
     * Получить HTML списка типов полей свойства.
     * @param string $selected Уже выбранный пользователем в форме тип
     * (string, file, text, date, element, html).
     * @return string HTML списка типов полей комплексного свойства.
     */
    private static function getOptionList($selected = 'string')
    {
        $result = '';
        /*
         * Отображаемые названия типов полей свойства. 
         */
        $arOption = [
            'string' => Loc::getMessage('IEX_COMPLEX_PROP_FIELD_TYPE_STRING'),
            'file' => Loc::getMessage('IEX_COMPLEX_PROP_FIELD_TYPE_FILE'),
            'text' => Loc::getMessage('IEX_COMPLEX_PROP_FIELD_TYPE_TEXT'),
            'date' => Loc::getMessage('IEX_COMPLEX_PROP_FIELD_TYPE_DATE'),
            'element' => Loc::getMessage('IEX_COMPLEX_PROP_FIELD_TYPE_ELEMENT'),
            'html' => Loc::getMessage('IEX_COMPLEX_PROP_FIELD_TYPE_HTML'),
        ];
        /*
         * В зависимости от параметра определить, какой тип уже выбран. 
         */
        foreach ($arOption as $code => $name) {
            $s = '';
            if ($code === $selected) {
                $s = 'selected';
            }

            $result .= '<option value="' . $code . '" ' . $s . '>' . $name . '</option>';
        }

        return $result;
    }

    /**
     * Применить CSS, используемые при настройках свойства в форме
     * редактирования инфоблока.
     */
    private static function showCssForSetting()
    {
        if (!self::$showedCss) {
            self::$showedCss = true;
            ?>
            <style>
                .many-fields-table {margin: 0 auto; /*display: inline;*/}
                .mf-setting-title td {text-align: center!important; border-bottom: unset!important;}
                .many-fields-table td {text-align: center;}
                .many-fields-table > input, .many-fields-table > select{width: 90%!important;}
                .inp-sort{text-align: center;}
                .inp-type{min-width: 125px;}
            </style>
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
