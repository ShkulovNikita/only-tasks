<?
IncludeModuleLangFile(__FILE__);

class CCustomTypeHtml extends \Bitrix\Main\UserField\Types\StringType
{
    /**
     * Метод-обработчик для задания нового типа пользовательского поля
     * с визуальным HTML-редактором.
     * @return array Параметры нового пользовательского типа.
     */
	public static function GetUserTypeDescription(): array
	{
		return array(
            /*
             * Идентификатор пользовательского типа. 
             */
			"USER_TYPE_ID" => "customhtml",
            /*
             * Класс-обработчик (данный класс). 
             */
			"CLASS_NAME" => "CCustomTypeHtml",
			"DESCRIPTION" => GetMessage("PPROP_NAME"),
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
         * ENTITY_VALUE_ID - идентификатор элемента сущности, значение поля которого редактируется;
         * DEFAULT_VALUE - Значение поля по умолчанию.
         * Задать значение пользовательского поля по умолчанию.
         */
		if (
            $arUserField["ENTITY_VALUE_ID"] < 1 
            && strlen($arUserField["SETTINGS"]["DEFAULT_VALUE"]) > 0
        ) {
            $arHtmlControl["VALUE"] = htmlspecialcharsbx($arUserField["SETTINGS"]["DEFAULT_VALUE"]);
        }
        /*
         * ROWS - Количество строчек поля ввода.
         * В данном модуле это множитель для высоты поля ввода.
         * Указывается минимальная высота по умолчанию, если нужно. 
         */
		if ($arUserField["SETTINGS"]["ROWS"] < 8) {
            $arUserField["SETTINGS"]["ROWS"] = 8;
        }
        /*
         * Если поле множественное, то заменить спецсимволы в имени поля
         * на символ подчеркивания.
         */
		if ($arUserField['MULTIPLE'] == 'Y') {
            $name = preg_replace("/[\[\]]/i", "_", $arHtmlControl["NAME"]);
        } else {
            $name = $arHtmlControl["NAME"];
        }
		/*
         * Начать буферизацию для получения HTML-кода компонента редактора.
         */
		ob_start();
		/*
         * Вывести (в буфер) визуальный редактор.
         */
		CFileMan::AddHTMLEditorFrame(
            /*
             * Имя текущего пользовательского поля. 
             */
			$name,
            /*
             * Уже введенное значение, если есть. 
             */
			$arHtmlControl["VALUE"],
            /*
             * Имя типа поля имя_TYPE.
             */
			$name."_TYPE",
            /*
             * Тип введенного текста (?): html либо обычный текст. 
             */
			strlen($arHtmlControl["VALUE"]) ? "html" : "text",
            /*
             * Высота поля ввода. 
             */
			array(
				'height' => $arUserField['SETTINGS']['ROWS'] * 10,
			)
		);
		/*
         * Если поле множественное, то также нужно добавить скрытое поле с именем поля. 
         */
		if ($arUserField['MULTIPLE'] == 'Y') {
            echo '<input type="hidden" name="'.$arHtmlControl["NAME"].'" >';
        }
		/*
         * Получить значение из буфера и закрыть буфер. 
         */
		$html = ob_get_contents();
		ob_end_clean();

		return $html; 
	}

    /**
     * Преобразование значения для его сохранения.
     */
	public static function OnBeforeSave($arUserField, $value)
    {
        /*
         * Если значение множественное, то перебрать все значения POST-запроса. 
         */
		if ($arUserField['MULTIPLE'] == 'Y') {
			foreach ($_POST as $key => $val) {
                /*
                 * Ключ значения, полученного из POST-запроса, 
                 * должно содержать имя пользовательского поля и число.
                 */
				if (preg_match("/".$arUserField['FIELD_NAME']."_([0-9]+)_$/i", $key, $m)) {
                    /*
                     * Если найдено подходящее значение, 
                     * то сохранить его в $value и закончить цикл. 
                     */
					$value = $val;
					unset($_POST[$key]);
					break;
				}
			}
		}
        return $value;
    }
}
?>
