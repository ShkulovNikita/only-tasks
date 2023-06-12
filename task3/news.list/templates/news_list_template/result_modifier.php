<?php
// получить список разделов новостей
$sectionsData = CIBlockSection::GetList(
    Array("SORT" => "ASC"),
    Array("IBLOCK_ID" => $arParams['IBLOCK_ID'])
);

// преобразовать полученный объект в массив
while ($section = $sectionsData->GetNext()) {
    $sections[] = $section;
}

// сохранить в arResult для доступа к массиву разделов из шаблона
$arResult["SECTIONS"] = $sections;
