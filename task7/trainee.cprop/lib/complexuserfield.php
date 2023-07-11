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
            /*
             * Идентификатор пользовательского поля. 
             */
			"USER_TYPE_ID" => "complexhtml",
            /*
             * Класс-обработчик (данный класс). 
             */
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
        return self::getEditRowHtml($arUserField, $arHtmlControl);
    }

    /**
     * @param $arUserField
     * @param $arHtmlControl
     * @return mixed
     */
    public static function getEditRowHtml($arUserField, $arHtmlControl)
    {
        /*
         * Свернуть/удалить. 
         */
        $hideText = Loc::getMessage('IEX_COMPLEX_PROP_HIDE_TEXT');
        $clearText = Loc::getMessage('IEX_COMPLEX_PROP_CLEAR_TEXT');
        

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
     * @param string $value Значение поля.
     * @return string Значение поля.
     */
	public static function OnBeforeSave($arUserField, $value): string
    {
        var_dump($_POST['NAME']);
        echo "<br>--------<br>";
        $nameOfField = $arUserField['FIELD_NAME'];
        var_dump($nameOfField);
        echo "<br>--------<br>";
        // UF_TESTING_COMPLEX_FIELD
        print_r($_POST);
        echo "<br>--------<br>";
        var_dump($_POST[$nameOfField]);

        if (isset($_POST[$arUserField['FIELD_NAME']])) {
            $value = $_POST[$arUserField['FIELD_NAME']];
        }

        $value = \Bitrix\Main\Web\Json::encode($value);
        die();

        return $value;
    }

    /**
     * @param array $arUserField
     * @param array $value
     * @return array
     */
    public static function CheckFields(array $userField, $value): array
    {
        $aMsg = array();
        return $aMsg;
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
            'file' => Loc::getMessage('COMPLEX_USER_FIELD_SETTINGS_FIELD_TYPE_FILE'),
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

    /*
    public static function onAfterFetch($arProperty, $arValue): array
    {
        if (!empty($arValue["VALUE"])) {
            $arValue = \Bitrix\Main\Web\Json::decode(html_entity_decode($arValue["VALUE"]));
        }
        print_r($arValue);
        echo " BRRRRRRRR ";
        print_r($arProperty);
        return $arValue;
    }*/
}
