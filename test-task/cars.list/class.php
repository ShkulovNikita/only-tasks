<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Context,
    Bitrix\Main\Type\DateTime,
    Bitrix\Main\Loader,
    Bitrix\Main\Entity,
    Bitrix\Iblock,
    Bitrix\Highloadblock as HL;

class CCarsList extends CBitrixComponent
{
    /**
     * Имя GET-параметра, хранящего значение начала поездки.
     * @var string
     */
    private $startTimeParam = 'start_time';
    /**
     * Имя GET-параметра, хранящего значение конца поездки.
     * @var string
     */
    private $endTimeParam = 'end_time';
    /**
     * Массив результата работы компонента, передаваемый в шаблон.
     * @var array
     */
    public $arResult;
    /**
     * Дата-время начала поездки.
     * @var Bitrix\Main\Type\DateTime
     */
    private $startTime;
    /**
     * Дата-время окончания поездки.
     * @var Bitrix\Main\Type\DateTime
     */
    private $endTime;
    /**
     * Должность текущего пользователя.
     * @var string
     */
    private $userPosition;
    /**
     * Символьные коды/названия используемых инфоблоков и хайлоад-блоков.
     * @var array
     */
    private $codes = [
        /*
         * Инфоблоки. 
         */
        'job_cars' => 'job_cars',
        'job_cars_drivers' => 'job_cars_drivers',
        /*
         * Хайлоад-блоки. 
         */
        'positions' => 'Positions',
        'car_brands' => 'CarBrands',
        'car_models' => 'CarModels',
        'job_car_bookings' => 'JobCarBookings',
        'comfortability_categories' => 'ComfortabilityCategories',
        'comfortability_availability' => 'ComfortabilityAvailability',
    ];

    /**
     * Код, выполняемый при вызове компонента.
     */
    public function executeComponent()
    {
        /*
         * Получить значения основных параметров компонента:
         * начало и конец поездки, должность текущего пользователя.
         */
        $this->prepareParameters();
        /*
         * Получить доступные по времени автомобили.
         */
        if ($this->startTime && $this->endTime && $this->userPosition) {
            /*
             * Получить доступные пользователю служебные автомобили, их бренды, модели, категории комфорта и водителей.
             */
            $this->findCars($comfortCategories, $carModels, $availableCars, $carBrands, $carDrivers);
            /*
             * Сформировать массив результата работы компонента. 
             */
            $this->makeArResult($comfortCategories, $carModels, $availableCars);
        }
        /*
         * Подключить шаблон компонента.
         */
        $this->IncludeComponentTemplate();
    }

    /**
     * Создать массив результата работы компонента arResult.
     */
    private function prepareParameters()
    {
        /*
         * Получить выбранное время начала и окончания поездки.
         */
        $this->startTime = $this->getCarDriveDateTime($this->startTimeParam);
        $this->endTime = $this->getCarDriveDateTime($this->endTimeParam);
        if (!$this->startTime || !$this->endTime) {
            return;
        }
        /*
         * Проверить, что время окончания поездки позже начала. 
         */
        $checkOrderResult = $this->checkDriveOrder($this->startTime, $this->endTime);
        if ($checkOrderResult === false) {
            ShowError(GetMessage('T_JOB_CARS_START_END_TIME_INCORRECT_ORDER_ERROR'));
            return;
        }
        /**
         * Получить должность текущего пользователя.
         */
        global $USER;
        if (isset($GLOBALS['USER']) && is_object($GLOBALS['USER']) && $GLOBALS['USER']->IsAuthorized()) {
            $this->userPosition = $this->getUserPosition();
            if ($this->userPosition == '') {
                ShowError(GetMessage('T_JOB_CARS_POSITION_REQUIRED_ERROR'));
                return;
            }
        } else {
            ShowError(GetMessage('T_JOB_CARS_LOGIN_REQUIRED_MESSAGE'));
            return;
        }
    }

