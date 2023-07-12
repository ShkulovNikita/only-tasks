<?php

use \Bitrix\Main\Localization\Loc;

class CComplexUserField extends \Bitrix\Main\UserField\Types\StringType
{
    private static $showedCss = false;
    private static $showedJs = false;

    /**
     * Метод-обработчик для задания комлексного типа пользовательского поля.
     * @return array Массив, описывающий поведение пользовательского поля.
     */
	public static function GetUserTypeDescription(): array
	{
		return array(
			"USER_TYPE_ID" => "complexhtml",
			"CLASS_NAME" => "CComplexUserField",
			"DESCRIPTION" => GetMessage("COMPLEX_USER_FIELD_NAME"),
			"BASE_TYPE" => "string",
		);
	} 

    /**
     * Получить HTML для работы с пользовательским полем.
     * @param array $arUserField Параметры пользовательского поля.
     * @param array $arHtmlControl Массив с именем текущего поля и его значением.
     * @return string HTML формы редактирования пользовательского поля.
     */
	public static function GetEditFormHTML(array $arUserField, ?array $arHtmlControl): string
    {
        /*
         * Свернуть/удалить. 
         */
        $hideText = Loc::getMessage('COMPLEX_USER_FIELD_FORM_HIDE_TEXT');
        $clearText = Loc::getMessage('COMPLEX_USER_FIELD_FORM_CLEAR_TEXT');
        /*
         * Подключить CSS и JS класса. 
         */
        self::showCss();
        self::showJs();
        /*
         * Подготовить массив с параметрами полей пользовательского поля. 
         */
        if (!empty($arUserField['SETTINGS'])) {
            $arFields = self::prepareSetting($arUserField['SETTINGS']);
        } else {
            return '<span>'.Loc::getMessage('COMPLEX_USER_FIELD_FORM_ERROR').'</span>';
        }
        /*
         * HTML-формы редактирования значений.
         */
        $result = '';
        /*
         * Кнопка "свернуть/показать" для отображения значений комплексного поля. 
         */
        $result .= '<div class="mf-gray"><a class="cl mf-toggle">' . $hideText . '</a>';
        /*
         * Если свойство множественное, то также отображать кнопку "удалить". 
         */
        if($arUserField['MULTIPLE'] === 'Y'){
            $result .= ' | <a class="cl mf-delete">' . $clearText . '</a></div>';
        }
        $result .= '<table class="mf-fields-list active">';
        /*
         * Для всех полей вывести соответствующий HTML. 
         */
        foreach ($arFields as $code => $arItem) {
            if ($arItem['TYPE'] === 'string') {
                $result .= self::showString($code, $arItem['TITLE'], $arHtmlControl, $arHtmlControl);
            } else if ($arItem['TYPE'] === 'file') {
                $result .= self::showFile($code, $arItem['TITLE'], $arHtmlControl, $arHtmlControl);
            } else if ($arItem['TYPE'] === 'text') {
                $result .= self::showTextarea($code, $arItem['TITLE'], $arHtmlControl, $arHtmlControl);
            } else if ($arItem['TYPE'] === 'date') {
                $result .= self::showDate($code, $arItem['TITLE'], $arHtmlControl, $arHtmlControl);
            } else if ($arItem['TYPE'] === 'element') {
                $result .= self::showBindElement($code, $arItem['TITLE'], $arHtmlControl, $arHtmlControl);
            } else if ($arItem['TYPE'] === 'html') {
                $result .= self::showHtmlElement($code, $arItem['TITLE'], $arUserField, $arHtmlControl);
            }
        }
        $result .= '</table>';

        return $result;
    }

