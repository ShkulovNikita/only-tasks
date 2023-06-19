<?php

\Bitrix\Main\Loader::includeModule('iblock');

/**
 * Parser - класс для парсинга вакансий из CSV-файла и заполнения 
 * элементов инфоблока вакансий.
 */
class Parser 
{
    /**
     * Идентификатор инфоблока вакансий.
     * @var int
     */
    private $IBLOCK_ID;

    /**
     * Массив имеющихся значений в списочных свойствах.
     * @var array
     */
    private $arProperties = [];

    /**
     * Объект элемента инфоблока для добавления/удаления элементов инфоблока.
     * @var CIBlockElement
     */
    private $iblockEl;

    /**
     * Конструктор для задания используемого инфоблока.
     * @param int $IBLOCK_ID Идентификатор инфоблока вакансий в системе.
     */
    public function __construct($IBLOCK_ID)
    {
        $this->IBLOCK_ID = $IBLOCK_ID;
        $this->iblockEl = new CIBlockElement;
    }

    /**
     * Выполнить парсинг CSV-файла.
     * @param string $file Путь до файла.
     */
    public function parseCsv($file)
    {
        // Заполнить вспомогательный массив со значениями списков.
        $this->getListValues();
        // Удалить имеющиеся элементы инфоблока.
        $this->deleteIblocks();

        // Открыть и построчно считать CSV-файл.
        if (($handle = fopen($file, "r")) !== false) {
            $row = 1;
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                // Пропустить заголовок CSV-файла.
                if ($row == 1) {
                    $row++;
                    continue;
                }
                $row++;

                // Массив со значениями свойств, полученных из очередной строки.
                $arValuesForParsing = $this->fillProperties($data);

                // Обработать полученные значения.
                foreach ($arValuesForParsing as $key => &$value) {
                    $value = trim($value);
                    $value = str_replace('\n', '', $value);
                    // Является текстом с пунктами.
                    if (stripos($value, '•') !== false) {
                        // Разбить текст по пунктам на массив.
                        $value = explode('•', $value);
                        // Удалить первый (пустой) элемент массива.
                        array_splice($value, 0, 1);
                        // Убрать лишние пробелы из пунктов.
                        foreach ($value as &$str) {
                            $str = trim($str);
                        }
                    // Является значением для списочного свойства.
                    } elseif ($this->arProperties[$key]) {
                        // Сопоставить с уже существующим списочным значением либо создать новое.
                        $this->matchListProperty($key, $value);
                    }
                }
                
                // Получить значение зарплаты и её тип.
                $this->parseSalary($arValuesForParsing);
                // Добавить элемент инфоблока.
                $this->addIblock($arValuesForParsing, $data);
            }

            fclose($handle);
        }
    }

    /**
     * Получить уже существующие в системе возможные значения.
     * списочных свойств и сохранить их в массив $this->arProperties.
     */
    private function getListValues()
    {
        // Все текущие возможные значения списков.
        $listPropsValues = CIBlockPropertyEnum::GetList(
            ["SORT" => "ASC", "VALUE" => "ASC"],
            ['IBLOCK_ID' => $this->IBLOCK_ID]
        );

        // Сохранить в массив $arProperties идентификаторы значений 
        // под соответствующими кодами.
        while ($arProp = $listPropsValues->Fetch()) {
            $key = trim($arProp['VALUE']);
            $this->arProperties[$arProp['PROPERTY_CODE']][$key] = $arProp['ID'];
        }
    }

    /**
     * Удалить все существующие элементы текущего инфоблока.
     */
    private function deleteIblocks()
    {
        // Получить имеющиеся элементы инфоблока.
        $currentElements = CIBlockElement::GetList(
            [], 
            ['IBLOCK_ID' => $this->IBLOCK_ID], 
            false, 
            false, 
            ['ID']
        );

        // Удалить.
        while ($element = $currentElements->GetNext()) {
            CIBlockElement::Delete($element['ID']);
        }  
    }

    /**
     * Распарсить строку по свойствам инфоблока.
     * @param array $arData Массив данных из одной строки CSV-файла.
     * @return array Ассоциативный массив с данными из файла и ключами, 
     * соответствующими свойствам инфоблока.
     */
    private function fillProperties($arData)
    {
        $arProps['OFFICE'] = $arData[1];
        $arProps['LOCATION'] = $arData[2];
        // Нет в инфоблоке, пропускается и используется в дальнейшем как имя.
        // $arProps['POSITION'] = $arData[3];
        $arProps['REQUIRE'] = $arData[4];
        $arProps['DUTY'] = $arData[5];
        $arProps['CONDITIONS'] = $arData[6];
        // Заполняется в зависимости от значения в столбце $arData[7].
        $arProps['SALARY_TYPE'] = '';
        $arProps['SALARY_VALUE'] = $arData[7];
        $arProps['TYPE'] = $arData[8];
        $arProps['ACTIVITY'] = $arData[9];
        $arProps['SCHEDULE'] = $arData[10];
        $arProps['FIELD'] = $arData[11];
        $arProps['EMAIL'] = $arData[12];
        // В DATE записать текущую дату.
        $arProps['DATE'] = date('d.m.Y');

        return $arProps;
    }

    /**
     * Cопоставить значение, полученное из файла, с одним из существующих списочных значений
     * либо создать для него новое.
     * @param string $key Символьный код свойства.
     * @param string $valueFromFile Значение свойства, полученное из файла.
     */
    private function matchListProperty($key, &$valueFromFile)
    {
        // Попробовать найти точно совпадающее с полученным из файла значение свойства.
        if ($this->findExactPropertyMatch($key, $valueFromFile) === false) {
            // Если не удалось, то найти похожее.
            if ($this->findSimilarPropertyMatch($key, $valueFromFile) === false) {
                // Если все ещё не найдено, то добавить новое значение в список.
                $this->addListPropertyValue($key, $valueFromFile);
            }
        }
    }

    /**
     * Попробовать найти точно совпадающее значение свойства (из имеющихся в системе) 
     * с полученным из файла.
     * @param string $key Символьный код свойства.
     * @param string $valueFromFile Значение свойства, полученное из файла.
     * @return bool True - совпадение было найдено, false - не найдено.
     */
    private function findExactPropertyMatch($key, &$valueFromFile)
    {
        foreach ($this->arProperties[$key] as $propertyKey => $propertyValueID) {
            // Если такое списочное значение уже есть,
            // то отдать соответствующий идентификатор списочного значения
            if (mb_stripos($propertyKey, $valueFromFile) !== false) {
                $valueFromFile = $propertyValueID;
                return true;
            } 
        }

        return false;
    }

    /**
     * Попробовать найти похожее значение свойства (из имеющихся в системе) 
     * с полученным из файла.
     * @param string $key Символьный код свойства.
     * @param string $valueFromFile Значение свойства, полученное из файла.
     * @return bool True - совпадение было найдено, false - не найдено.
     */
    private function findSimilarPropertyMatch($key, &$valueFromFile)
    {
        // Насколько должны быть похожи некоторое существующее значение свойства и значение из файла.
        $similarityLimitPercent = 90;

        foreach ($this->arProperties[$key] as $propertyKey => $propertyValueID) {
            $similarity = similar_text($propertyKey, $valueFromFile, $perc);
            if ($perc > 90) {
                $valueFromFile = $propertyValueID;
                return true;
            }
        }

        return false;
    }

    /**
     * Добавить новое значение в списочное свойство.
     * @param string $key Символьный код свойства.
     * @param string $valueFromFile Значение свойства, полученное из файла.
     */
    private function addListPropertyValue($key, &$valueFromFile)
    {
        // Значение должно быть не слишком коротким (например, дефис-"прочерк") или пустым.
        if (mb_strlen($valueFromFile, 'utf-8') > 2) {
            $newValueID = $this->createListPropertyValue($key, $valueFromFile);
            if ($newValueID !== false) {
                $this->arProperties[$key][$valueFromFile] = $newValueID;
                $valueFromFile = $newValueID;
            }
        }
    }

    /**
     * Создание нового списочного значения свойства.
     * @param string $propertyCode Символьный код свойства.
     * @param string $newListValue Добавляемое значение свойства.
     * @return int|bool Идентификатор добавленного значения свойства либо false,
     * если добавить не удалось.
     */
    private function createListPropertyValue($propertyCode, $newListValue)
    {
        // Получить ID свойства по его коду.
        $property = CIBlockProperty::GetByID($propertyCode, $this->IBLOCK_ID)->GetNext();
        $propertyID = $property['ID'];

        // Добавить новое значение в список.
        $propertyValues = new CIBlockPropertyEnum;
        $newValueID = $propertyValues->Add(
            Array(
                'PROPERTY_ID' => $propertyID, 
                'VALUE' => $newListValue,
                'XML_ID' => CUtil::translit($newListValue, 'ru', Array('change_case' => "U"))
            )
        );

        // Если удалось добавить новое значение, вернуть его идентификатор.
        if ($newValueID) {
            return $newValueID;
        } else {
            return false;
        }
    }

    /**
     * Обработать значение, полученное для свойств зарплаты.
     * @param array $arPropsFromFile Массив значений для инфоблока с данными,
     * полученными из CSV-файла.
     */
    private function parseSalary(&$arPropsFromFile)
    {
        // Если зарплата содержит только "прочерк", то сделать её пустой.
        if ($arPropsFromFile['SALARY_VALUE'] == '-') {
            $arPropsFromFile['SALARY_VALUE'] = '';
        // Если зарплата "по договоренности", то тип зарплаты - "Договорная" из списка свойства.
        } elseif ($arPropsFromFile['SALARY_VALUE'] == 'по договоренности') {
            $arPropsFromFile['SALARY_VALUE'] = '';
            $arPropsFromFile['SALARY_TYPE'] = $this->arProperties['SALARY_TYPE']['Договорная'];
        } else {
            $arSalary = explode(' ', $arPropsFromFile['SALARY_VALUE']);
            // Вариант задания зарплаты "от/до".
            if ($arSalary[0] == 'от' || $arSalary[0] == 'до') {
                // Установить соответствующий тип зарплаты.
                $PROP['SALARY_TYPE'] = $arProps['SALARY_TYPE'][$arSalary[0]];
                // Убрать из массива элемент "от/до".
                array_splice($arSalary, 0, 1);
                // Собрать массив в строку-зарплату.
                $PROP['SALARY_VALUE'] = implode(' ', $arSalary);
            // Указано точное значение зарплаты.
            } else {
                $PROP['SALARY_TYPE'] = $arProps['SALARY_TYPE']['='];
            }
        }
    }

    /**
     * Добавить элемент инфоблока на основе полученных из файла данных.
     * @param array $arPropValues Значения свойств добавляемого элемента инфоблока.
     * @param array $data Массив значений из одной (текущей) строки CSV-файла.
     */
    private function addIblock($arPropValues, $data) 
    {
        // Заполнить элемент инфоблока.
        global $USER;
        $arVacancyIblock = [
            "MODIFIED_BY" => $USER->GetID(),
            "IBLOCK_SECTION_ID" => false,
            "IBLOCK_ID" => $this->IBLOCK_ID,
            "PROPERTY_VALUES" => $arPropValues,
            "NAME" => $data[3] != "" ? $data[3] : "Без названия",
            "ACTIVE" => end($data) ? 'Y' : 'N',
        ];
        
        // Сохранить элемент инфоблока.
        if ($vacancyID = $this->iblockEl->Add($arVacancyIblock)) {
            echo "Добавлен элемент с ID: " . $vacancyID . "<br>";
        } else {
            echo "Ошибка: " . $this->iblockEl->LAST_ERROR . "<br>";
        }
    }
}