    /**
     * Получить данные о доступных пользователю служебных автомобилях, их категориях комфорта и моделях.
     * @param array $comfortCategories Массив для категорий комфорта.
     * @param array $carModels Массив для доступных пользователю моделей автомобилей.
     * @param array $availableCars Массив доступных пользователю служебных автомобилей.
     * @param array $carBrands Массив марок доступных автомобилей.
     * @param array $carDrivers Массив водителей доступных автомобилей.
     */
    private function findCars(&$comfortCategories, &$carModels, &$availableCars, &$carBrands, &$carDrivers)
    {
        /*
         * Идентификатор должности текущего пользователя в хайлоад-справочнике. 
         */
        $positionId = $this->findPositionInDirectory();
        /*
         * Определить, какие категории комфорта доступны пользователю согласно его должности. 
         */
        $comfortCategories = $this->getComfortCategories($positionId);
        if ($comfortCategories == []) {
            ShowError(GetMessage('T_JOB_CARS_NO_COMFORT_CATEGORY_OF_USER_ERROR'));
            return [];
        }
        /*
         * Получить массив идентификаторов.
         */
        $comfortIds = array_column($comfortCategories, 'ID');
        /*
         * Получить модели автомобилей, соответствующие указанным категориям комфорта.
         */
        $carModels = $this->getAvailableCarModels($comfortIds);
        /*
         * Получить идентификаторы автомобилей, занятых на период, выбранный пользователем.
         */
        $takenCarIds = $this->getTakenCars();
        /*
         * Получить автомобили, доступные текущему пользователю. 
         */
        $carModelsIds = array_column($carModels, 'UF_XML_ID');
        $availableCars = $this->getAvailableCars($takenCarIds, $carModelsIds);
        /*
         * Получить их бренды.
         */
        $carBrandsIds = array_column($carModels, 'UF_CAR_BRAND_OF_MODEL');
        $carBrands = $this->getCarBrands($carBrandsIds);
        /*
         * Получить водителей. 
         */
        $driversIds = $this->getDriverIds($availableCars);
        $carDrivers = $this->getCarDrivers($driversIds);

        $this->arResult['brands'] = $carDrivers;
    }

    /**
     * Сформировать массив результата работы компонента.
     * @param array $comfortCategories Массив категорий комфорта.
     * @param array $carModels Массив доступных пользователю моделей автомобилей.
     * @param array $availableCars Массив доступных пользователю служебных автомобилей.
     */
    private function makeArResult($comfortCategories, $carModels, $availableCars)
    {
        //TODO
    }

    /**
     * Получить и проверить дату-время начала или конца поездки (в часовом поясе сервера).
     * @param string $param Имя GET-параметра с временем 
     * (также указывает, является ли данное время началом или концом поездки)
     * @return Bitrix\Main\Type\DateTime|bool Дата-время начала или конца поездки либо false.
     */
    private function getCarDriveDateTime($param)
    {
        /*
         * Получить время из GET-параметра.
         */
        $time = $this->getCarDriveTime($param);
        if ($time === false) {
            ShowError(GetMessage('T_JOB_CARS_EMPTY_' . strtoupper($param) . '_ERROR'));
            return false;
        }
        /*
         * Произвести валидацию.
         */
        $validateResult = $this->validateDateTime($time);
        if ($validateResult === false) {
            ShowError(GetMessage('T_JOB_CARS_INCORRECT_DATE_FORMAT_' . strtoupper($param) . '_ERROR'));
            return false;
        }
        /*
         * Получить выбранное время в часовом поясе сервера.
         */
        $timeOnServer = $this->createServerDate($time);
        if ($timeOnServer === false) {
            ShowError(GetMessage('T_JOB_CARS_CONVERT_' . strtoupper($param) . '_TO_SERVER_ERROR'));
            return false;
        }
        /*
         * Проверить, что выбранное время не меньше текущего времени. 
         */
        $checkLimitResult = $this->checkTimeLimit($timeOnServer);
        if ($checkLimitResult === false) {
            ShowError(GetMessage('T_JOB_CARS_' . strtoupper($param) . '_IS_EARLIER_THAN_CURRENT_ERROR'));
            return false;
        }

        return $timeOnServer;
    }

    /**
     * Получить время поездки из GET-параметра.
     * @param string $param Имя GET-параметра с временем.
     * @return string|bool Время начала или конца поездки либо false.
     */
    private function getCarDriveTime($param)
    {
        /*
         * Проверить на пустоту GET-параметр с временем.
         */
        if (!isset($_GET[$param]) || empty($_GET[$param])) {
            return false;
        } else {
            return htmlspecialchars($_GET[$param]);
        }
    }

