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
            $cars = $this->findCars();
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
     * Получить список доступных служебных автомобилей.
     * @return array Массив с данными о доступных служебных автомобилях.
     */
    private function findCars()
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


        $this->arResult['hl'] = $carModels;
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
     * @param array Идентификаторы категорий комфорта.
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
     * @param int Идентификатор должности пользователя в справочнике.
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
     * Получить сущность highload-блока по его имени.
     * @param string $name Имя highload-блока.
     * @return string Имя класса сущности для выполнения запросов.
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
}