    /**
     * Метод, формирующий вывод HTML для настроек поля.
     * @param array $arUserField Параметры пользовательского поля.
     * @param array $arHtmlControl Массив для хранения имен и значений полей формы.
     * @return string HTML формы настроек поля.
     */
    public static function GetSettingsHTML($arUserField = false, ?array $arHtmlControl, $bVarsFromForm): string
    {
        /*
         * "Добавить", "Список полей". 
         */
        $btnAdd = Loc::getMessage('COMPLEX_USER_FIELD_SETTINGS_BTN_ADD');
        $settingsTitle =  Loc::getMessage('COMPLEX_USER_FIELD_SETTINGS_LIST_NAME');
        /*
         * Здесь подключение стилей и скриптов формы. 
         */
        self::showJsForSetting($arHtmlControl['NAME']);
        self::showCssForSetting();
        /*
         * Формирование заголовка таблицы-формы настроек полей. 
         */
        $result = '<tr><td colspan="2" align="center">
            <table id="many-fields-table" class="many-fields-table internal">        
                <tr valign="top" class="heading mf-setting-title">
                   <td>XML_ID</td>
                   <td>' . Loc::getMessage('COMPLEX_USER_FIELD_SETTINGS_TITLE_NAME') . '</td>
                   <td>' . Loc::getMessage('COMPLEX_USER_FIELD_SETTINGS_TITLE_SORT') . '</td>
                   <td>' . Loc::getMessage('COMPLEX_USER_FIELD_SETTINGS_TITLE_TYPE') . '</td>
                </tr>';
        /*
         * Получить массив полей свойства с их параметрами.
         */
        $arSetting = self::prepareSetting($arUserField['SETTINGS']);
        /*
         * Вывести значения и параметры уже заданных полей свойства.
         */
        if (!empty($arSetting)) {
            foreach ($arSetting as $code => $arItem) {
                $result .= '
                       <tr valign="top">
                           <td><input type="text" class="inp-code" size="20" value="' . $code . '"></td>
                           <td><input type="text" class="inp-title" size="35" name="' . $arHtmlControl["NAME"] . '[' . $code . '_TITLE]" value="' . $arItem['TITLE'] . '"></td>
                           <td><input type="text" class="inp-sort" size="5" name="' . $arHtmlControl["NAME"] . '[' . $code . '_SORT]" value="' . $arItem['SORT'] . '"></td>
                           <td>
                                <select class="inp-type" name="' . $arHtmlControl["NAME"] . '[' . $code . '_TYPE]">
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
     * Метод, отвечающий за заполнение массива параметров пользовательского поля.
     * @param array $arUserField Массив со значениями полей настроек.
     * @return array Массив настроек, в который были занесены значения полей
     * из формы настроек.
     */
    public static function PrepareSettings(array $arUserField) : array
    {   
        $result = [];
        if (!empty($arUserField['SETTINGS'])) {
            foreach ($arUserField['SETTINGS'] as $code => $value) {
                $result[$code] = $value;
            }
        }

        return $result;
    }

    /**
     * Преобразование значения для его сохранения.
     * @param array $arUserField Параметры поля.
     * @param array $value Значение поля.
     * @return string Значение поля.
     */
	public static function OnBeforeSave($arUserField, $value): string
    {
        $arFields = self::prepareSetting($arUserField['SETTINGS']);
        /*
         * Дополнение массива значений значениями из визуальных редакторов.
         */
        if (!empty($value)) {
            self::addHtmlFields($arFields, $value);
        }
        /*
         * Для полей типа "файл" получить идентификаторы файлов
         * в таблице файлов системы. 
         */
        foreach ($value as $code => $v) {
            if ($arFields[$code]['TYPE'] === 'file') {
                $value[$code] = self::prepareFileToDB($v);
            }
        }

        $arResult = json_encode($value);

        return $arResult;
    }

    /**
     * Метод, срабатываемый при получении данных из БД.
     * Необходим для предварительной десериализации значений поля.
     * @param array $arProperty Параметры пользовательского поля.
     * @param array $arValue Массив значений поля.
     * @return array Десериализованные значения поля из БД.
     */
    public static function onAfterFetch($arProperty, $arValue): array
    {
        if (!empty($arValue["VALUE"])) {
            $arValue = \Bitrix\Main\Web\Json::decode(html_entity_decode($arValue["VALUE"]));
        }

        return $arValue;
    }

    /**
     * Метод, отвечающий за валидацию введенного значения.
     * Необходимо перекрыть одноименный метод из исходного типа StringType.
     * @param array $arUserField Параметры поля.
     * @param array $value Значение поля.
     * @return array Пустой массив ошибок.
     */
    public static function CheckFields(array $userField, $value): array
    {
        $aMsg = array();
        return $aMsg;
    }

    /**
     * Применить стили, используемые в форме настройки пользовательского поля.
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
     * Сформировать HTML-код для текстового поля.
     * @param string $code Символьный код поля.
     * @param string $title Название поля.
     * @param array $arValue Значения полей свойства.
     * @param array $arHtmlControl Имена элементов управления.
     * @return string HTML текстового поля.
     */
    private static function showString($code, $title, $arValue, $arHtmlControl)
    {
        $result = '';
        /*
         * Получить значение свойства для данного поля по его символьному коду
         * либо установить пустое значение. 
         */
        $v = !empty($arValue['VALUE'][$code]) ? $arValue['VALUE'][$code] : '';
        $result .= '<tr>
                <td align="right">'.$title.': </td>
                <td><input type="text" value="'.$v.'" name="'.$arHtmlControl['NAME'] . '['.$code.']"/></td>
            </tr>';

        return $result;
    }

    /**
     * Сформировать HTML-код для файлового поля.
     * @param string $code Символьный код поля.
     * @param string $title Название поля.
     * @param array $arValue Значения полей свойства.
     * @param array $arHtmlControl Имена элементов управления.
     * @return string HTML файлового поля.
     */
    private static function showFile($code, $title, $arValue, $strHTMLControlName)
    {
        $result = '';

        $fileId = self::getFileIdFromPropValue($code, $strHTMLControlName);

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
                $result = '<tr>
                <td align="right" valign="top">' . $title . ': </td>
                <td>
                    <table class="mf-img-table">
                        <tr>
                            <td>' . $content . '<br>
                                <div>
                                    <label><input name="' . $strHTMLControlName['NAME'] . '[' . $code . '][DEL]" value="Y" type="checkbox"> ' . Loc::getMessage("COMPLEX_USER_FIELD_FORM_DELETE_FILE_TEXT") . '</label>
                                    <input name="' . $strHTMLControlName['NAME'] . '[' . $code . '][OLD]" value="' . $fileId . '" type="hidden">
                                </div>
                            </td>
                        </tr>
                    </table>                      
                </td>
                </tr>';
            }
        } else {
            $result .= '<tr>
                            <td align="right">'.$title.': </td>
                            <td><input type="file" value="" name="'.$strHTMLControlName['NAME'].'['.$code.']"/></td>
                        </tr>';
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
        if (
            !empty($arValue['VALUE'][$code]) 
            && !is_array($arValue['VALUE'][$code])
        ) {
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
     * Сформировать HTML-код для поля-визуального редактора.
     * @param string $code Код текущего элемента.
     * @param string $title Название поля.
     * @param array $arUserField Параметры пользовательского поля.
     * @param array $arHtmlControl Имена элементов управления и значения.
     * @return string HTML поля-визуального редактора.
     */
    private static function showHtmlElement($code, $title, $arUserField, $arHtmlControl)
    {
        $result = '';
        /*
         * Получить значение свойства для данного поля
         * либо установить пустое значение. 
         */

        $v = !empty($arHtmlControl['VALUE'][$code]) ? $arHtmlControl['VALUE'][$code] : '';
        
        $htmlEditorHeight = 80;
        /*
         * Создать имена для полей-редакторов с заменой скобок на подчеркивания.
         */
        $name = $arHtmlControl['NAME'] . '[' . $code . ']';
        $name = preg_replace("/[\[\]]/i", "_", $name);
        /*
         * Начать буферизацию, чтобы получить HTML-код редактора. 
         */
        ob_start();
        /*
         * Вывести визуальный редактор. 
         */
        CFileMan::AddHTMLEditorFrame(
            $name,
            $v,
            $code . "_TYPE",
            "html",
            array(
                'height' => $htmlEditorHeight,
            )
        );

        echo '<input type="hidden" name="'.$arHtmlControl["NAME"] . '[' . $code . ']' .'" >';

        /*
         * Получить значение из буфера и закрыть буфер. 
         */
        $tempResult .= ob_get_contents();
        ob_end_clean();

        $result .= '<tr>' . 
                        '<td align="right">' . $title . ': </td>' .
                        '<td>' . $tempResult . '</td>' .
                    '</tr>';

        return $result;
    }

    /**
     * Сформировать HTML-код для многострочного текстового поля.
     * @param string $code Символьный код поля.
     * @param string $title Название поля.
     * @param array $arValue Значения полей свойства.
     * @param array $strHTMLControlName Имена элементов управления.
     * @return string HTML многострочного текстового поля.
     */
    public static function showTextarea($code, $title, $arValue, $strHTMLControlName)
    {
        $result = '';

        $v = !empty($arValue['VALUE'][$code]) ? $arValue['VALUE'][$code] : '';
        $result .= '<tr>
                <td align="right" valign="top">'.$title.': </td>
                <td><textarea rows="8" name="'.$strHTMLControlName['NAME'].'['.$code.']">'.$v.'</textarea></td>
            </tr>';

        return $result;
    }

    /**
     * Сформировать HTML-код для поля-даты.
     * @param string $code Символьный код поля.
     * @param string $title Название поля.
     * @param array $arValue Значения полей свойства.
     * @param array $strHTMLControlName Имена элементов управления.
     * @return string HTML поля-даты.
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
                                                <input class="adm-input adm-input-calendar" type="text" name="' . $strHTMLControlName['NAME'] . '[' . $code . ']" size="23" value="' . $v . '">
                                                <span class="adm-calendar-icon"
                                                    onclick="BX.calendar({node: this, field:\'' . $strHTMLControlName['NAME'] . '[' . $code . ']\', form: \'\', bTime: true, bHideTime: false});"></span>
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
     * @param array $strHTMLControlName Имена элементов управления.
     * @return string HTML поля-привязки к элементу.
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
                            <input name="' . $strHTMLControlName['NAME'] . '[' . $code . ']" id="' . $strHTMLControlName['NAME'] . '[' . $code . ']" value="' . $v . '" size="8" type="text" class="mf-inp-bind-elem">
                            <input type="button" value="..." onClick="jsUtils.OpenWindow(\'/bitrix/admin/iblock_element_search.php?lang=ru&IBLOCK_ID=0&n=' . $strHTMLControlName['NAME'] . '&k=' . $code . '\', 900, 700);">&nbsp;
                            <span>' . $elUrl . '</span>
                        </td>
                    </tr>';

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
                        preg_match("/\w+_([0-9]+)__" . $arFieldCode . "_$/i",
                        $keyFromPost, 
                        $m) 
                    ) {
                        $arValue[$arFieldCode] = $valFromPost;
                        unset($_POST[$keyFromPost]);
                        break;
                    }
                }
            }
        }
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
            'string' => Loc::getMessage('COMPLEX_USER_FIELD_SETTINGS_FIELD_TYPE_STRING'),
            'text' => Loc::getMessage('COMPLEX_USER_FIELD_SETTINGS_FIELD_TYPE_TEXT'),
            'date' => Loc::getMessage('COMPLEX_USER_FIELD_SETTINGS_FIELD_TYPE_DATE'),
            'element' => Loc::getMessage('COMPLEX_USER_FIELD_SETTINGS_FIELD_TYPE_ELEMENT'),
            'html' => Loc::getMessage('COMPLEX_USER_FIELD_SETTINGS_FIELD_TYPE_HTML'),
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
             * Если выбрано комплексное поле, то установить его множественность.
             */
            $(document).ready(function() {
                var multyCheckBox = $("input[name='MULTIPLE']");
                $(multyCheckBox).attr('checked', 'checked');
            });
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
     * Отображение стилей CSS.
     */
    private static function showCss()
    {
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
        $showText = Loc::getMessage('COMPLEX_USER_FIELD_FORM_SHOW_TEXT');
        $hideText = Loc::getMessage('COMPLEX_USER_FIELD_FORM_HIDE_TEXT');

        CJSCore::Init(array("jquery"));
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
     * Получить расширение указанного файла.
     * @param string $filePath Путь до файла.
     * @return string Расширение файла.
     */
    private static function getExtension($filePath)
    {
        return array_pop(explode('.', $filePath));
    }
}
