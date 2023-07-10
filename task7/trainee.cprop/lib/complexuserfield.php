<?php

use \Bitrix\Main\Localization\Loc;

class CComplexUserField extends \Bitrix\Main\UserField\Types\StringType
{
    /**
     * Метод-обработчик для задания нового типа пользовательского поля
     * с визуальным HTML-редактором.
     * @return array Параметры нового пользовательского типа.
     */
	public static function GetUserTypeDescription(): array
	{
        print_r("It works!!!");
        die();

		return array(
            /*
             * Идентификатор пользовательского типа. 
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
        // TODO
    }

    /**
     * Преобразование значения для его сохранения.
     * @param array $arUserField Параметры поля.
     * @param string $value Значение поля.
     * @return string Значение поля.
     */
	public static function OnBeforeSave($arUserField, $value)
    {
        // TODO
    }
}