    /**
     * Произвести валидацию времени.
     * @param string $time Время поездки.
     * @return bool Результат прохождения валидации.
     */
    private function validateDateTime($time)
    {
        /*
         * Попробовать создать тестовую дату с указанным временем. 
         */
        $testTime = "01.01.2023 " . $time;
        $tryDate = \DateTime::createFromFormat('d.m.Y H:i', $testTime);
        if ($tryDate !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Получить дату-время поездки в серверном времени.
     * @param string $time Время поездки.
     * @return Bitrix\Main\Type\DateTime|bool Дата-время поездки в серверном времени.
     */
    private function createServerDate($time)
    {
        /*
         * Получить текущее локальное время пользователя.
         */
        $userTime = new Bitrix\Main\Type\DateTime();
        $userTime = $userTime->toUserTime();
        /*
         * Задать время поездки в данную дату.
         */
        try {
            $parts = explode(':', $time);
            $userTime = $userTime->setTime(intval($parts[0]), intval($parts[1]));
            
            /**
             * Перевести время в timestamp, чтобы учесть разницу часовых поясов.
             */
            $userTimeStamp = $userTime->getTimestamp();
            /*
             * Перевод в серверное время. 
             */
            $serverTimeStamp = $userTimeStamp - CTimeZone::GetOffset();
            /*
             * Создать объект для серверного времени. 
             */
            $serverTime = \Bitrix\Main\Type\DateTime::createFromTimestamp($serverTimeStamp);
            
            return $serverTime;
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Проверить, что выбранное время не меньше текущего.
     * @param Bitrix\Main\Type\DateTime $time Выбранное время поездки в серверном часовом поясе.
     * @return bool Результат проверки: true - пройдена, false - не пройдена.
     */
    private function checkTimeLimit($time)
    {
        /*
         * Получить текущее время. 
         */
        $currentTime = new Bitrix\Main\Type\DateTime();

        if ($time->getTimestamp() < $currentTime->getTimestamp()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Проверить, что время окончания поездки не раньше начала.
     * @param Bitrix\Main\Type\DateTime $startTime Время начала поездки.
     * @param Bitrix\Main\Type\DateTime $endTime Время окончания поездки.
     * @return bool true - правильный порядок, false - неправильный.
     */
    private function checkDriveOrder($startTime, $endTime)
    {
        if ($startTime->getTimestamp() > $endTime->getTimestamp()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Получить должность текущего пользователя.
     * @return string|bool Должность текущего пользователя, пустая строка либо false,
     * если произошла ошибка.
     */
    private function getUserPosition()
    {
        try {
            /*
             * Выполнить запрос на получение объекта пользователя с текущим ID. 
             */
            $userQuery = CUser::GetByID($GLOBALS['USER']->GetID());
            $arUser = $userQuery->Fetch();

            if (isset($arUser['WORK_POSITION']) && !empty($arUser['WORK_POSITION'])) {
                return $arUser['WORK_POSITION'];
            } else {
                return '';
            }
        } catch(Exception $ex) {
            ShowError(GetMessage('T_JOB_CARS_CANT_GET_USER_DATA_ERROR'));
            return false;
        }
    }

    /**
     * Получить идентификатор должности текущего пользователя в справочнике должностей.
     * @return int|string Идентификатор должности в справочнике либо пустая строка.
     */
    private function findPositionInDirectory()
    {
        /*
         * Получить имя класса хайлоад-блока должностей для выполнения запросов. 
         */
        $positionsHlblockName = $this->getHlblockByName($this->codes['positions']);
        if ($positionsHlblockName === false) {
            return '';
        }
        /*
         * Выполнить запрос с фильтрацией по названию должности пользователя. 
         */
        $resPositions = $positionsHlblockName::getList(
            [
                'select' => ['ID'],
                'order' => ['ID' => 'ASC'],
                'filter' => ['UF_NAME' => $this->userPosition]
            ]
        )->fetchAll();
        /*
         * Если в справочнике найдена профессия текущего пользователя, то сохранить идентификатор.
         */
        if (count($resPositions) > 0) {
            return $resPositions[0]['ID'];
        } else {
            return '';
        }
    }

    /**
     * Получить категории комфорта согласно указанной должности.
     * @param int $positionId Идентификатор должности пользователя либо пустая строка.
     * @return array Список категорий комфорта, доступных пользователю.
     */
    private function getComfortCategories($positionId)
    {
        /*
         * Если идентификатор должности пустой, то должность пользователя не содержится в справочнике.
         * В этом случае получить самую низкую категорию комфорта. 
         */
        if ($positionId == '') {
            return $this->getComfortCategoryByName('Третья');
        } else {
            /*
             * Если идентификатор должности указан, то найти записи о сопоставлении должностей и категорий комфорта. 
             */
            $categoryIds = $this->getComfortCategoryIds($positionId);
            if (count($categoryIds) > 0) {
                return $this->getComfortCategoriesByIds($categoryIds);
            } else {
                return [];
            }
        }
    }

    /**
     * Получить указанную категорию комфорта.
     * @param string $categoryName Имя категории.
     * @return array Категория комфорта с указанным именем.
     */
    private function getComfortCategoryByName($categoryName)
    {
        $comfortHlBlockName = $this->getHlblockByName($this->codes['comfortability_categories']);
        $resComfCategories = $comfortHlBlockName::getList(
            [
                'select' => ['*'],
                'order' => ['ID' => 'ASC'],
                'filter' => ['UF_NAME' => $categoryName]
            ]
        )->fetchAll();
        if (count($resComfCategories) > 0) {
            return $resComfCategories;
        } else {
            return [];
        }
    }

    /**
     * Получить категории комфорта по их идентификаторам.
     * @param array $categoryIds Массив идентификаторов категорий комфорта.
     * @return array Массив доступных пользователю категорий комфорта.
     */
    private function getComfortCategoriesByIds($categoryIds)
    {
        $comfortHlBlockName = $this->getHlblockByName($this->codes['comfortability_categories']);
        $resComfCategories = $comfortHlBlockName::getList(
            [
                'select' => ['*'],
                'order' => ['ID' => 'ASC'],
                'filter' => ['ID' => $categoryIds]
            ]
        )->fetchAll();
        if (count($resComfCategories) > 0) {
            return $resComfCategories;
        } else {
            return [];
        }
    }
    
    /**
     * Получить модели автомобилей указанных категорий комфорта.
     * @param array $comfortIds Идентификаторы категорий комфорта.
     * @return array Доступные модели автомобилей.
     */
    private function getAvailableCarModels($comfortIds)
    {
        $carModelsHlBlockName = $this->getHlblockByName($this->codes['car_models']);
        $resCarModels = $carModelsHlBlockName::getList(
            [
                'select' => ['*'],
                'order' => ['ID' => 'ASC'],
                'filter' => ['UF_CAR_COMFORTABILITY_OF_MODEL' => $comfortIds]
            ]
        )->fetchAll();
        if (count($resCarModels) > 0) {
            return $resCarModels;
        } else {
            return [];
        }
    }

    /**
     * Получить идентификаторы доступных категорий комфорта для указанной должности.
     * @param int $positionId Идентификатор должности пользователя в справочнике.
     * @return array Массив идентификаторов доступных пользователю категорий комфорта.
     */
    private function getComfortCategoryIds($positionId)
    {
        $comfortHlBlockName = $this->getHlblockByName($this->codes['comfortability_availability']);
        $resComfAvailability = $comfortHlBlockName::getList(
            [
                'select' => ['*'],
                'order' => ['ID' => 'ASC'],
                'filter' => ['UF_COMF_AVAIL_POSITION' => $positionId]
            ]
        )->fetchAll();
        if (count($resComfAvailability) > 0) {
            return array_column($resComfAvailability, 'UF_COMF_AVAIL_CATEGORY');
        } else {
            return [];
        }
    }

    /**
     * Получить марки автомобилей.
     * @param array $carBrandsIds Массив идентификаторов марок автомобилей.
     * @return array Массив марок автомобилей указанных моделей.
     */
    private function getCarBrands($carBrandsIds)
    {
        print_r($carBrandsIds);
        $brandHlBlockName = $this->getHlblockByName($this->codes['car_brands']);
        $resBrands = $brandHlBlockName::getList(
            [
                'select' => ['*'],
                'order' => ['ID' => 'ASC'],
                'filter' => ['=ID' => $carBrandsIds]
            ]
        )->fetchAll();
        if (count($resBrands) > 0) {
            return $resBrands;
        } else {
            return [];
        }
    }

    /**
     * Получить идентификаторы автомобилей, которые заняты на период, выбранный пользователем.
     * @return array Массив идентификаторов занятых автомобилей.
     */
    private function getTakenCars()
    {
        $drivesHlBlockName = $this->getHlblockByName($this->codes['job_car_bookings']);
        $resDrives = $drivesHlBlockName::getList(
            [
                'select' => ['UF_CAR_BOOKING_CHOSEN_CAR'],
                'order' => ['ID' => 'ASC'],
                'filter' => [
                    /*
                     * Автомобиль используется не текущим пользователем. 
                     */
                    '!=UF_CAR_BOOKING_USER_ID' => $GLOBALS['USER']->GetID(),
                    /*
                     * Определить, что период для использования данного автомобиля попадает
                     * в выбранный пользователем период. 
                     */
                    [
                        'LOGIC' => 'OR',
                        /*
                         * Выбранный пользователем период полностью включен в чужой период. 
                         */
                        [
                            '<=UF_CAR_BOOKING_START' => $this->startTime, 
                            '>=UF_CAR_BOOKING_END' => $this->endTime
                        ],
                        /*
                         * Наоборот, чужой период полностью внутри выбранного. 
                         */
                        [
                            '>=UF_CAR_BOOKING_START' => $this->startTime, 
                            '<=UF_CAR_BOOKING_END' => $this->endTime
                        ],
                        /*
                         * Пересечения временных промежутков.
                         */
                        [
                            '<=UF_CAR_BOOKING_START' => $this->startTime,
                            '>=UF_CAR_BOOKING_END' => $this->startTime
                        ],
                        [
                            '>=UF_CAR_BOOKING_START' => $this->startTime,
                            '<=UF_CAR_BOOKING_END' => $this->startTime
                        ],
                        [
                            '<=UF_CAR_BOOKING_START' => $this->endTime,
                            '>=UF_CAR_BOOKING_END' => $this->endTime
                        ],
                        [
                            '>=UF_CAR_BOOKING_START' => $this->endTime,
                            '<=UF_CAR_BOOKING_END' => $this->endTime
                        ]
                    ]
                ]
            ]
        )->fetchAll();
        if (count($resDrives) > 0) {
            return array_column($resDrives, 'UF_CAR_BOOKING_CHOSEN_CAR');
        } else {
            return [];
        }
    }

    /**
     * Получить доступные пользователю служебные автомобили.
     * @param array $takenCarsIds Идентификаторы уже занятых автомобилей.
     * @param array $modelIds Идентификаторы доступных пользователю моделей автомобилей.
     * @return array Служебные автомобили, которыми может воспользоваться пользователь.
     */
    private function getAvailableCars($takenCarsIds, $modelIds)
    {
        $carsIblock = $this->getIblockByName($this->codes['job_cars']);

        $arSelect = ['ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_PICTURE', 'PROPERTY_*'];
        $arFilter = [
            /*
             * Идентификатор инфоблока автомобилей. 
             */
            '=IBLOCK_ID' => $carsIblock['ID'],
            /*
             * В выборку войдут только незанятые автомобили. 
             */
            '!=ID' => $takenCarsIds,
            /*
             * Только модели доступного уровня комфорта. 
             */
            '=PROPERTY_CAR_MODEL' => $modelIds
        ];
        $res = CIBLOCKElement::GetList([], $arFilter, false, false, $arSelect);

        $arCars = [];
        while ($row = $res->GetNextElement()) {
            $arCars[] = array_merge($row->GetFields(), $row->GetProperties());
        }
        
        return $arCars;
    }

    /**
     * Получить подробную информацию об указанных водителях.
     * @param array $driversIds Массив идентификаторов водителей.
     * @return array Массив водителей.
     */
    private function getCarDrivers($driversIds)
    {
        $driversIblock = $this->getIblockByName($this->codes['job_cars_drivers']);

        $arSelect = ['ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_PICTURE', 'PROPERTY_*'];
        $arFilter = [
            '=IBLOCK_ID' => $driversIblock['ID'],
            '=ID' => $driversIds
        ];
        $res = CIBlockElement::GetList([], $arFilter, false, false, $arSelect);

        $arDrivers = [];
        while ($row = $res->GetNextElement()) {
            $arDrivers[] = array_merge($row->GetFields(), $row->GetProperties());
        }

        return $arDrivers;
    }

    /**
     * Получить сущность highload-блока по его имени.
     * @param string $name Имя highload-блока.
     * @return string|bool Имя класса сущности для выполнения запросов.
     */
    private function getHlblockByName($name)
    {
        $hlblock = HL\HighloadBlockTable::getList([
            'filter' => ['=NAME' => $name]
        ])->fetch();
        if (!$hlblock) {
            return false;
        }

        $hlClassName = (HL\HighloadBlockTable::compileEntity($hlblock))->getDataClass();
        return $hlClassName;
    }
    
    /**
     * Получить инфоблок по его символьному коду.
     * @param string $name Имя инфоблока.
     * @return string|bool Инфоблок с указанным именем.
     */
    private function getIblockByName($name)
    {
        $iblock = CIBlock::GetList(
            [],
            ['CODE' => $name]
        )->fetch();
        if (!$iblock) {
            return false;
        }
        return $iblock;
    }

    /**
     * Получить идентификаторы водителей из массива автомобилей.
     * @param array $cars Массив автомобилей.
     * @return array Массив идентификаторов водителей.
     */
    private function getDriverIds($cars)
    {
        $result = [];
        foreach ($cars as $car) {
            $result[] = $car['CAR_DRIVER']['VALUE'];
        }
        return $result;
    }
}
